<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Inventory\OrderTrendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private User $owner;

    private Vendor $lisanti;

    private Vendor $depot;

    private InventoryCategory $meats;

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
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);

        // Order history moved to owner-only: the client treats past ordering as
        // a back-office view, not something the manager needs on a Monday.
        $this->owner = User::factory()->create(['role' => 'owner', 'state' => 'PA']);
        $this->owner->ownedStores()->attach($this->store->id);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
        $this->lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti', 'vendor_type' => 'Food']);
        $this->depot = Vendor::factory()->create(['vendor_name' => 'Restaurant Depot', 'vendor_type' => 'Food']);
    }

    private function item(string $name = 'Steak'): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => $name,
            'base_unit' => 'portion',
            'purchase_unit' => 'box',
            'units_per_purchase' => 53,
        ]);
    }

    /** An order N weeks back, with one line per [item, quantity] pair. */
    private function order(int $weeksAgo, array $overrides = [], array $lines = []): Order
    {
        $order = Order::create(array_merge([
            'store_id' => $this->store->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->copy()->subWeeks($weeksAgo)->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_RECEIVED,
            'created_by' => $this->manager->id,
        ], $overrides));

        foreach ($lines as [$item, $quantity, $price]) {
            $order->items()->create([
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
                'unit' => 'box',
                'unit_price' => $price,
            ]);
        }

        return $order->fresh()->load('items.inventoryItem');
    }

    // ---- The page ----------------------------------------------------------

    /** @test */
    public function the_history_page_lists_past_orders_grouped_by_vendor(): void
    {
        $steak = $this->item();
        $this->order(1, [], [[$steak, 5, 145.00]]);
        $this->order(2, ['vendor_id' => $this->depot->id], [[$steak, 3, 152.50]]);

        $this->actingAs($this->owner)
            ->get(route('admin.orders.history'))
            ->assertOk()
            ->assertSee('Order History')
            ->assertSee('1 order from Lisanti')
            ->assertSee('1 order from Restaurant Depot');
    }

    /** @test */
    public function the_history_route_is_not_swallowed_by_the_order_wildcard(): void
    {
        // /orders/history would otherwise resolve as /orders/{order}.
        $this->actingAs($this->owner)->get('/orders/history')->assertOk()->assertSee('Order History');
    }

    /** @test */
    public function the_default_window_is_the_last_four_weeks(): void
    {
        $steak = $this->item();
        $recent = $this->order(1, [], [[$steak, 5, 145.00]]);
        $old = $this->order(9, [], [[$steak, 5, 145.00]]);

        $response = $this->actingAs($this->owner)->get(route('admin.orders.history'))->assertOk();

        $response->assertSee('data-order-id="'.$recent->id.'"', false);
        $response->assertDontSee('data-order-id="'.$old->id.'"', false);
    }

    /** @test */
    public function line_items_are_rendered_in_an_expandable_row(): void
    {
        $steak = $this->item('Ribeye Steak');
        $order = $this->order(1, [], [[$steak, 5, 145.00]]);

        $this->actingAs($this->owner)->get(route('admin.orders.history'))
            ->assertOk()
            ->assertSee('id="lines-'.$order->id.'"', false)
            ->assertSee('Ribeye Steak')
            ->assertSee('$725.00'); // 5 x 145
    }

    // ---- Filters -----------------------------------------------------------

    /** @test */
    public function the_list_can_be_filtered_by_vendor(): void
    {
        $steak = $this->item();
        $lisantiOrder = $this->order(1, [], [[$steak, 5, 145.00]]);
        $depotOrder = $this->order(1, ['vendor_id' => $this->depot->id], [[$steak, 3, 152.50]]);

        $response = $this->actingAs($this->owner)
            ->get(route('admin.orders.history', ['vendor_ids' => [$this->depot->id]]))
            ->assertOk();

        $response->assertSee('data-order-id="'.$depotOrder->id.'"', false);
        $response->assertDontSee('data-order-id="'.$lisantiOrder->id.'"', false);
    }

    /** @test */
    public function several_vendors_can_be_selected_at_once(): void
    {
        $steak = $this->item();
        $third = Vendor::factory()->create(['vendor_name' => 'Sams Club', 'vendor_type' => 'Food']);

        $a = $this->order(1, [], [[$steak, 5, 145.00]]);
        $b = $this->order(1, ['vendor_id' => $this->depot->id], [[$steak, 3, 150.00]]);
        $c = $this->order(1, ['vendor_id' => $third->id], [[$steak, 2, 149.00]]);

        $response = $this->actingAs($this->owner)
            ->get(route('admin.orders.history', ['vendor_ids' => [$this->lisanti->id, $this->depot->id]]))
            ->assertOk();

        $response->assertSee('data-order-id="'.$a->id.'"', false);
        $response->assertSee('data-order-id="'.$b->id.'"', false);
        $response->assertDontSee('data-order-id="'.$c->id.'"', false);
    }

    /** @test */
    public function the_list_can_be_filtered_by_status(): void
    {
        $steak = $this->item();
        $received = $this->order(1, [], [[$steak, 5, 145.00]]);
        $draft = $this->order(1, ['order_sequence' => 2, 'status' => Order::STATUS_DRAFT], [[$steak, 2, 145.00]]);

        $response = $this->actingAs($this->owner)
            ->get(route('admin.orders.history', ['statuses' => ['draft']]))
            ->assertOk();

        $response->assertSee('data-order-id="'.$draft->id.'"', false);
        $response->assertDontSee('data-order-id="'.$received->id.'"', false);
    }

    /** @test */
    public function the_list_can_be_filtered_by_a_date_range(): void
    {
        $steak = $this->item();
        $inRange = $this->order(6, [], [[$steak, 5, 145.00]]);
        $outOfRange = $this->order(1, [], [[$steak, 5, 145.00]]);

        $response = $this->actingAs($this->owner)->get(route('admin.orders.history', [
            'date_from' => $this->monday()->copy()->subWeeks(8)->toDateString(),
            'date_to' => $this->monday()->copy()->subWeeks(5)->toDateString(),
        ]))->assertOk();

        $response->assertSee('data-order-id="'.$inRange->id.'"', false);
        $response->assertDontSee('data-order-id="'.$outOfRange->id.'"', false);
    }

    /** @test */
    public function a_backwards_date_range_is_swapped_rather_than_returning_nothing(): void
    {
        $steak = $this->item();
        $order = $this->order(6, [], [[$steak, 5, 145.00]]);

        $this->actingAs($this->owner)->get(route('admin.orders.history', [
            'date_from' => $this->monday()->copy()->subWeeks(5)->toDateString(),
            'date_to' => $this->monday()->copy()->subWeeks(8)->toDateString(),
        ]))->assertOk()->assertSee('data-order-id="'.$order->id.'"', false);
    }

    // ---- Trends ------------------------------------------------------------

    /** @test */
    public function the_average_per_week_is_the_total_over_the_weeks_ordered(): void
    {
        $steak = $this->item('Steak');

        // 4, 6 and 5 boxes across three separate weeks = 5 a week.
        $this->order(1, [], [[$steak, 4, 145.00]]);
        $this->order(2, [], [[$steak, 6, 145.00]]);
        $this->order(3, [], [[$steak, 5, 145.00]]);

        $orders = Order::with('items.inventoryItem')->get();
        $trends = app(OrderTrendService::class)->perItem($orders);

        $this->assertCount(1, $trends);
        $this->assertSame('Steak', $trends[0]['item_name']);
        $this->assertEqualsWithDelta(5.0, $trends[0]['average_per_week'], 0.001);
        $this->assertEqualsWithDelta(15.0, $trends[0]['total_quantity'], 0.001);
        $this->assertSame(3, $trends[0]['weeks']);
    }

    /** @test */
    public function two_orders_in_one_week_count_as_one_week(): void
    {
        $steak = $this->item('Steak');

        // 6 boxes in one week, split across Order 1 and Order 2.
        $this->order(1, ['order_sequence' => 1], [[$steak, 4, 145.00]]);
        $this->order(1, ['order_sequence' => 2], [[$steak, 2, 145.00]]);

        $trends = app(OrderTrendService::class)->perItem(Order::with('items.inventoryItem')->get());

        $this->assertSame(1, $trends[0]['weeks']);
        $this->assertEqualsWithDelta(6.0, $trends[0]['average_per_week'], 0.001);
        $this->assertSame(2, $trends[0]['order_count']);
    }

    /** @test */
    public function drafts_and_cancelled_orders_are_left_out_of_the_averages(): void
    {
        $steak = $this->item('Steak');

        $this->order(1, [], [[$steak, 4, 145.00]]);                                    // counted
        $this->order(2, ['status' => Order::STATUS_DRAFT], [[$steak, 99, 145.00]]);    // a plan
        $this->order(3, ['status' => Order::STATUS_CANCELLED], [[$steak, 99, 145.00]]); // never happened

        $trends = app(OrderTrendService::class)->perItem(Order::with('items.inventoryItem')->get());

        $this->assertEqualsWithDelta(4.0, $trends[0]['average_per_week'], 0.001);
        $this->assertSame(1, $trends[0]['weeks']);
    }

    /** @test */
    public function the_summary_counts_weeks_spend_and_excluded_orders(): void
    {
        $steak = $this->item('Steak');
        $this->order(1, [], [[$steak, 4, 100.00]]);                                 // $400
        $this->order(2, [], [[$steak, 6, 100.00]]);                                 // $600
        $this->order(3, ['status' => Order::STATUS_DRAFT], [[$steak, 99, 100.00]]); // excluded

        $summary = app(OrderTrendService::class)->summary(Order::with('items')->get());

        $this->assertSame(2, $summary['order_count']);
        $this->assertSame(2, $summary['week_count']);
        $this->assertEqualsWithDelta(1000.00, $summary['total_spend'], 0.001);
        $this->assertEqualsWithDelta(500.00, $summary['average_weekly_spend'], 0.001);
        $this->assertSame(3, $summary['listed_count']);
        $this->assertSame(1, $summary['excluded_count']);
    }

    /** @test */
    public function the_trend_table_is_rendered_on_the_page(): void
    {
        $steak = $this->item('Ribeye Steak');
        $this->order(1, [], [[$steak, 4, 145.00]]);
        $this->order(2, [], [[$steak, 6, 145.00]]);

        $this->actingAs($this->owner)->get(route('admin.orders.history'))
            ->assertOk()
            ->assertSee('What you order, per week')
            ->assertSee('Ribeye Steak')
            ->assertSee('box/week');
    }

    // ---- Reorder -----------------------------------------------------------

    /** @test */
    public function reordering_copies_the_lines_into_a_new_draft_for_this_week(): void
    {
        $steak = $this->item('Steak');
        $bread = $this->item('Bread');
        $past = $this->order(3, ['notes' => 'Deliver before 10am'], [[$steak, 5, 145.00], [$bread, 2, 32.00]]);

        $this->actingAs($this->owner)
            ->post(route('admin.orders.reorder', $past), ['week' => $this->monday()->toDateString()])
            ->assertRedirect();

        $copy = Order::forWeek($this->monday()->toDateString())->firstOrFail();

        $this->assertSame(Order::STATUS_DRAFT, $copy->status);
        $this->assertSame($this->lisanti->id, $copy->vendor_id);
        $this->assertSame(1, (int) $copy->order_sequence);
        $this->assertSame('Deliver before 10am', $copy->notes);
        $this->assertSame(2, $copy->items()->count());

        $line = $copy->items()->whereHas('inventoryItem', fn ($q) => $q->where('name', 'Steak'))->firstOrFail();
        $this->assertEqualsWithDelta(5, (float) $line->quantity, 1e-4);
        $this->assertEqualsWithDelta(145.00, (float) $line->unit_price, 0.001);
        // Copied from history, so it never had a suggestion to override.
        $this->assertNull($line->suggested_quantity);
        $this->assertFalse($line->is_manual_override);

        // The original is untouched.
        $this->assertSame(Order::STATUS_RECEIVED, $past->fresh()->status);
    }

    /** @test */
    public function reordering_falls_through_to_order_two_when_order_one_is_taken(): void
    {
        $steak = $this->item();
        $past = $this->order(3, [], [[$steak, 5, 145.00]]);
        $this->order(0, ['status' => Order::STATUS_DRAFT], [[$steak, 1, 145.00]]); // this week, Order 1

        $this->actingAs($this->owner)
            ->post(route('admin.orders.reorder', $past), ['week' => $this->monday()->toDateString()])
            ->assertRedirect();

        $copy = Order::forWeek($this->monday()->toDateString())->where('order_sequence', 2)->firstOrFail();
        $this->assertSame(1, $copy->items()->count());
    }

    /** @test */
    public function reordering_is_refused_when_both_orders_for_the_week_exist(): void
    {
        $steak = $this->item();
        $past = $this->order(3, [], [[$steak, 5, 145.00]]);
        $this->order(0, ['order_sequence' => 1], [[$steak, 1, 145.00]]);
        $this->order(0, ['order_sequence' => 2], [[$steak, 1, 145.00]]);

        $this->actingAs($this->owner)
            ->post(route('admin.orders.reorder', $past), ['week' => $this->monday()->toDateString()])
            ->assertSessionHas('error');

        $this->assertSame(2, Order::forWeek($this->monday()->toDateString())->count());
    }

    /** @test */
    public function reordering_into_a_future_week_is_refused(): void
    {
        $steak = $this->item();
        $past = $this->order(3, [], [[$steak, 5, 145.00]]);

        $this->actingAs($this->owner)->post(route('admin.orders.reorder', $past), [
            'week' => $this->monday()->copy()->addWeek()->toDateString(),
        ])->assertSessionHas('error');

        $this->assertSame(0, Order::forWeek($this->monday()->copy()->addWeek()->toDateString())->count());
    }

    /** @test */
    public function an_empty_order_cannot_be_reordered(): void
    {
        $past = $this->order(3);

        $this->actingAs($this->owner)
            ->post(route('admin.orders.reorder', $past), ['week' => $this->monday()->toDateString()])
            ->assertSessionHas('error');

        $this->assertSame(0, Order::forWeek($this->monday()->toDateString())->count());
    }

    /** @test */
    public function a_cancelled_order_can_still_be_reordered(): void
    {
        // Cancelling because the delivery slipped is a reason to send it again.
        $steak = $this->item();
        $past = $this->order(3, ['status' => Order::STATUS_CANCELLED], [[$steak, 5, 145.00]]);

        $this->actingAs($this->owner)
            ->post(route('admin.orders.reorder', $past), ['week' => $this->monday()->toDateString()])
            ->assertRedirect();

        $this->assertSame(1, Order::forWeek($this->monday()->toDateString())->count());
    }

    // ---- Scoping -----------------------------------------------------------

    /** @test */
    public function an_owner_only_sees_their_own_stores_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        $steak = $this->item();
        $mine = $this->order(1, [], [[$steak, 5, 145.00]]);

        $theirs = Order::create([
            'store_id' => $otherStore->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_RECEIVED,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('admin.orders.history', ['store_id' => $otherStore->id]))
            ->assertOk();

        $response->assertSee('data-order-id="'.$mine->id.'"', false);
        $response->assertDontSee('data-order-id="'.$theirs->id.'"', false);
    }

    /** @test */
    public function an_owner_cannot_reorder_another_stores_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        $foreign = Order::create([
            'store_id' => $otherStore->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_RECEIVED,
        ]);

        $this->actingAs($this->owner)
            ->post(route('admin.orders.reorder', $foreign), ['week' => $this->monday()->toDateString()])
            ->assertForbidden();

        $this->assertSame(0, Order::where('store_id', $this->store->id)->count());
    }

    /** @test */
    public function only_owners_and_admins_can_reach_the_history(): void
    {
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $this->store->id]);

        // The manager counts stock; reviewing past orders is the owner's job.
        $this->actingAs($this->manager)->get(route('admin.orders.history'))->assertStatus(403);
        $this->actingAs($employee)->get(route('admin.orders.history'))->assertStatus(403);

        $this->actingAs($this->owner)->get(route('admin.orders.history'))->assertOk();
    }

    /** @test */
    public function a_manager_cannot_reorder(): void
    {
        $steak = $this->item();
        $past = $this->order(3, [], [[$steak, 5, 145.00]]);

        $this->actingAs($this->manager)
            ->post(route('admin.orders.reorder', $past), ['week' => $this->monday()->toDateString()])
            ->assertStatus(403);

        $this->assertSame(0, Order::forWeek($this->monday()->toDateString())->count());
    }
}
