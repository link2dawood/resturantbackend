<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MenuItem;
use App\Models\MenuItemSold;
use App\Models\Store;
use App\Models\User;
use App\Services\Inventory\RecipeService;
use App\Services\Inventory\VarianceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 Part 2 — the client wants the Square CSV as the normal route and
 * typing the numbers in as the backup. Both have to land in the same place,
 * because variance reads one table.
 */
class ManualSalesEntryTest extends TestCase
{
    use RefreshDatabase;

    private string $week = '2026-08-03';

    private Store $store;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Round Rock']);
        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->owner->ownedStores()->attach($this->store->id);
    }

    private function menuItem(string $name = 'Steak Sandwich'): MenuItem
    {
        return MenuItem::factory()->create(['store_id' => $this->store->id, 'name' => $name]);
    }

    private function save(array $sold)
    {
        return $this->actingAs($this->owner)->post(route('admin.square-import.manual.store'), [
            'store_id' => $this->store->id,
            'week_start_date' => $this->week,
            'sold' => $sold,
        ]);
    }

    /** @test */
    public function typed_sales_are_stored_the_same_way_the_csv_stores_them(): void
    {
        $menu = $this->menuItem();

        $this->save([$menu->id.'-large' => 10])->assertRedirect();

        $this->assertDatabaseHas('menu_items_sold', [
            'store_id' => $this->store->id,
            'menu_item_id' => $menu->id,
            'size_variant' => 'large',
            'quantity_sold' => 10,
            'is_matched' => true,
        ]);
    }

    /** @test */
    public function typed_sales_feed_the_variance_calculation(): void
    {
        // The client's own example, with the sales typed rather than imported:
        // 53 pieces of steak, 10 large sandwiches at 2 pieces each, 23 counted.
        $steak = InventoryItem::factory()->create([
            'store_id' => $this->store->id, 'name' => 'Steak',
            'base_unit' => 'each', 'purchase_unit' => 'box', 'units_per_purchase' => 53,
        ]);
        InventoryStock::factory()->create([
            'inventory_item_id' => $steak->id, 'store_id' => $this->store->id,
            'week_start_date' => $this->week, 'starting_stock' => 53, 'actual_ending_stock' => 23,
        ]);

        $menu = $this->menuItem();
        app(RecipeService::class)->saveVersion($menu, 'large', [
            ['inventory_item_id' => $steak->id, 'quantity' => 2, 'unit' => 'each'],
        ]);

        $this->save([$menu->id.'-large' => 10])->assertRedirect();

        $line = (new VarianceCalculationService)->calculate($this->store->id, $steak->id, $this->week);

        $this->assertEqualsWithDelta(20.0, $line->theoreticalUsage, 0.001);
        $this->assertEqualsWithDelta(33.0, $line->theoreticalEnding, 0.001);
        $this->assertEqualsWithDelta(-10.0, $line->variance, 0.001);
    }

    /** @test */
    public function saving_replaces_the_week_rather_than_adding_to_it(): void
    {
        $menu = $this->menuItem();

        $this->save([$menu->id.'-large' => 10])->assertRedirect();
        $this->save([$menu->id.'-large' => 4])->assertRedirect();

        // Entering it twice must not read as 14 sandwiches sold.
        $this->assertSame(1, MenuItemSold::where('store_id', $this->store->id)->count());
        $this->assertEqualsWithDelta(4.0, (float) MenuItemSold::first()->quantity_sold, 0.001);
    }

    /** @test */
    public function blank_and_zero_lines_are_left_out(): void
    {
        $menu = $this->menuItem();
        $other = $this->menuItem('Chicken Sandwich');

        $this->save([$menu->id.'-large' => 3, $other->id.'-regular' => 0, $other->id.'-mini' => ''])
            ->assertRedirect();

        $this->assertSame(1, MenuItemSold::count());
    }

    /** @test */
    public function a_menu_item_from_another_store_is_ignored(): void
    {
        $foreign = MenuItem::factory()->create([
            'store_id' => Store::factory()->create()->id,
            'name' => 'Someone Elses Sandwich',
        ]);

        $this->save([$foreign->id.'-large' => 99])->assertRedirect();

        $this->assertSame(0, MenuItemSold::count());
    }

    /** @test */
    public function the_screen_is_owner_facing_and_reachable_from_the_import(): void
    {
        $this->menuItem();

        $this->actingAs($this->owner)
            ->get(route('admin.square-import.manual', ['store_id' => $this->store->id, 'week_start_date' => $this->week]))
            ->assertOk()
            ->assertSee('Enter sales by hand')
            ->assertSee('Steak Sandwich');

        $this->actingAs($this->owner)
            ->get(route('admin.square-import.form', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee(route('admin.square-import.manual', ['store_id' => $this->store->id]), false);

        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $manager->assignedStoresPivot()->attach($this->store->id);

        $this->actingAs($manager)
            ->get(route('admin.square-import.manual', ['store_id' => $this->store->id]))
            ->assertForbidden();
    }
}
