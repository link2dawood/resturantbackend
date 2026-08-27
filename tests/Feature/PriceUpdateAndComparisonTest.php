<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPrice;
use App\Services\Inventory\PriceComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceUpdateAndComparisonTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $store;

    private InventoryCategory $meats;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $this->admin->id]);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
    }

    private function item(array $overrides = []): InventoryItem
    {
        return InventoryItem::factory()->create(array_merge([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Ribeye Steak',
            'base_unit' => 'oz',
            'purchase_unit' => 'case',
            'units_per_purchase' => 640,
            'is_active' => true,
        ], $overrides));
    }

    private function vendor(string $name, string $type = 'Food'): Vendor
    {
        return Vendor::factory()->create(['vendor_name' => $name, 'vendor_type' => $type]);
    }

    private function price(Vendor $vendor, InventoryItem $item, float $price, string $unit, string $date): VendorPrice
    {
        return VendorPrice::create([
            'vendor_id' => $vendor->id,
            'inventory_item_id' => $item->id,
            'price' => $price,
            'price_unit' => $unit,
            'effective_date' => $date,
            'entered_by' => $this->admin->id,
        ]);
    }

    // ---- Bulk update -------------------------------------------------------

    /** @test */
    public function the_update_page_renders_at_the_pricing_path(): void
    {
        $this->item();
        $this->vendor('Lisanti');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.index', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('Ribeye Steak');

        $this->assertStringContainsString('/pricing/update', route('admin.vendor-prices.index'));
        $response->assertSee('Compare prices');
    }

    /** @test */
    public function saving_writes_only_the_rows_that_actually_changed(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');
        $depot = $this->vendor('Restaurant Depot');

        $post = fn (array $prices) => $this->actingAs($this->admin)
            ->post(route('admin.vendor-prices.bulk'), ['store_id' => $this->store->id, 'prices' => $prices]);

        $post([$item->id => [$lisanti->id => 145.00, $depot->id => 152.50]]);
        // Only Lisanti moves; Restaurant Depot is resubmitted unchanged.
        $post([$item->id => [$lisanti->id => 149.00, $depot->id => 152.50]]);

        $this->assertSame(2, VendorPrice::where('vendor_id', $lisanti->id)->count());
        $this->assertSame(1, VendorPrice::where('vendor_id', $depot->id)->count());
    }

    /** @test */
    public function an_item_with_no_price_from_any_vendor_is_flagged_on_the_update_page(): void
    {
        $this->item(['name' => 'Zeta Unpriced Fixture']);
        $this->vendor('Lisanti');

        $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.index', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('table-warning', false)
            ->assertSee('no price');
    }

    /** @test */
    public function the_update_page_can_be_filtered_by_category(): void
    {
        $breads = InventoryCategory::where('name', 'Breads')->firstOrFail();
        $this->item(['name' => 'Zeta Meat Fixture']);
        $this->item(['name' => 'Omega Bread Fixture', 'inventory_category_id' => $breads->id]);
        $this->vendor('Lisanti');

        $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.index', [
                'store_id' => $this->store->id, 'inventory_category_id' => $this->meats->id,
            ]))
            ->assertOk()
            ->assertSee('Zeta Meat Fixture')
            ->assertDontSee('Omega Bread Fixture');
    }

    // ---- History -----------------------------------------------------------

    /** @test */
    public function history_accumulates_in_vendor_prices_rather_than_a_second_table(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');

        $this->price($lisanti, $item, 140.00, 'case', '2026-07-01');
        $this->price($lisanti, $item, 145.00, 'case', '2026-08-01');
        $this->price($lisanti, $item, 149.00, 'case', '2026-08-20');

        $rows = VendorPrice::where('inventory_item_id', $item->id)->orderBy('effective_date')->get();
        $this->assertCount(3, $rows);
        $this->assertEquals([140.00, 145.00, 149.00], $rows->pluck('price')->map(fn ($p) => (float) $p)->all());
        $this->assertSame($this->admin->id, $rows->first()->entered_by);

        $this->actingAs($this->admin)->get(route('admin.vendor-prices.history', $item))
            ->assertOk()
            ->assertSee('Lisanti');
    }

    /** @test */
    public function the_current_price_is_the_latest_effective_date(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');

        // Inserted out of order on purpose.
        $this->price($lisanti, $item, 149.00, 'case', '2026-08-20');
        $this->price($lisanti, $item, 140.00, 'case', '2026-07-01');

        $current = app(PriceComparisonService::class)->currentPrices([$item->id]);
        $this->assertEquals(149.00, (float) $current->get($item->id)->get($lisanti->id)->price);
    }

    // ---- Change percentage -------------------------------------------------

    /** @test */
    public function the_change_percentage_compares_against_the_previous_price(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');

        $this->price($lisanti, $item, 100.00, 'case', '2026-07-01');
        $this->price($lisanti, $item, 110.00, 'case', '2026-08-01');

        $service = app(PriceComparisonService::class);
        $rows = $service->comparisonRows(collect([$item]), collect([$lisanti]));

        $this->assertEquals(10.0, $rows[0]['cells'][$lisanti->id]['change_pct']);
    }

    /** @test */
    public function a_price_with_no_prior_quote_has_no_change_percentage(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');
        $this->price($lisanti, $item, 100.00, 'case', '2026-08-01');

        $rows = app(PriceComparisonService::class)->comparisonRows(collect([$item]), collect([$lisanti]));

        $this->assertNull($rows[0]['cells'][$lisanti->id]['change_pct']);
    }

    /** @test */
    public function a_price_drop_reports_a_negative_percentage(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');

        $this->price($lisanti, $item, 200.00, 'case', '2026-07-01');
        $this->price($lisanti, $item, 150.00, 'case', '2026-08-01');

        $rows = app(PriceComparisonService::class)->comparisonRows(collect([$item]), collect([$lisanti]));
        $this->assertEquals(-25.0, $rows[0]['cells'][$lisanti->id]['change_pct']);
    }

    // ---- Cheapest calculation ----------------------------------------------

    /** @test */
    public function the_cheapest_vendor_is_judged_per_base_unit_not_per_quote(): void
    {
        // 640 oz per case. Lisanti quotes $64/case = $0.10/oz.
        // Walmart quotes $0.09/oz directly, which is cheaper despite the bigger
        // headline number on Lisanti's side.
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');
        $walmart = $this->vendor('Walmart', 'Supplies');

        $this->price($lisanti, $item, 64.00, 'case', '2026-08-01');
        $this->price($walmart, $item, 0.09, 'oz', '2026-08-01');

        $rows = app(PriceComparisonService::class)->comparisonRows(collect([$item]), collect([$lisanti, $walmart]));

        $this->assertSame($walmart->id, $rows[0]['cheapest_vendor_id']);
        $this->assertTrue($rows[0]['cells'][$walmart->id]['is_cheapest']);
        $this->assertFalse($rows[0]['cells'][$lisanti->id]['is_cheapest']);
        $this->assertEqualsWithDelta(0.09, $rows[0]['cheapest_per_base'], 0.0001);
    }

    /** @test */
    public function a_quote_in_an_unconvertible_unit_is_left_out_of_the_cheapest_calculation(): void
    {
        $item = $this->item(); // base oz, purchase case
        $lisanti = $this->vendor('Lisanti');
        $depot = $this->vendor('Restaurant Depot');

        $this->price($lisanti, $item, 64.00, 'case', '2026-08-01');
        // "pallet" converts to neither the base nor the purchase unit.
        $this->price($depot, $item, 1.00, 'pallet', '2026-08-01');

        $rows = app(PriceComparisonService::class)->comparisonRows(collect([$item]), collect([$lisanti, $depot]));

        $this->assertSame($lisanti->id, $rows[0]['cheapest_vendor_id']);
        $this->assertNull($rows[0]['cells'][$depot->id]['per_base']);
        // The quote still shows, it just cannot win.
        $this->assertEquals(1.00, $rows[0]['cells'][$depot->id]['price']);
    }

    /** @test */
    public function an_item_with_no_quotes_at_all_is_marked_rather_than_given_a_cheapest(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');

        $rows = app(PriceComparisonService::class)->comparisonRows(collect([$item]), collect([$lisanti]));

        $this->assertTrue($rows[0]['has_no_price']);
        $this->assertNull($rows[0]['cheapest_vendor_id']);
        $this->assertSame(0, $rows[0]['priced_count']);
    }

    // ---- Comparison page ---------------------------------------------------

    /** @test */
    public function the_comparison_page_renders_with_the_cheapest_flag_and_movement(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');
        $depot = $this->vendor('Restaurant Depot');

        $this->price($lisanti, $item, 100.00, 'case', '2026-07-01');
        $this->price($lisanti, $item, 110.00, 'case', '2026-08-01');
        $this->price($depot, $item, 200.00, 'case', '2026-08-01');

        $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.compare', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('Price Comparison')
            ->assertSee('Ribeye Steak')
            ->assertSee('cheapest')
            ->assertSee('10.0%');

        $this->assertStringContainsString('/pricing/compare', route('admin.vendor-prices.compare'));
    }

    /** @test */
    public function the_comparison_page_can_be_filtered_by_category(): void
    {
        $breads = InventoryCategory::where('name', 'Breads')->firstOrFail();
        $this->item(['name' => 'Zeta Meat Fixture']);
        $this->item(['name' => 'Omega Bread Fixture', 'inventory_category_id' => $breads->id]);
        $this->vendor('Lisanti');

        $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.compare', [
                'store_id' => $this->store->id, 'inventory_category_id' => $breads->id,
            ]))
            ->assertOk()
            ->assertSee('Omega Bread Fixture')
            ->assertDontSee('Zeta Meat Fixture');
    }

    /** @test */
    public function the_comparison_columns_are_sortable(): void
    {
        $this->vendor('Lisanti');
        $this->item(['name' => 'Alpha Item']);
        $this->item(['name' => 'Zulu Item']);

        $ascending = $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.compare', ['store_id' => $this->store->id, 'sort' => 'name', 'direction' => 'asc']))
            ->getContent();
        $this->assertLessThan(strpos($ascending, 'Zulu Item'), strpos($ascending, 'Alpha Item'));

        $descending = $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.compare', ['store_id' => $this->store->id, 'sort' => 'name', 'direction' => 'desc']))
            ->getContent();
        $this->assertLessThan(strpos($descending, 'Alpha Item'), strpos($descending, 'Zulu Item'));
    }

    /** @test */
    public function sorting_by_cheapest_puts_unpriced_items_last_either_way(): void
    {
        $priced = $this->item(['name' => 'Alpha Priced']);
        $this->item(['name' => 'Beta Unpriced']);
        $lisanti = $this->vendor('Lisanti');
        $this->price($lisanti, $priced, 64.00, 'case', '2026-08-01');

        foreach (['asc', 'desc'] as $direction) {
            $html = $this->actingAs($this->admin)
                ->get(route('admin.vendor-prices.compare', [
                    'store_id' => $this->store->id, 'sort' => 'cheapest', 'direction' => $direction,
                ]))
                ->getContent();

            $this->assertLessThan(
                strpos($html, 'Beta Unpriced'),
                strpos($html, 'Alpha Priced'),
                "Unpriced items should sort last with direction={$direction}."
            );
        }
    }

    // ---- CSV export --------------------------------------------------------

    /** @test */
    public function the_comparison_exports_to_csv(): void
    {
        $item = $this->item();
        $lisanti = $this->vendor('Lisanti');
        $walmart = $this->vendor('Walmart', 'Supplies');

        $this->price($lisanti, $item, 100.00, 'case', '2026-07-01');
        $this->price($lisanti, $item, 110.00, 'case', '2026-08-01');
        $this->price($walmart, $item, 0.09, 'oz', '2026-08-01');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.compare.export', ['store_id' => $this->store->id]))
            ->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Lisanti price', $csv);
        $this->assertStringContainsString('Lisanti change %', $csv);
        $this->assertStringContainsString('Cheapest vendor', $csv);
        $this->assertStringContainsString('Ribeye Steak', $csv);
        $this->assertStringContainsString('110.00', $csv);
        $this->assertStringContainsString('Walmart', $csv);  // cheapest per base unit
        $this->assertStringContainsString('10', $csv);       // change %
    }

    /** @test */
    public function the_csv_export_respects_the_category_filter(): void
    {
        $breads = InventoryCategory::where('name', 'Breads')->firstOrFail();
        $this->item(['name' => 'Zeta Meat Fixture']);
        $this->item(['name' => 'Omega Bread Fixture', 'inventory_category_id' => $breads->id]);
        $this->vendor('Lisanti');

        $csv = $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.compare.export', [
                'store_id' => $this->store->id, 'inventory_category_id' => $breads->id,
            ]))
            ->streamedContent();

        $this->assertStringContainsString('Omega Bread Fixture', $csv);
        $this->assertStringNotContainsString('Zeta Meat Fixture', $csv);
    }

    // ---- Access ------------------------------------------------------------

    /** @test */
    public function the_pricing_pages_are_closed_to_employees(): void
    {
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $this->store->id]);

        $this->actingAs($employee)->get(route('admin.vendor-prices.index'))->assertStatus(403);
        $this->actingAs($employee)->get(route('admin.vendor-prices.compare'))->assertStatus(403);
        $this->actingAs($employee)->get(route('admin.vendor-prices.compare.export'))->assertStatus(403);
    }

    /** @test */
    public function the_comparison_only_covers_the_selected_store(): void
    {
        $otherStore = Store::factory()->create(['created_by' => $this->admin->id]);
        $this->item(['name' => 'Zeta Mine Fixture']);
        InventoryItem::factory()->create([
            'store_id' => $otherStore->id, 'inventory_category_id' => $this->meats->id,
            'name' => 'Omega Theirs Fixture',
        ]);
        $this->vendor('Lisanti');

        $this->actingAs($this->admin)
            ->get(route('admin.vendor-prices.compare', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('Zeta Mine Fixture')
            ->assertDontSee('Omega Theirs Fixture');
    }

    // ---- Seeded prices -----------------------------------------------------

    /** @test */
    public function the_seeders_give_every_sample_mapping_a_current_and_a_prior_price(): void
    {
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->seed(\Database\Seeders\InventoryItemsSeeder::class);
        $this->seed(\Database\Seeders\VendorPricesSeeder::class);

        $oil = InventoryItem::where('store_id', $this->store->id)->where('name', 'Frying Oil')->firstOrFail();

        foreach ($oil->vendors as $vendor) {
            $this->assertSame(
                2,
                VendorPrice::where('inventory_item_id', $oil->id)->where('vendor_id', $vendor->id)->count(),
                "Frying Oil / {$vendor->vendor_name} should have a current and a prior quote."
            );
        }

        // Oil was seeded as an 11% rise, so the comparison shows it going up.
        $rows = app(PriceComparisonService::class)->comparisonRows(collect([$oil]), $oil->vendors);
        $sams = $oil->vendors->firstWhere('vendor_name', "Sam's Club");
        $this->assertEqualsWithDelta(11.0, $rows[0]['cells'][$sams->id]['change_pct'], 0.2);
        $this->assertSame($sams->id, $rows[0]['cheapest_vendor_id']);
    }

    /** @test */
    public function re_running_the_price_seeder_does_not_stack_extra_history(): void
    {
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->seed(\Database\Seeders\InventoryItemsSeeder::class);
        $this->seed(\Database\Seeders\VendorPricesSeeder::class);
        $this->seed(\Database\Seeders\VendorPricesSeeder::class);

        $oil = InventoryItem::where('store_id', $this->store->id)->where('name', 'Frying Oil')->firstOrFail();
        $this->assertSame(6, VendorPrice::where('inventory_item_id', $oil->id)->count()); // 3 vendors x 2 quotes
    }
}
