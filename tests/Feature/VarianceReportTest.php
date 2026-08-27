<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MenuItem;
use App\Models\MenuItemSold;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use App\Models\VarianceReport;
use App\Services\Inventory\RecipeService;
use App\Services\Inventory\VarianceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VarianceReportTest extends TestCase
{
    use RefreshDatabase;

    private string $week = '2026-08-17';

    /** Build the worked-example scenario: 20 oz short → 1.25% → green. */
    private function scenario(): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $steak = InventoryItem::factory()->create([
            'store_id' => $store->id, 'name' => 'Ribeye Steak',
            'base_unit' => 'oz', 'purchase_unit' => 'case', 'units_per_purchase' => 640,
        ]);
        InventoryStock::factory()->create([
            'inventory_item_id' => $steak->id, 'store_id' => $store->id,
            'week_start_date' => $this->week, 'starting_stock' => 320, 'actual_ending_stock' => 680,
        ]);
        $order = Order::factory()->received()->create(['store_id' => $store->id, 'week_start_date' => $this->week]);
        OrderItem::factory()->create(['order_id' => $order->id, 'inventory_item_id' => $steak->id, 'quantity' => 2, 'unit' => 'case']);

        $menu = MenuItem::factory()->create(['store_id' => $store->id, 'name' => 'Standard Steak Sandwich']);
        app(RecipeService::class)->saveVersion($menu, 'regular', [['inventory_item_id' => $steak->id, 'quantity' => 4.5, 'unit' => 'oz']]);
        MenuItemSold::factory()->create([
            'store_id' => $store->id, 'week_start_date' => $this->week,
            'menu_item_id' => $menu->id, 'size_variant' => 'regular', 'quantity_sold' => 200, 'is_matched' => true,
        ]);

        return [$admin, $store, $steak];
    }

    /** @test */
    public function the_report_shows_the_computed_variance_and_tally(): void
    {
        [$admin, $store] = $this->scenario();

        $this->actingAs($admin)->get(route('admin.variance.index', ['store_id' => $store->id, 'week_start_date' => $this->week]))
            ->assertOk()
            ->assertSee('Ribeye Steak')
            ->assertSee('1 acceptable'); // the green tally
    }

    /** @test */
    public function drill_down_lists_the_contributing_menu_items(): void
    {
        [$admin, $store, $steak] = $this->scenario();

        $this->actingAs($admin)->get(route('admin.variance.drill-down', ['inventoryItem' => $steak->id, 'week_start_date' => $this->week]))
            ->assertOk()
            ->assertSee('Standard Steak Sandwich')
            ->assertSee('900'); // 200 × 4.5 usage
    }

    /** @test */
    public function csv_export_downloads_the_report(): void
    {
        [$admin, $store] = $this->scenario();

        $response = $this->actingAs($admin)->get(route('admin.variance.export.csv', ['store_id' => $store->id, 'week_start_date' => $this->week]));
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Ribeye Steak', $response->streamedContent());
    }

    /** @test */
    public function pdf_export_returns_a_pdf(): void
    {
        [$admin, $store] = $this->scenario();

        $this->actingAs($admin)->get(route('admin.variance.export.pdf', ['store_id' => $store->id, 'week_start_date' => $this->week]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function persist_saves_a_snapshot(): void
    {
        [$admin, $store, $steak] = $this->scenario();

        $report = app(VarianceReportService::class)->persist($store->id, $this->week, $admin->id);

        $this->assertDatabaseHas('variance_reports', ['id' => $report->id, 'store_id' => $store->id]);
        $this->assertDatabaseHas('variance_report_lines', [
            'variance_report_id' => $report->id, 'inventory_item_id' => $steak->id, 'severity' => 'green',
        ]);
    }

    /** @test */
    public function managers_can_view_but_employees_cannot(): void
    {
        $store = Store::factory()->create();
        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $store->id]);
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $store->id]);

        $this->actingAs($manager)->get(route('admin.variance.index'))->assertOk();
        $this->actingAs($employee)->get(route('admin.variance.index'))->assertStatus(403);
    }
}
