<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Inventory\ManagerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private Vendor $lisanti;

    private InventoryCategory $meats;

    private ManagerDashboardService $widgets;

    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-24')->startOfWeek(Carbon::MONDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo($this->monday()->copy()->setTime(9, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Round Rock']);
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id, 'name' => 'Dana Reed']);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
        $this->lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti', 'vendor_type' => 'Food']);
        $this->widgets = app(ManagerDashboardService::class);
    }

    private function item(string $name = 'Steak', array $overrides = []): InventoryItem
    {
        return InventoryItem::factory()->create(array_merge([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => $name,
            'base_unit' => 'portion',
            'purchase_unit' => 'box',
            'units_per_purchase' => 53,
            'min_stock_level' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function countRow(InventoryItem $item, ?float $base, string $status = 'draft'): InventoryStock
    {
        return InventoryStock::create([
            'inventory_item_id' => $item->id,
            'store_id' => $this->store->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => $base ?? 0,
            'status' => $status,
            'counted_by' => $base === null ? null : $this->manager->id,
            'counted_at' => $base === null ? null : now(),
        ]);
    }

    // ---- Widget 1: count status --------------------------------------------

    /** @test */
    public function the_count_widget_reports_how_many_items_are_counted(): void
    {
        $a = $this->item('Steak');
        $b = $this->item('Chicken');
        $this->item('Bread');

        $this->countRow($a, 100);
        $this->countRow($b, 50);
        $this->countRow($this->item('Pita'), null); // opened but not counted

        $status = $this->widgets->countStatus($this->store, $this->monday());

        $this->assertSame(2, $status['counted']);
        $this->assertSame(3, $status['total']);
        $this->assertTrue($status['started']);
        $this->assertFalse($status['submitted']);
        $this->assertSame(67, $status['percent']);
    }

    /** @test */
    public function the_count_widget_says_not_started_before_anything_is_counted(): void
    {
        $this->item('Steak');
        $this->item('Chicken');

        $status = $this->widgets->countStatus($this->store, $this->monday());

        $this->assertFalse($status['started']);
        $this->assertSame(0, $status['counted']);
        // No stock rows exist yet, so the denominator falls back to the item count.
        $this->assertSame(2, $status['total']);

        $this->actingAs($this->manager)->get(route('admin.inventory-dashboard.index'))
            ->assertOk()
            ->assertSee('Not started')
            ->assertSee('click to begin this week');
    }

    /** @test */
    public function the_count_widget_reports_a_submitted_week(): void
    {
        $item = $this->item();
        $this->countRow($item, 100, InventoryStock::STATUS_SUBMITTED);

        $status = $this->widgets->countStatus($this->store, $this->monday());

        $this->assertTrue($status['submitted']);
        $this->assertSame(100, $status['percent']);
    }

    // ---- Widget 2: pending orders ------------------------------------------

    /** @test */
    public function the_pending_widget_counts_drafts_and_placed_but_not_received(): void
    {
        $make = fn (string $status, int $sequence) => Order::create([
            'store_id' => $this->store->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(),
            'order_sequence' => $sequence,
            'status' => $status,
        ]);

        $make(Order::STATUS_DRAFT, 1);
        $make(Order::STATUS_PLACED, 2);

        // Neither of these still needs attention.
        Order::create([
            'store_id' => $this->store->id, 'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'order_sequence' => 1, 'status' => Order::STATUS_RECEIVED,
        ]);
        Order::create([
            'store_id' => $this->store->id, 'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'order_sequence' => 2, 'status' => Order::STATUS_CANCELLED,
        ]);

        $pending = $this->widgets->pendingOrders($this->store, $this->monday());

        $this->assertCount(2, $pending);
        $this->assertEqualsCanonicalizing(
            [Order::STATUS_DRAFT, Order::STATUS_PLACED],
            $pending->pluck('status')->all()
        );
    }

    /** @test */
    public function the_pending_widget_includes_stale_orders_from_earlier_weeks(): void
    {
        // A placed order from three weeks ago that never arrived is exactly the
        // thing a manager needs reminding about.
        Order::create([
            'store_id' => $this->store->id, 'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->copy()->subWeeks(3)->toDateString(),
            'order_sequence' => 1, 'status' => Order::STATUS_PLACED, 'placed_at' => now()->subWeeks(3),
        ]);

        $this->assertCount(1, $this->widgets->pendingOrders($this->store, $this->monday()));
    }

    // ---- Widget 3: low stock ------------------------------------------------

    /** @test */
    public function low_stock_uses_the_per_store_target_in_purchase_units(): void
    {
        $steak = $this->item('Steak'); // 53 portions per box
        StoreInventoryTarget::create([
            'store_id' => $this->store->id, 'inventory_item_id' => $steak->id,
            'target_stock_level' => 15, 'min_stock_level' => 4,
        ]);
        // 3 boxes on hand, reorder point is 4 boxes.
        $this->countRow($steak, 53 * 3);

        $low = $this->widgets->lowStock($this->store, $this->monday());

        $this->assertCount(1, $low);
        $this->assertSame('Steak', $low[0]['item']->name);
        $this->assertEqualsWithDelta(3.0, $low[0]['on_hand'], 0.01);
        $this->assertEqualsWithDelta(4.0, $low[0]['threshold'], 0.01);
        $this->assertSame('box', $low[0]['unit']);
        $this->assertFalse($low[0]['is_out']);
    }

    /** @test */
    public function an_item_above_its_reorder_point_is_not_flagged(): void
    {
        $steak = $this->item('Steak');
        StoreInventoryTarget::create([
            'store_id' => $this->store->id, 'inventory_item_id' => $steak->id,
            'target_stock_level' => 15, 'min_stock_level' => 4,
        ]);
        $this->countRow($steak, 53 * 9); // 9 boxes, well above 4

        $this->assertCount(0, $this->widgets->lowStock($this->store, $this->monday()));
    }

    /** @test */
    public function low_stock_falls_back_to_the_items_own_reorder_point_in_base_units(): void
    {
        // No per-store target, so the base-unit column on the item is used.
        $steak = $this->item('Steak', ['min_stock_level' => 200]);
        $this->countRow($steak, 150);

        $low = $this->widgets->lowStock($this->store, $this->monday());

        $this->assertCount(1, $low);
        $this->assertEqualsWithDelta(150.0, $low[0]['on_hand'], 0.01);
        $this->assertEqualsWithDelta(200.0, $low[0]['threshold'], 0.01);
        $this->assertSame('portion', $low[0]['unit']);
    }

    /** @test */
    public function an_item_with_no_reorder_point_anywhere_is_never_flagged(): void
    {
        $steak = $this->item('Steak', ['min_stock_level' => 0]);
        $this->countRow($steak, 0);

        $this->assertCount(0, $this->widgets->lowStock($this->store, $this->monday()));
    }

    /** @test */
    public function an_uncounted_item_is_not_treated_as_low_stock(): void
    {
        // Unknown is not the same as zero; flagging it would bury the real alerts.
        $steak = $this->item('Steak', ['min_stock_level' => 200]);
        $this->countRow($steak, null);

        $this->assertCount(0, $this->widgets->lowStock($this->store, $this->monday()));
    }

    /** @test */
    public function an_item_counted_at_zero_is_flagged_as_out_of_stock(): void
    {
        $steak = $this->item('Steak', ['min_stock_level' => 200]);
        $this->countRow($steak, 0);

        $low = $this->widgets->lowStock($this->store, $this->monday());

        $this->assertCount(1, $low);
        $this->assertTrue($low[0]['is_out']);
    }

    /** @test */
    public function low_stock_is_ordered_by_how_far_short_the_item_is(): void
    {
        $a = $this->item('Slightly Short', ['min_stock_level' => 100]);
        $b = $this->item('Very Short', ['min_stock_level' => 100]);
        $this->countRow($a, 90);
        $this->countRow($b, 10);

        $low = $this->widgets->lowStock($this->store, $this->monday());

        $this->assertSame(['Very Short', 'Slightly Short'], $low->pluck('item.name')->all());
    }

    // ---- Widget 4: recent activity -----------------------------------------

    /** @test */
    public function recent_activity_reports_counts_and_order_events_newest_first(): void
    {
        $item = $this->item();

        $this->travelTo($this->monday()->copy()->setTime(8, 0));
        $this->countRow($item, 100, InventoryStock::STATUS_SUBMITTED);

        $this->travelTo($this->monday()->copy()->setTime(9, 0));
        $order = Order::create([
            'store_id' => $this->store->id, 'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(), 'order_sequence' => 1,
            'status' => Order::STATUS_PLACED, 'placed_at' => now(),
        ]);
        $order->items()->create([
            'inventory_item_id' => $item->id, 'quantity' => 4, 'unit' => 'box', 'unit_price' => 145.00,
        ]);

        $activity = $this->widgets->recentActivity($this->store);

        $this->assertGreaterThanOrEqual(2, $activity->count());
        $this->assertSame('order_placed', $activity[0]['type']);
        $this->assertStringContainsString('Order placed with Lisanti', $activity[0]['description']);
        $this->assertStringContainsString('$580.00', $activity[0]['description']);
        $this->assertSame('count_submitted', $activity[1]['type']);
        $this->assertSame('Dana Reed', $activity[1]['actor']);
    }

    /** @test */
    public function a_whole_weeks_count_is_one_activity_line_not_one_per_item(): void
    {
        // 90 items counted must not push everything else off the feed.
        foreach (range(1, 12) as $n) {
            $this->countRow($this->item("Item {$n}"), 10, InventoryStock::STATUS_SUBMITTED);
        }

        $activity = $this->widgets->recentActivity($this->store);
        $countEvents = $activity->where('type', 'count_submitted');

        $this->assertCount(1, $countEvents);
    }

    /** @test */
    public function recent_activity_is_capped_at_five_entries(): void
    {
        foreach (range(1, 8) as $n) {
            Order::create([
                'store_id' => $this->store->id, 'vendor_id' => $this->lisanti->id,
                'week_start_date' => $this->monday()->copy()->subWeeks($n)->toDateString(),
                'order_sequence' => 1, 'status' => Order::STATUS_PLACED,
                'placed_at' => now()->subDays($n),
            ]);
        }

        $this->assertCount(5, $this->widgets->recentActivity($this->store));
    }

    // ---- The page -----------------------------------------------------------

    /** @test */
    public function the_dashboard_renders_all_four_widgets_and_the_activity_feed(): void
    {
        $steak = $this->item('Ribeye Steak', ['min_stock_level' => 200]);
        $this->countRow($steak, 10);
        Order::create([
            'store_id' => $this->store->id, 'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(), 'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
        ]);

        $this->actingAs($this->manager)->get(route('admin.inventory-dashboard.index'))
            ->assertOk()
            ->assertSee("This week's count")
            ->assertSee('Pending orders')
            ->assertSee('Low stock')
            ->assertSee('Variance alerts')
            ->assertSee('Recent activity')
            ->assertSee('Ribeye Steak')
            ->assertSee('Lisanti');
    }

    /** @test */
    public function each_widget_links_to_its_detail_page(): void
    {
        $this->item();

        $this->actingAs($this->manager)->get(route('admin.inventory-dashboard.index'))
            ->assertOk()
            ->assertSee(route('inventory.weekly-count.index', ['store_id' => $this->store->id]), false)
            ->assertSee(route('admin.orders.index', ['store_id' => $this->store->id]), false)
            ->assertSee(route('inventory.weekly-count.suggestions', ['store_id' => $this->store->id]), false);
    }

    // ---- Scoping ------------------------------------------------------------

    /** @test */
    public function the_widgets_only_cover_the_managers_own_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);

        $foreign = InventoryItem::factory()->create([
            'store_id' => $otherStore->id, 'inventory_category_id' => $this->meats->id,
            'name' => 'Omega Foreign Fixture', 'min_stock_level' => 999,
        ]);
        InventoryStock::create([
            'inventory_item_id' => $foreign->id, 'store_id' => $otherStore->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 0, 'status' => 'draft', 'counted_at' => now(),
        ]);

        $mine = $this->item('Zeta Mine Fixture', ['min_stock_level' => 100]);
        $this->countRow($mine, 10);

        $low = $this->widgets->lowStock($this->store, $this->monday());
        $this->assertSame(['Zeta Mine Fixture'], $low->pluck('item.name')->all());

        $this->actingAs($this->manager)
            ->get(route('admin.inventory-dashboard.index', ['store_id' => $otherStore->id]))
            ->assertOk()
            ->assertSee('Zeta Mine Fixture')
            ->assertDontSee('Omega Foreign Fixture');
    }
}
