<?php

namespace Tests\Feature;

use App\Models\DailyReport;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MenuItem;
use App\Models\MenuItemSold;
use App\Models\RevenueIncomeType;
use App\Models\Store;
use App\Models\User;
use App\Services\Inventory\RecipeService;
use App\Services\Inventory\StockUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StockUpTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $projMonday;
    private Carbon $historyMonday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projMonday = Carbon::parse('2026-08-17')->startOfWeek(Carbon::MONDAY);
        $this->historyMonday = $this->projMonday->copy()->subWeek();
    }

    private function steak(Store $store, array $o = []): InventoryItem
    {
        return InventoryItem::factory()->create(array_merge([
            'store_id' => $store->id, 'base_unit' => 'oz', 'purchase_unit' => 'case', 'units_per_purchase' => 640,
        ], $o));
    }

    /** Seed one history week's usage: qtySold of a menu item whose recipe uses $portion oz of $item. */
    private function seedUsage(Store $store, Carbon $week, InventoryItem $item, float $qtySold, float $portion): void
    {
        $menu = MenuItem::factory()->create(['store_id' => $store->id]);
        app(RecipeService::class)->saveVersion($menu, 'regular', [['inventory_item_id' => $item->id, 'quantity' => $portion, 'unit' => 'oz']]);
        MenuItemSold::factory()->create([
            'store_id' => $store->id, 'week_start_date' => $week->toDateString(),
            'menu_item_id' => $menu->id, 'size_variant' => 'regular', 'quantity_sold' => $qtySold, 'is_matched' => true,
        ]);
    }

    private function seedSales(Store $store, User $creator, Carbon $dateInWeek, float $net): void
    {
        $type = RevenueIncomeType::firstOrCreate(['name' => 'Cash'], ['is_active' => true, 'category' => 'cash']);
        $report = DailyReport::factory()->create([
            'store_id' => $store->id, 'report_date' => $dateInWeek->toDateString(), 'created_by' => $creator->id,
            'coupons_received' => 0, 'adjustments_overrings' => 0, 'adjustments_cash' => 0, 'credit_card_tips' => 0, 'credit_cards' => 0,
        ]);
        $report->revenues()->create(['revenue_income_type_id' => $type->id, 'amount' => $net]);
    }

    private function onHand(Store $store, InventoryItem $item, float $qty): void
    {
        InventoryStock::factory()->create([
            'inventory_item_id' => $item->id, 'store_id' => $store->id,
            'week_start_date' => $this->projMonday->toDateString(), 'starting_stock' => $qty,
        ]);
    }

    /** @test */
    public function it_scales_usage_by_projected_sales_dollars(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $steak = $this->steak($store);

        // History: 900 oz used on $1,000 net sales → 0.9 oz/$.
        $this->seedUsage($store, $this->historyMonday, $steak, 200, 4.5);
        $this->seedSales($store, $owner, $this->historyMonday, 1000);
        $this->onHand($store, $steak, 300);

        // Project $2,000 → 1,800 oz needed, 300 on hand → order 1,500.
        $rows = app(StockUpService::class)->suggest($store->id, $this->projMonday, 2000, 1);
        $row = collect($rows)->firstWhere(fn ($r) => $r['item']->id === $steak->id);

        $this->assertSame('sales', $row['basis']);
        $this->assertEqualsWithDelta(1800, $row['projected_usage'], 0.01);
        $this->assertEqualsWithDelta(1500, $row['suggested_order'], 0.01);
    }

    /** @test */
    public function the_safety_buffer_increases_the_required_quantity(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $steak = $this->steak($store, ['safety_buffer_pct' => 10]);

        $this->seedUsage($store, $this->historyMonday, $steak, 200, 4.5);
        $this->seedSales($store, $owner, $this->historyMonday, 1000);
        $this->onHand($store, $steak, 300);

        $rows = app(StockUpService::class)->suggest($store->id, $this->projMonday, 2000, 1);
        $row = collect($rows)->firstWhere(fn ($r) => $r['item']->id === $steak->id);

        // 1800 × 1.10 = 1980 required; 1980 − 300 = 1680.
        $this->assertEqualsWithDelta(1980, $row['required'], 0.01);
        $this->assertEqualsWithDelta(1680, $row['suggested_order'], 0.01);
    }

    /** @test */
    public function it_falls_back_to_average_usage_without_sales_history(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $steak = $this->steak($store);

        // Usage history but NO daily-report sales dollars.
        $this->seedUsage($store, $this->historyMonday, $steak, 200, 4.5); // 900 oz

        $rows = app(StockUpService::class)->suggest($store->id, $this->projMonday, 5000, 1);
        $row = collect($rows)->firstWhere(fn ($r) => $r['item']->id === $steak->id);

        $this->assertSame('average', $row['basis']);
        $this->assertEqualsWithDelta(900, $row['projected_usage'], 0.01); // dollar projection ignored
    }

    /** @test */
    public function it_flags_items_at_or_below_the_reorder_threshold(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $steak = $this->steak($store, ['reorder_threshold' => 500]);
        $this->onHand($store, $steak, 300); // 300 ≤ 500

        $rows = app(StockUpService::class)->suggest($store->id, $this->projMonday, 0, 1);
        $row = collect($rows)->firstWhere(fn ($r) => $r['item']->id === $steak->id);

        $this->assertTrue($row['reorder_flag']);
    }

    /** @test */
    public function the_worksheet_renders_and_is_gated(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $this->steak($store, ['name' => 'Ribeye Steak']);

        $this->actingAs($owner)->get(route('admin.stock-up.index', ['store_id' => $store->id, 'projected_dollars' => 2000]))
            ->assertOk()->assertSee('Ribeye Steak');

        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $store->id]);
        $this->actingAs($employee)->get(route('admin.stock-up.index'))->assertStatus(403);
    }
}
