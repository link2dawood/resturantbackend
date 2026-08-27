<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreInventoryTargetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $roundRock;

    private Store $downtown;

    private InventoryCategory $meats;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->roundRock = Store::factory()->create(['created_by' => $this->admin->id, 'store_info' => 'Round Rock']);
        $this->downtown = Store::factory()->create(['created_by' => $this->admin->id, 'store_info' => 'Downtown']);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
    }

    private function item(Store $store, string $name, array $overrides = []): InventoryItem
    {
        return InventoryItem::factory()->create(array_merge([
            'store_id' => $store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => $name,
            'base_unit' => 'portion',
            'purchase_unit' => 'box',
            'units_per_purchase' => 53,
            'is_active' => true,
        ], $overrides));
    }

    // ---- The core requirement ----------------------------------------------

    /** @test */
    public function each_store_keeps_its_own_level_for_the_same_item(): void
    {
        $rrSteak = $this->item($this->roundRock, 'Steak');
        $dtSteak = $this->item($this->downtown, 'Steak');

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.update', $this->roundRock), [
            'targets' => [$rrSteak->id => ['target_stock_level' => 15, 'min_stock_level' => 4]],
        ])->assertRedirect();

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.update', $this->downtown), [
            'targets' => [$dtSteak->id => ['target_stock_level' => 8, 'min_stock_level' => 2]],
        ])->assertRedirect();

        $this->assertEquals(15, (float) StoreInventoryTarget::where('store_id', $this->roundRock->id)
            ->where('inventory_item_id', $rrSteak->id)->value('target_stock_level'));
        $this->assertEquals(8, (float) StoreInventoryTarget::where('store_id', $this->downtown->id)
            ->where('inventory_item_id', $dtSteak->id)->value('target_stock_level'));
    }

    /** @test */
    public function the_page_renders_with_the_items_and_current_targets(): void
    {
        $steak = $this->item($this->roundRock, 'Steak');
        StoreInventoryTarget::create([
            'store_id' => $this->roundRock->id, 'inventory_item_id' => $steak->id,
            'target_stock_level' => 15, 'min_stock_level' => 4,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.inventory-targets.index', $this->roundRock))
            ->assertOk()
            ->assertSee('Inventory Targets')
            ->assertSee('Round Rock')
            ->assertSee('Steak')
            ->assertSee('Min Stock (reorder point)')
            ->assertSee('15');
    }

    /** @test */
    public function items_without_a_target_are_flagged(): void
    {
        $this->item($this->roundRock, 'Steak');

        $this->actingAs($this->admin)
            ->get(route('admin.inventory-targets.index', $this->roundRock))
            ->assertOk()
            ->assertSee('no target')
            ->assertSee('0</strong> of <strong>1', false);
    }

    /** @test */
    public function a_target_is_stored_in_purchase_units_and_shown_in_base_units(): void
    {
        $steak = $this->item($this->roundRock, 'Steak'); // 53 portions per box

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.update', $this->roundRock), [
            'targets' => [$steak->id => ['target_stock_level' => 15, 'min_stock_level' => 4]],
        ])->assertRedirect();

        $target = StoreInventoryTarget::where('inventory_item_id', $steak->id)->firstOrFail();
        $this->assertEquals(15, (float) $target->target_stock_level);
        $this->assertEquals(795.0, $target->baseTargetFor($steak)); // 15 boxes x 53

        $this->actingAs($this->admin)
            ->get(route('admin.inventory-targets.index', $this->roundRock))
            ->assertOk()
            ->assertSee('795 portion');
    }

    /** @test */
    public function clearing_both_fields_removes_the_target_rather_than_storing_zero(): void
    {
        $steak = $this->item($this->roundRock, 'Steak');
        StoreInventoryTarget::create([
            'store_id' => $this->roundRock->id, 'inventory_item_id' => $steak->id,
            'target_stock_level' => 15, 'min_stock_level' => 4,
        ]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.update', $this->roundRock), [
            'targets' => [$steak->id => ['target_stock_level' => null, 'min_stock_level' => null]],
        ])->assertRedirect();

        $this->assertDatabaseMissing('store_inventory_targets', [
            'store_id' => $this->roundRock->id, 'inventory_item_id' => $steak->id,
        ]);
    }

    /** @test */
    public function saving_twice_updates_rather_than_duplicating(): void
    {
        $steak = $this->item($this->roundRock, 'Steak');

        foreach ([15, 18] as $level) {
            $this->actingAs($this->admin)->post(route('admin.inventory-targets.update', $this->roundRock), [
                'targets' => [$steak->id => ['target_stock_level' => $level, 'min_stock_level' => 4]],
            ])->assertRedirect();
        }

        $this->assertSame(1, StoreInventoryTarget::where('inventory_item_id', $steak->id)->count());
        $this->assertEquals(18, (float) StoreInventoryTarget::where('inventory_item_id', $steak->id)->value('target_stock_level'));
    }

    /** @test */
    public function a_negative_target_is_rejected(): void
    {
        $steak = $this->item($this->roundRock, 'Steak');

        $this->actingAs($this->admin)->postJson(route('admin.inventory-targets.update', $this->roundRock), [
            'targets' => [$steak->id => ['target_stock_level' => -5]],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('store_inventory_targets', ['inventory_item_id' => $steak->id]);
    }

    // ---- Bulk default ------------------------------------------------------

    /** @test */
    public function a_default_can_be_applied_to_every_item_at_once(): void
    {
        $this->item($this->roundRock, 'Steak');
        $this->item($this->roundRock, 'Chicken');
        $this->item($this->roundRock, 'Gyro');

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.bulk-default', $this->roundRock), [
            'default_target' => 10, 'default_min' => 3,
        ])->assertRedirect();

        $this->assertSame(3, StoreInventoryTarget::where('store_id', $this->roundRock->id)->count());
        $this->assertSame(3, StoreInventoryTarget::where('store_id', $this->roundRock->id)
            ->where('target_stock_level', 10)->count());
    }

    /** @test */
    public function the_bulk_default_leaves_existing_targets_alone_unless_asked(): void
    {
        $steak = $this->item($this->roundRock, 'Steak');
        $chicken = $this->item($this->roundRock, 'Chicken');
        StoreInventoryTarget::create([
            'store_id' => $this->roundRock->id, 'inventory_item_id' => $steak->id,
            'target_stock_level' => 15, 'min_stock_level' => 4,
        ]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.bulk-default', $this->roundRock), [
            'default_target' => 10,
        ])->assertRedirect();

        $this->assertEquals(15, (float) StoreInventoryTarget::where('inventory_item_id', $steak->id)->value('target_stock_level'));
        $this->assertEquals(10, (float) StoreInventoryTarget::where('inventory_item_id', $chicken->id)->value('target_stock_level'));
    }

    /** @test */
    public function the_bulk_default_can_overwrite_when_asked(): void
    {
        $steak = $this->item($this->roundRock, 'Steak');
        StoreInventoryTarget::create([
            'store_id' => $this->roundRock->id, 'inventory_item_id' => $steak->id,
            'target_stock_level' => 15, 'min_stock_level' => 4,
        ]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.bulk-default', $this->roundRock), [
            'default_target' => 10, 'overwrite_existing' => true,
        ])->assertRedirect();

        $this->assertEquals(10, (float) StoreInventoryTarget::where('inventory_item_id', $steak->id)->value('target_stock_level'));
    }

    /** @test */
    public function the_bulk_default_can_be_limited_to_one_category(): void
    {
        $breads = InventoryCategory::where('name', 'Breads')->firstOrFail();
        $steak = $this->item($this->roundRock, 'Steak');
        $bread = $this->item($this->roundRock, '8 inch Bread', ['inventory_category_id' => $breads->id]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.bulk-default', $this->roundRock), [
            'default_target' => 10, 'inventory_category_id' => $breads->id,
        ])->assertRedirect();

        $this->assertDatabaseMissing('store_inventory_targets', ['inventory_item_id' => $steak->id]);
        $this->assertDatabaseHas('store_inventory_targets', ['inventory_item_id' => $bread->id, 'target_stock_level' => 10]);
    }

    /** @test */
    public function the_bulk_default_ignores_inactive_items(): void
    {
        $this->item($this->roundRock, 'Steak');
        $retired = $this->item($this->roundRock, 'Retired Item', ['is_active' => false]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.bulk-default', $this->roundRock), [
            'default_target' => 10,
        ])->assertRedirect();

        $this->assertDatabaseMissing('store_inventory_targets', ['inventory_item_id' => $retired->id]);
    }

    // ---- Copy between stores -----------------------------------------------

    /** @test */
    public function targets_can_be_copied_from_another_store(): void
    {
        $rrSteak = $this->item($this->roundRock, 'Steak');
        $rrChicken = $this->item($this->roundRock, 'Chicken');
        $dtSteak = $this->item($this->downtown, 'Steak');
        $dtChicken = $this->item($this->downtown, 'Chicken');

        StoreInventoryTarget::create(['store_id' => $this->roundRock->id, 'inventory_item_id' => $rrSteak->id, 'target_stock_level' => 15, 'min_stock_level' => 4]);
        StoreInventoryTarget::create(['store_id' => $this->roundRock->id, 'inventory_item_id' => $rrChicken->id, 'target_stock_level' => 8, 'min_stock_level' => 2]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.copy-from', $this->downtown), [
            'source_store_id' => $this->roundRock->id,
        ])->assertRedirect();

        // Matched by name, because ids never line up between stores.
        $this->assertEquals(15, (float) StoreInventoryTarget::where('store_id', $this->downtown->id)
            ->where('inventory_item_id', $dtSteak->id)->value('target_stock_level'));
        $this->assertEquals(8, (float) StoreInventoryTarget::where('store_id', $this->downtown->id)
            ->where('inventory_item_id', $dtChicken->id)->value('target_stock_level'));

        // The source store is untouched.
        $this->assertSame(2, StoreInventoryTarget::where('store_id', $this->roundRock->id)->count());
    }

    /** @test */
    public function copying_leaves_targets_already_set_here_alone_unless_asked(): void
    {
        $rrSteak = $this->item($this->roundRock, 'Steak');
        $dtSteak = $this->item($this->downtown, 'Steak');

        StoreInventoryTarget::create(['store_id' => $this->roundRock->id, 'inventory_item_id' => $rrSteak->id, 'target_stock_level' => 15]);
        StoreInventoryTarget::create(['store_id' => $this->downtown->id, 'inventory_item_id' => $dtSteak->id, 'target_stock_level' => 8]);

        $response = $this->actingAs($this->admin)->post(route('admin.inventory-targets.copy-from', $this->downtown), [
            'source_store_id' => $this->roundRock->id,
        ])->assertRedirect();

        $this->assertEquals(8, (float) StoreInventoryTarget::where('inventory_item_id', $dtSteak->id)->value('target_stock_level'));
        $response->assertSessionHas('success', fn ($m) => str_contains($m, 'already had a target'));
    }

    /** @test */
    public function copying_can_overwrite_when_asked(): void
    {
        $rrSteak = $this->item($this->roundRock, 'Steak');
        $dtSteak = $this->item($this->downtown, 'Steak');

        StoreInventoryTarget::create(['store_id' => $this->roundRock->id, 'inventory_item_id' => $rrSteak->id, 'target_stock_level' => 15]);
        StoreInventoryTarget::create(['store_id' => $this->downtown->id, 'inventory_item_id' => $dtSteak->id, 'target_stock_level' => 8]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.copy-from', $this->downtown), [
            'source_store_id' => $this->roundRock->id, 'overwrite_existing' => true,
        ])->assertRedirect();

        $this->assertEquals(15, (float) StoreInventoryTarget::where('inventory_item_id', $dtSteak->id)->value('target_stock_level'));
    }

    /** @test */
    public function copying_reports_items_the_destination_store_does_not_carry(): void
    {
        $rrSteak = $this->item($this->roundRock, 'Steak');
        $rrGyro = $this->item($this->roundRock, 'Gyro');
        $this->item($this->downtown, 'Steak');

        StoreInventoryTarget::create(['store_id' => $this->roundRock->id, 'inventory_item_id' => $rrSteak->id, 'target_stock_level' => 15]);
        StoreInventoryTarget::create(['store_id' => $this->roundRock->id, 'inventory_item_id' => $rrGyro->id, 'target_stock_level' => 4]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.copy-from', $this->downtown), [
            'source_store_id' => $this->roundRock->id,
        ])->assertRedirect()
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Gyro'));

        $this->assertSame(1, StoreInventoryTarget::where('store_id', $this->downtown->id)->count());
    }

    /** @test */
    public function a_store_cannot_copy_from_itself(): void
    {
        $steak = $this->item($this->roundRock, 'Steak');
        StoreInventoryTarget::create(['store_id' => $this->roundRock->id, 'inventory_item_id' => $steak->id, 'target_stock_level' => 15]);

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.copy-from', $this->roundRock), [
            'source_store_id' => $this->roundRock->id,
        ])->assertSessionHas('error');
    }

    // ---- Access ------------------------------------------------------------

    /** @test */
    public function a_manager_cannot_reach_a_store_they_are_not_assigned_to(): void
    {
        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->roundRock->id]);
        $manager->assignedStoresPivot()->attach($this->roundRock->id);

        $this->actingAs($manager)->get(route('admin.inventory-targets.index', $this->roundRock))->assertOk();
        $this->actingAs($manager)->get(route('admin.inventory-targets.index', $this->downtown))->assertForbidden();

        $steak = $this->item($this->downtown, 'Steak');
        $this->actingAs($manager)->post(route('admin.inventory-targets.update', $this->downtown), [
            'targets' => [$steak->id => ['target_stock_level' => 99]],
        ])->assertForbidden();

        $this->assertDatabaseMissing('store_inventory_targets', ['inventory_item_id' => $steak->id]);
    }

    /** @test */
    public function a_posted_item_id_from_another_store_is_ignored(): void
    {
        $mine = $this->item($this->roundRock, 'Steak');
        $theirs = $this->item($this->downtown, 'Their Steak');

        $this->actingAs($this->admin)->post(route('admin.inventory-targets.update', $this->roundRock), [
            'targets' => [
                $mine->id => ['target_stock_level' => 15],
                $theirs->id => ['target_stock_level' => 99],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('store_inventory_targets', ['inventory_item_id' => $mine->id, 'store_id' => $this->roundRock->id]);
        $this->assertDatabaseMissing('store_inventory_targets', ['inventory_item_id' => $theirs->id]);
    }

    /** @test */
    public function copying_from_a_store_the_user_cannot_access_is_refused(): void
    {
        $rrSteak = $this->item($this->roundRock, 'Steak');
        StoreInventoryTarget::create(['store_id' => $this->roundRock->id, 'inventory_item_id' => $rrSteak->id, 'target_stock_level' => 15]);
        $this->item($this->downtown, 'Steak');

        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->downtown->id]);
        $manager->assignedStoresPivot()->attach($this->downtown->id);

        $this->actingAs($manager)->post(route('admin.inventory-targets.copy-from', $this->downtown), [
            'source_store_id' => $this->roundRock->id,
        ])->assertSessionHas('error');

        $this->assertSame(0, StoreInventoryTarget::where('store_id', $this->downtown->id)->count());
    }

    // ---- Seeder ------------------------------------------------------------

    /** @test */
    public function the_seeder_gives_only_the_first_store_targets(): void
    {
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->seed(\Database\Seeders\InventoryItemsSeeder::class);
        $this->seed(\Database\Seeders\StoreInventoryTargetsSeeder::class);

        $first = Store::orderBy('id')->first();

        $this->assertSame(10, StoreInventoryTarget::where('store_id', $first->id)->count());
        // The other stores stay empty on purpose, so "copy from another store"
        // has something to demonstrate.
        $this->assertSame(0, StoreInventoryTarget::where('store_id', '!=', $first->id)->count());

        $steak = InventoryItem::where('store_id', $first->id)->where('name', 'Steak')->firstOrFail();
        $target = StoreInventoryTarget::where('store_id', $first->id)->where('inventory_item_id', $steak->id)->firstOrFail();
        $this->assertEquals(15, (float) $target->target_stock_level);
        $this->assertEquals(4, (float) $target->min_stock_level);
        $this->assertEquals(795.0, $target->baseTargetFor($steak));
    }

    /** @test */
    public function re_running_the_target_seeder_does_not_duplicate(): void
    {
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->seed(\Database\Seeders\InventoryItemsSeeder::class);
        $this->seed(\Database\Seeders\StoreInventoryTargetsSeeder::class);
        $this->seed(\Database\Seeders\StoreInventoryTargetsSeeder::class);

        $this->assertSame(10, StoreInventoryTarget::count());
    }
}
