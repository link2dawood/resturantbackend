<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 5 Part 2 — six drinks come as bag-in-box for the fountain and as 20oz
 * bottles. They are separate products: different pack, different price, and an
 * order has to say which one to buy.
 */
class DualFormatBeverageTest extends TestCase
{
    use RefreshDatabase;

    private const DRINKS = ['Coke', 'Coke Zero', 'Diet Coke', 'Dr. Pepper', 'Diet Dr. Pepper', 'Sprite'];

    /** @test */
    public function the_order_guide_carries_both_formats_of_every_dual_format_drink(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Round Rock']);

        $this->seed(\Database\Seeders\FannsPhillyOrderGuideSeeder::class);

        foreach (self::DRINKS as $drink) {
            foreach ([' (Bag-in-Box)', ' (20oz Bottle)'] as $format) {
                $this->assertDatabaseHas('inventory_items', ['name' => $drink.$format]);
            }

            // The ambiguous single row must not survive alongside the pair.
            $this->assertDatabaseMissing('inventory_items', ['name' => $drink]);
        }
    }

    /** @test */
    public function each_format_keeps_its_own_stock_target_and_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Store::factory()->create(['created_by' => $admin->id]);
        $this->seed(\Database\Seeders\FannsPhillyOrderGuideSeeder::class);

        $bib = InventoryItem::where('name', 'Coke (Bag-in-Box)')->firstOrFail();
        $bottle = InventoryItem::where('name', 'Coke (20oz Bottle)')->firstOrFail();

        $this->assertNotSame($bib->id, $bottle->id, 'The two formats must be separate items.');
        $this->assertSame($bib->store_id, $bottle->store_id);
        $this->assertSame($bib->inventory_category_id, $bottle->inventory_category_id);
    }

    /** @test */
    public function hamburger_meat_carries_the_forty_per_case_the_client_named(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Store::factory()->create(['created_by' => $admin->id]);
        $this->seed(\Database\Seeders\FannsPhillyOrderGuideSeeder::class);

        $meat = InventoryItem::where('name', 'Hamburger Meat')->firstOrFail();

        $this->assertEqualsWithDelta(40.0, (float) $meat->units_per_purchase, 0.001);
        $this->assertEqualsWithDelta(
            40.0,
            \App\Services\Inventory\CountEntry::maxPartial($meat),
            0.001,
            'The 40 cap comes from the pack size.'
        );
    }

    /** @test */
    public function the_migration_relabels_live_rows_without_losing_their_history(): void
    {
        // What production actually looks like: one ambiguous "Coke" row that
        // already has counts and orders hanging off it.
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $category = \App\Models\InventoryCategory::where('name', 'Beverages')->firstOrFail();

        $coke = InventoryItem::factory()->create([
            'store_id' => $store->id,
            'inventory_category_id' => $category->id,
            'name' => 'Coke',
            'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1,
        ]);
        $meat = InventoryItem::factory()->create([
            'store_id' => $store->id,
            'inventory_category_id' => $category->id,
            'name' => 'Hamburger Meat',
            'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 1,
        ]);
        \App\Models\StoreInventoryTarget::create([
            'store_id' => $store->id, 'inventory_item_id' => $coke->id, 'target_stock_level' => 9,
        ]);

        $migration = require database_path('migrations/2026_09_23_000001_split_dual_format_beverages_and_set_meat_pack_sizes.php');
        $migration->up();

        // The original row is relabelled, so its id, target and any order lines
        // survive rather than being orphaned by a delete-and-recreate.
        $this->assertSame('Coke (Bag-in-Box)', $coke->fresh()->name);
        $this->assertDatabaseHas('store_inventory_targets', [
            'inventory_item_id' => $coke->id, 'target_stock_level' => 9,
        ]);

        $bottle = InventoryItem::where('name', 'Coke (20oz Bottle)')->firstOrFail();
        $this->assertSame($store->id, $bottle->store_id);
        $this->assertNotSame($coke->id, $bottle->id);

        $this->assertEqualsWithDelta(40.0, (float) $meat->fresh()->units_per_purchase, 0.001);

        // Diet Dr. Pepper was never in the guide, so it is created outright.
        $this->assertDatabaseHas('inventory_items', ['name' => 'Diet Dr. Pepper (Bag-in-Box)']);

        // Running it twice must not double the rows.
        $migration->up();
        $this->assertSame(1, InventoryItem::where('name', 'Coke (20oz Bottle)')->count());
        $this->assertSame(1, InventoryItem::where('name', 'Coke (Bag-in-Box)')->count());
    }
}
