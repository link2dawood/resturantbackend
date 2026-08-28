<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPrice;
use App\Services\Inventory\ClientInventoryImporter;
use App\Services\Inventory\ClientInventoryImporterOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the exact spreadsheet shape requested from the client:
 *   Item Name | Category | Unit | Portions per Unit | Portion Size | Which Vendor(s)
 * with a comma-separated vendor column, plus the vendor-contact and store-list tabs.
 */
class ClientExcelImportTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private ClientInventoryImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Round Rock']);
        $this->importer = app(ClientInventoryImporter::class);

        foreach (['Lisanti', 'Restaurant Depot', "Sam's Club", 'Coca-Cola'] as $name) {
            Vendor::factory()->create(['vendor_name' => $name, 'vendor_type' => 'Food']);
        }
    }

    /** Writes the requested format as an HTML table, which the importer reads. */
    private function sheet(array $rows): string
    {
        $html = '<table>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>'.htmlspecialchars((string) $cell).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        $path = tempnam(sys_get_temp_dir(), 'sheet').'.html';
        file_put_contents($path, $html);

        return $path;
    }

    private function itemSheet(array $dataRows): string
    {
        return $this->sheet(array_merge(
            [['Item Name', 'Category', 'Unit', 'Portions per Unit', 'Portion Size', 'Portion Unit', 'Which Vendor(s)', 'Cost']],
            $dataRows
        ));
    }

    private function import(string $path): array
    {
        $plan = $this->importer->plan($path, $this->store);

        return $this->importer->commit($plan, new ClientInventoryImporterOptions);
    }

    // ---- The comma-separated vendor column ---------------------------------

    /** @test */
    public function a_comma_separated_vendor_column_maps_to_several_vendors(): void
    {
        $path = $this->itemSheet([
            ['Frying Oil', 'Canned Goods & Misc', 'jug', 35, '', '', "Lisanti, Sam's Club, Restaurant Depot", 42.00],
        ]);

        $this->import($path);

        $oil = InventoryItem::where('name', 'Frying Oil')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['Lisanti', "Sam's Club", 'Restaurant Depot'],
            $oil->vendors()->pluck('vendor_name')->all()
        );
    }

    /** @test */
    public function the_first_vendor_listed_becomes_the_preferred_one(): void
    {
        $path = $this->itemSheet([
            ['Steak', 'Meats', 'box', 53, 3, 'oz', 'Restaurant Depot, Lisanti', 145.00],
        ]);

        $this->import($path);

        $steak = InventoryItem::where('name', 'Steak')->firstOrFail();

        $this->assertSame('Restaurant Depot', $steak->preferredVendor->vendor_name);
        $this->assertSame(1, $steak->vendors()->wherePivot('is_preferred_vendor', true)->count());
    }

    /** @test */
    public function it_does_not_create_a_vendor_named_after_the_whole_list(): void
    {
        // The bug this whole change exists to prevent.
        $path = $this->itemSheet([
            ['Frying Oil', 'Canned Goods & Misc', 'jug', 35, '', '', "Lisanti, Sam's Club", 42.00],
        ]);

        $this->import($path);

        $this->assertDatabaseMissing('vendors', ['vendor_name' => "Lisanti, Sam's Club"]);
        $this->assertSame(4, Vendor::count(), 'No new vendor should have been invented.');
    }

    /** @test */
    public function semicolons_slashes_and_the_word_and_also_separate_vendors(): void
    {
        $path = $this->itemSheet([
            ['Item A', 'Meats', 'box', 1, '', '', 'Lisanti; Restaurant Depot', ''],
            ['Item B', 'Meats', 'box', 1, '', '', 'Lisanti / Restaurant Depot', ''],
            ['Item C', 'Meats', 'box', 1, '', '', 'Lisanti and Restaurant Depot', ''],
        ]);

        $this->import($path);

        foreach (['Item A', 'Item B', 'Item C'] as $name) {
            $this->assertCount(
                2,
                InventoryItem::where('name', $name)->firstOrFail()->vendors,
                "{$name} should have two vendors."
            );
        }
    }

    /** @test */
    public function an_apostrophe_in_a_vendor_name_is_not_treated_as_a_separator(): void
    {
        $path = $this->itemSheet([
            ['Hamburger Buns', 'Breads', 'case', 1, '', '', "Sam's Club", ''],
        ]);

        $this->import($path);

        $this->assertSame(
            ["Sam's Club"],
            InventoryItem::where('name', 'Hamburger Buns')->firstOrFail()->vendors()->pluck('vendor_name')->all()
        );
    }

    /** @test */
    public function a_retired_vendor_inside_a_list_is_remapped(): void
    {
        $path = $this->itemSheet([
            ['Onions', 'Veggies', 'bag', 50, '', '', 'Nogales Produce, Lisanti', 28.00],
        ]);

        $this->import($path);

        $vendors = InventoryItem::where('name', 'Onions')->firstOrFail()->vendors()->pluck('vendor_name')->all();

        $this->assertContains('Restaurant Depot', $vendors);
        $this->assertContains('Lisanti', $vendors);
        $this->assertNotContains('Nogales Produce', $vendors);
    }

    /** @test */
    public function a_price_is_recorded_against_every_vendor_on_the_row(): void
    {
        $path = $this->itemSheet([
            ['Frying Oil', 'Canned Goods & Misc', 'jug', 35, '', '', 'Lisanti, Restaurant Depot', 42.00],
        ]);

        $this->import($path);

        $oil = InventoryItem::where('name', 'Frying Oil')->firstOrFail();

        $this->assertSame(2, VendorPrice::where('inventory_item_id', $oil->id)->count());
        foreach ($oil->vendors as $vendor) {
            $this->assertEqualsWithDelta(42.00, (float) $vendor->pivot->current_price, 0.001);
        }
    }

    // ---- Portion Size ------------------------------------------------------

    /** @test */
    public function the_portion_size_column_is_imported(): void
    {
        $path = $this->itemSheet([
            ['Steak', 'Meats', 'box', 53, 3, 'oz', 'Lisanti', 145.00],
        ]);

        $this->import($path);

        $steak = InventoryItem::where('name', 'Steak')->firstOrFail();

        $this->assertEqualsWithDelta(53, (float) $steak->units_per_purchase, 0.001);
        $this->assertEqualsWithDelta(3, (float) $steak->portion_size, 0.001);
        $this->assertSame('oz', $steak->portion_unit);
        // The portion unit is what the item is counted in.
        $this->assertSame('oz', $steak->base_unit);
        $this->assertEqualsWithDelta(159.0, $steak->portion_total, 0.001);
    }

    /** @test */
    public function an_item_with_no_portion_size_counts_in_pieces(): void
    {
        $path = $this->itemSheet([
            ['8 inch Bread', 'Breads', 'case', 60, '', '', 'Lisanti', 32.00],
        ]);

        $this->import($path);

        $bread = InventoryItem::where('name', '8 inch Bread')->firstOrFail();

        $this->assertNull($bread->portion_size);
        $this->assertSame('each', $bread->base_unit);
        $this->assertEqualsWithDelta(60, (float) $bread->units_per_purchase, 0.001);
    }

    /** @test */
    public function a_portion_size_with_no_unit_is_reported_and_ignored(): void
    {
        $path = $this->itemSheet([
            ['Steak', 'Meats', 'box', 53, 3, '', 'Lisanti', 145.00],
        ]);

        $plan = $this->importer->plan($path, $this->store);

        $this->assertStringContainsString('Portion size has no unit', implode(' ', $plan['errors']));

        $this->importer->commit($plan, new ClientInventoryImporterOptions);
        $this->assertNull(InventoryItem::where('name', 'Steak')->firstOrFail()->portion_size);
    }

    // ---- Extra tabs --------------------------------------------------------

    /** @test */
    public function the_vendor_contact_tab_fills_in_vendor_details(): void
    {
        $path = $this->sheet([
            ['Vendor Name', 'Contact Person', 'Phone', 'Email', 'Website'],
            ['Lisanti', 'Marco Rossi', '215-555-0142', 'orders@lisanti.test', 'https://lisanti.test'],
            ['New Supplier Co', 'Ada Vance', '512-555-0180', 'ada@newsupplier.test', 'https://newsupplier.test'],
        ]);

        $rows = $this->importer->plan($path, $this->store);
        $this->assertNotEmpty($rows['vendor_contacts'] ?? []);
    }

    /** @test */
    public function the_store_list_tab_matches_by_name_and_reports_the_rest(): void
    {
        $path = $this->sheet([
            ['Store Name', 'Address', 'Manager Name'],
            ['Round Rock', '1200 Sam Bass Rd', 'Dana Reed'],
            ['Somewhere Else', '1 Nowhere St', 'Nobody'],
        ]);

        $plan = $this->importer->plan($path, $this->store);

        $this->assertContains('Round Rock', $plan['stores_matched']);
        $this->assertContains('Somewhere Else', $plan['stores_unmatched']);
    }

    /** @test */
    public function an_unmatched_store_is_never_created_from_a_spreadsheet(): void
    {
        $before = Store::count();

        $path = $this->sheet([
            ['Store Name', 'Address', 'Manager Name'],
            ['Brand New Store', '9 New Rd', 'Someone'],
        ]);

        $plan = $this->importer->plan($path, $this->store);
        $this->importer->commit($plan, new ClientInventoryImporterOptions);

        $this->assertSame($before, Store::count(), 'Stores need a creator and tax rates; never invent one.');
    }

    // ---- Regression: the old single-vendor format still works ---------------

    /** @test */
    public function the_clients_existing_single_supplier_sheet_still_imports(): void
    {
        $path = $this->sheet([
            ['Inventory ID', 'Name', 'Supplier', 'Category', 'Pack Size', 'VendorID #', 'Cost'],
            [1, 'Coke', 'Coke', 'Beverages', 24, 'CC-24', 18.00],
        ]);

        $this->import($path);

        $coke = InventoryItem::where('name', 'Coke')->firstOrFail();

        $this->assertSame('Coca-Cola', $coke->preferredVendor->vendor_name);
        $this->assertEqualsWithDelta(24, (float) $coke->units_per_purchase, 0.001);
    }

    /** @test */
    public function the_plan_counts_multi_vendor_rows(): void
    {
        $path = $this->itemSheet([
            ['Frying Oil', 'Canned Goods & Misc', 'jug', 35, '', '', 'Lisanti, Restaurant Depot', 42.00],
            ['Steak', 'Meats', 'box', 53, 3, 'oz', 'Lisanti', 145.00],
        ]);

        $plan = $this->importer->plan($path, $this->store);

        $this->assertSame(1, $plan['multi_vendor_items']);
        $this->assertSame(3, $plan['mappings']);
    }

    /** @test */
    public function a_dry_run_still_writes_nothing(): void
    {
        $path = $this->itemSheet([
            ['Frying Oil', 'Canned Goods & Misc', 'jug', 35, '', '', 'Lisanti, Restaurant Depot', 42.00],
        ]);

        $this->importer->plan($path, $this->store);

        $this->assertSame(0, InventoryItem::count());
        $this->assertSame(0, VendorPrice::count());
    }
}
