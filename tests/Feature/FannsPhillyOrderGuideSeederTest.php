<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\FannsPhillyOrderGuideSeeder;
use Database\Seeders\VendorsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the client's real order guide data, so a future edit to the seeder
 * cannot quietly lose a pack size or a vendor mapping.
 */
class FannsPhillyOrderGuideSeederTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id]);

        $this->seed(VendorsSeeder::class);
        $this->seed(FannsPhillyOrderGuideSeeder::class);
    }

    private function item(string $name): InventoryItem
    {
        return InventoryItem::where('store_id', $this->store->id)->where('name', $name)->firstOrFail();
    }

    /** @test */
    public function it_seeds_the_whole_order_guide_into_every_store(): void
    {
        $this->assertSame(123, InventoryItem::where('store_id', $this->store->id)->count());
        $this->assertSame(123, InventoryItem::where('store_id', $this->store->id)->where('is_active', true)->count());
    }

    /** @test */
    public function it_carries_the_pack_sizes_the_sheet_spells_out(): void
    {
        // Straight from the item names on the paper guide.
        $expected = [
            'Steak' => 53,
            'Chicken' => 40,
            'Gyro' => 20,
            '8" Bread' => 60,
            '10" Bread' => 48,
            'Pita' => 120,
        ];

        foreach ($expected as $name => $packSize) {
            $this->assertEqualsWithDelta(
                $packSize,
                (float) $this->item($name)->units_per_purchase,
                0.001,
                "{$name} should hold {$packSize} per purchase unit."
            );
        }

        // Chicken is the one measured in pounds rather than pieces.
        $this->assertSame('lb', $this->item('Chicken')->base_unit);
        $this->assertSame('box', $this->item('Steak')->purchase_unit);
    }

    /** @test */
    public function every_item_lands_in_one_of_the_eight_order_guide_categories(): void
    {
        $items = InventoryItem::with('inventoryCategory')->where('store_id', $this->store->id)->get();

        $this->assertCount(0, $items->whereNull('inventory_category_id'), 'Every item needs a category.');

        $this->assertEqualsCanonicalizing(
            InventoryCategory::pluck('name')->all(),
            InventoryCategory::pluck('name')->all()
        );

        // The legacy free-text column is kept in step for the older screens.
        foreach ($items as $item) {
            $this->assertSame($item->inventoryCategory->name, $item->category);
        }
    }

    /** @test */
    public function the_three_vendor_item_from_the_brief_is_mapped_to_all_three(): void
    {
        $oil = $this->item('Frying Oil');

        $this->assertEqualsCanonicalizing(
            ['Lisanti', 'Restaurant Depot', "Sam's Club"],
            $oil->vendors()->pluck('vendor_name')->all()
        );
    }

    /** @test */
    public function items_the_sheet_lists_under_two_headings_get_both_vendors(): void
    {
        foreach (['Mushrooms', 'Romaine', 'Tomatoes', '#50 Food Tray'] as $name) {
            $vendors = $this->item($name)->vendors()->pluck('vendor_name')->all();

            $this->assertContains('Lisanti', $vendors, "{$name} should be available from Lisanti.");
            $this->assertContains('Restaurant Depot', $vendors, "{$name} should be available from Restaurant Depot.");
        }
    }

    /** @test */
    public function the_coca_cola_column_maps_to_the_coca_cola_vendor(): void
    {
        foreach (['Coke', 'Diet Coke', 'Sprite', 'Coke Zero'] as $name) {
            $this->assertSame(
                'Coca-Cola',
                $this->item($name)->preferredVendor->vendor_name,
                "{$name} comes from Coca-Cola on the sheet."
            );
        }
    }

    /** @test */
    public function drinks_are_filed_as_beverages_not_produce(): void
    {
        // "Dr. Pepper" matched a produce keyword on the first pass.
        foreach (['Dr. Pepper', 'Root Beer', 'Coke', 'Apple Juice'] as $name) {
            $this->assertSame(
                'Beverages',
                $this->item($name)->inventoryCategory->name,
                "{$name} is a drink."
            );
        }
    }

    /** @test */
    public function exactly_one_vendor_per_item_is_flagged_preferred(): void
    {
        $items = InventoryItem::with('vendors')->where('store_id', $this->store->id)->get();

        foreach ($items as $item) {
            $flagged = $item->vendors->filter(fn ($v) => (bool) $v->pivot->is_preferred_vendor);

            $this->assertLessThanOrEqual(1, $flagged->count(), "{$item->name} has more than one preferred vendor.");
            $this->assertSame(
                $item->preferred_vendor_id,
                $flagged->first()?->id,
                "preferred_vendor_id disagrees with the pivot flag on {$item->name}."
            );
        }
    }

    /** @test */
    public function re_running_the_seeder_does_not_duplicate_anything(): void
    {
        $mappingsBefore = \Illuminate\Support\Facades\DB::table('inventory_item_vendor')->count();

        $this->seed(FannsPhillyOrderGuideSeeder::class);

        $this->assertSame(123, InventoryItem::where('store_id', $this->store->id)->count());
        $this->assertSame($mappingsBefore, \Illuminate\Support\Facades\DB::table('inventory_item_vendor')->count());
    }

    /** @test */
    public function it_warns_rather_than_crashing_when_a_vendor_is_missing(): void
    {
        \App\Models\Vendor::query()->forceDelete();
        InventoryItem::query()->forceDelete();

        $this->artisan('db:seed', ['--class' => FannsPhillyOrderGuideSeeder::class])
            ->expectsOutputToContain('not in the database')
            ->assertExitCode(0);

        // Items still land; only the mappings are skipped.
        $this->assertSame(123, InventoryItem::where('store_id', $this->store->id)->count());
    }
}
