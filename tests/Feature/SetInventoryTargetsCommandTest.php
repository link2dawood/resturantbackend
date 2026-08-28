<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetInventoryTargetsCommandTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    private InventoryCategory $meats;

    private InventoryCategory $breads;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->storeA = Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Round Rock']);
        $this->storeB = Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Downtown']);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
        $this->breads = InventoryCategory::where('name', 'Breads')->firstOrFail();
    }

    private function item(Store $store, string $name, ?InventoryCategory $category = null, bool $active = true): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $store->id,
            'inventory_category_id' => ($category ?? $this->meats)->id,
            'name' => $name,
            'is_active' => $active,
        ]);
    }

    /** @test */
    public function a_dry_run_writes_nothing(): void
    {
        $this->item($this->storeA, 'Steak');

        $this->artisan('inventory:set-targets', ['--store' => $this->storeA->id])
            ->expectsOutputToContain('DRY RUN')
            ->assertExitCode(0);

        $this->assertSame(0, StoreInventoryTarget::count());
    }

    /** @test */
    public function commit_sets_a_target_on_every_item_without_one(): void
    {
        $this->item($this->storeA, 'Steak');
        $this->item($this->storeA, 'Chicken');

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--target' => 12, '--min' => 4, '--commit' => true,
        ])->assertExitCode(0);

        $this->assertSame(2, StoreInventoryTarget::count());

        $target = StoreInventoryTarget::first();
        $this->assertEqualsWithDelta(12, (float) $target->target_stock_level, 0.001);
        $this->assertEqualsWithDelta(4, (float) $target->min_stock_level, 0.001);
    }

    /** @test */
    public function an_existing_target_is_left_alone_unless_overwrite_is_passed(): void
    {
        $steak = $this->item($this->storeA, 'Steak');
        $this->item($this->storeA, 'Chicken');

        StoreInventoryTarget::create([
            'store_id' => $this->storeA->id, 'inventory_item_id' => $steak->id,
            'target_stock_level' => 15, 'min_stock_level' => 4,
        ]);

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--target' => 10, '--commit' => true,
        ])->assertExitCode(0);

        $this->assertEqualsWithDelta(
            15,
            (float) StoreInventoryTarget::where('inventory_item_id', $steak->id)->value('target_stock_level'),
            0.001,
            'A hand-tuned target must survive a bulk run.'
        );
        $this->assertSame(2, StoreInventoryTarget::count());
    }

    /** @test */
    public function overwrite_replaces_an_existing_target(): void
    {
        $steak = $this->item($this->storeA, 'Steak');
        StoreInventoryTarget::create([
            'store_id' => $this->storeA->id, 'inventory_item_id' => $steak->id,
            'target_stock_level' => 15,
        ]);

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--target' => 10, '--overwrite' => true, '--commit' => true,
        ])->assertExitCode(0);

        $this->assertEqualsWithDelta(
            10,
            (float) StoreInventoryTarget::where('inventory_item_id', $steak->id)->value('target_stock_level'),
            0.001
        );
    }

    /** @test */
    public function it_can_be_limited_to_one_category(): void
    {
        $steak = $this->item($this->storeA, 'Steak', $this->meats);
        $bread = $this->item($this->storeA, 'Pita', $this->breads);

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--category' => 'Breads', '--commit' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('store_inventory_targets', ['inventory_item_id' => $bread->id]);
        $this->assertDatabaseMissing('store_inventory_targets', ['inventory_item_id' => $steak->id]);
    }

    /** @test */
    public function an_unknown_category_is_refused_with_the_valid_names(): void
    {
        $this->item($this->storeA, 'Steak');

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--category' => 'Nonsense', '--commit' => true,
        ])->assertExitCode(1);

        $this->assertSame(0, StoreInventoryTarget::count());
    }

    /** @test */
    public function inactive_items_are_skipped(): void
    {
        $this->item($this->storeA, 'Steak');
        $retired = $this->item($this->storeA, 'Retired', null, false);

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--commit' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('store_inventory_targets', ['inventory_item_id' => $retired->id]);
        $this->assertSame(1, StoreInventoryTarget::count());
    }

    /** @test */
    public function omitting_the_store_covers_every_store(): void
    {
        $this->item($this->storeA, 'Steak');
        $this->item($this->storeB, 'Steak');

        $this->artisan('inventory:set-targets', ['--commit' => true])->assertExitCode(0);

        $this->assertSame(1, StoreInventoryTarget::where('store_id', $this->storeA->id)->count());
        $this->assertSame(1, StoreInventoryTarget::where('store_id', $this->storeB->id)->count());
    }

    /** @test */
    public function it_warns_when_the_reorder_point_is_above_the_target(): void
    {
        $this->item($this->storeA, 'Steak');

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--target' => 5, '--min' => 9,
        ])->expectsOutputToContain('above the target')->assertExitCode(0);
    }

    /** @test */
    public function a_negative_target_is_refused(): void
    {
        $this->item($this->storeA, 'Steak');

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--target' => -5, '--commit' => true,
        ])->assertExitCode(1);

        $this->assertSame(0, StoreInventoryTarget::count());
    }

    /** @test */
    public function suggestions_stop_being_zero_once_targets_exist(): void
    {
        // The whole point of the command.
        $steak = InventoryItem::factory()->create([
            'store_id' => $this->storeA->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Steak',
            'base_unit' => 'portion', 'purchase_unit' => 'box', 'units_per_purchase' => 53,
        ]);

        $service = app(\App\Services\Inventory\OrderSuggestionService::class);
        $week = \Illuminate\Support\Carbon::now()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);

        $before = $service->generateSuggestions($this->storeA, $week)->first();
        $this->assertSame(0.0, $before['suggested_order']);
        $this->assertFalse($before['has_target']);

        $this->artisan('inventory:set-targets', [
            '--store' => $this->storeA->id, '--target' => 12, '--min' => 4, '--commit' => true,
        ])->assertExitCode(0);

        $after = $service->generateSuggestions($this->storeA, $week)->first();
        $this->assertTrue($after['has_target']);
        $this->assertEqualsWithDelta(12.0, $after['suggested_order'], 0.001);
        $this->assertTrue($after['needs_order']);
    }
}
