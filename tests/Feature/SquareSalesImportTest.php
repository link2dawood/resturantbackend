<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\MenuItemSold;
use App\Models\Store;
use App\Models\User;
use App\Services\Inventory\SquareItemsSoldParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SquareSalesImportTest extends TestCase
{
    use RefreshDatabase;

    private function sampleCsv(): string
    {
        return file_get_contents(base_path('tests/Fixtures/statements/square_items_sold_sample.csv'));
    }

    /** @test */
    public function the_parser_reads_item_variation_and_quantity_and_skips_totals(): void
    {
        $rows = (new SquareItemsSoldParser())->parse(base_path('tests/Fixtures/statements/square_items_sold_sample.csv'));

        $this->assertCount(5, $rows); // the Totals row is skipped
        $steak = collect($rows)->firstWhere(fn ($r) => $r['item'] === 'Standard Steak Sandwich' && $r['variation'] === 'Regular');
        $this->assertEqualsWithDelta(42, $steak['quantity'], 1e-4);
        $mini = collect($rows)->firstWhere(fn ($r) => $r['variation'] === 'Mini');
        $this->assertEqualsWithDelta(18, $mini['quantity'], 1e-4);
    }

    /** @test */
    public function preview_auto_matches_menu_items_and_flags_unmatched(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        MenuItem::factory()->create(['store_id' => $store->id, 'name' => 'Standard Steak Sandwich']);
        MenuItem::factory()->create(['store_id' => $store->id, 'name' => 'Chicken Cheesesteak']);

        $file = UploadedFile::fake()->createWithContent('square.csv', $this->sampleCsv());

        $this->actingAs($admin)->post(route('admin.square-import.preview'), [
            'store_id' => $store->id, 'week_start_date' => '2026-08-03', 'file' => $file,
        ])->assertOk()
            ->assertSee('Loaded Fries')     // present as a row…
            ->assertSee('unmatched');       // …and flagged, since there's no menu item for it
    }

    /** @test */
    public function committing_writes_sold_rows_with_match_flags(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $steak = MenuItem::factory()->create(['store_id' => $store->id, 'name' => 'Standard Steak Sandwich']);

        $this->actingAs($admin)->post(route('admin.square-import.commit'), [
            'store_id' => $store->id, 'week_start_date' => '2026-08-03',
            'square_raw_name' => ['Standard Steak Sandwich', 'Loaded Fries'],
            'quantity' => [42, 15],
            'menu_item_id' => [$steak->id, ''],
            'size_variant' => ['regular', 'regular'],
        ])->assertRedirect();

        $this->assertDatabaseHas('menu_items_sold', [
            'store_id' => $store->id, 'menu_item_id' => $steak->id, 'is_matched' => true,
        ]);
        $this->assertDatabaseHas('menu_items_sold', [
            'store_id' => $store->id, 'square_raw_name' => 'Loaded Fries', 'menu_item_id' => null, 'is_matched' => false,
        ]);
    }

    /** @test */
    public function re_importing_a_week_replaces_the_prior_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);

        $payload = [
            'store_id' => $store->id, 'week_start_date' => '2026-08-03',
            'square_raw_name' => ['Widget'], 'quantity' => [10],
            'menu_item_id' => [''], 'size_variant' => ['regular'],
        ];

        $this->actingAs($admin)->post(route('admin.square-import.commit'), $payload);
        $this->actingAs($admin)->post(route('admin.square-import.commit'), $payload);

        // Two imports for the same week → still one row, not duplicated.
        $this->assertSame(1, MenuItemSold::where('store_id', $store->id)->count());
    }

    /** @test */
    public function preview_aggregates_repeated_item_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);

        $csv = "Item,Variation,Qty\nWidget,Regular,10\nWidget,Regular,15\n";
        $file = UploadedFile::fake()->createWithContent('s.csv', $csv);

        $this->actingAs($admin)->post(route('admin.square-import.preview'), [
            'store_id' => $store->id, 'week_start_date' => '2026-08-03', 'file' => $file,
        ])->assertOk()->assertSee('value="25"', false); // 10 + 15 aggregated
    }

    /** @test */
    public function employees_cannot_reach_the_square_import(): void
    {
        $store = Store::factory()->create();
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $store->id]);

        $this->actingAs($employee)->get(route('admin.square-import.form'))->assertStatus(403);
    }
}
