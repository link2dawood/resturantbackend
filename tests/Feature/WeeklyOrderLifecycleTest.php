<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WeeklyOrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private Vendor $lisanti;

    private Vendor $depot;

    private InventoryCategory $meats;

    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-03')->startOfWeek(Carbon::MONDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // These suites are not about notifications; the real sending behaviour
        // is covered by NotificationSystemTest.
        Notification::fake();

        $this->travelTo($this->monday()->copy()->setTime(9, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id, 'store_info' => 'Round Rock']);
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
        $this->lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti', 'vendor_type' => 'Food']);
        $this->depot = Vendor::factory()->create(['vendor_name' => 'Restaurant Depot', 'vendor_type' => 'Food']);
    }

    private function item(string $name = 'Steak', ?Vendor $vendor = null): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => $name,
            'base_unit' => 'portion',
            'purchase_unit' => 'box',
            'units_per_purchase' => 53,
            'preferred_vendor_id' => ($vendor ?? $this->lisanti)->id,
            'is_active' => true,
        ]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'store_id' => $this->store->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
            'created_by' => $this->manager->id,
        ], $overrides));
    }

    private function line(Order $order, InventoryItem $item, array $overrides = []): OrderItem
    {
        return $order->items()->create(array_merge([
            'inventory_item_id' => $item->id,
            'quantity' => 5,
            'unit' => 'box',
        ], $overrides));
    }

    // ---- Creation from suggestions ----------------------------------------

    /** @test */
    public function an_order_is_created_per_vendor_from_the_suggestions_screen(): void
    {
        $steak = $this->item('Steak', $this->lisanti);
        $bread = $this->item('Bread', $this->depot);

        foreach ([$steak, $bread] as $item) {
            StoreInventoryTarget::create([
                'store_id' => $this->store->id, 'inventory_item_id' => $item->id, 'target_stock_level' => 10,
            ]);
            InventoryStock::create([
                'inventory_item_id' => $item->id, 'store_id' => $this->store->id,
                'week_start_date' => $this->monday()->toDateString(),
                'starting_stock' => 0, 'status' => 'draft', 'counted_at' => now(),
            ]);
        }

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$steak->id => 10, $bread->id => 10],
            'vendors' => [$steak->id => $this->lisanti->id, $bread->id => $this->depot->id],
        ])->assertRedirect();

        $this->assertSame(2, Order::count());
        $this->assertSame(Order::STATUS_DRAFT, Order::first()->status);
    }

    // ---- Totals ------------------------------------------------------------

    /** @test */
    public function the_line_total_is_kept_in_step_with_quantity_and_price(): void
    {
        $order = $this->order();
        $line = $this->line($order, $this->item(), ['quantity' => 4, 'unit_price' => 145.00]);

        $this->assertEqualsWithDelta(580.00, (float) $line->fresh()->line_total, 0.001);

        $line->update(['quantity' => 5]);
        $this->assertEqualsWithDelta(725.00, (float) $line->fresh()->line_total, 0.001);

        // No price means no line total, rather than a misleading zero.
        $line->update(['unit_price' => null]);
        $this->assertNull($line->fresh()->line_total);
    }

    /** @test */
    public function the_order_total_sums_its_lines(): void
    {
        $order = $this->order();
        $this->line($order, $this->item('Steak'), ['quantity' => 2, 'unit_price' => 100.00]);
        $this->line($order, $this->item('Bread'), ['quantity' => 3, 'unit_price' => 32.50]);

        $this->assertEqualsWithDelta(297.50, $order->fresh()->load('items')->total, 0.001);
    }

    // ---- Status lifecycle --------------------------------------------------

    /** @test */
    public function a_draft_can_be_marked_placed_and_then_received(): void
    {
        $order = $this->order();
        $this->line($order, $this->item());

        $this->actingAs($this->manager)->patch(route('admin.orders.placed', $order))->assertRedirect();
        $order->refresh();
        $this->assertSame(Order::STATUS_PLACED, $order->status);
        $this->assertNotNull($order->placed_at);

        $line = $order->items()->firstOrFail();
        $this->actingAs($this->manager)->patch(route('admin.orders.received', $order), [
            'received' => [$line->id => $line->quantity],
        ])->assertRedirect();
        $order->refresh();
        $this->assertSame(Order::STATUS_RECEIVED, $order->status);
        $this->assertNotNull($order->received_at);
    }

    /** @test */
    public function a_draft_cannot_jump_straight_to_received(): void
    {
        $order = $this->order();

        $this->actingAs($this->manager)->patch(route('admin.orders.received', $order), ['received' => []])
            ->assertSessionHas('error');

        $this->assertSame(Order::STATUS_DRAFT, $order->fresh()->status);
    }

    /** @test */
    public function a_received_order_is_terminal(): void
    {
        $order = $this->order(['status' => Order::STATUS_RECEIVED]);

        foreach (['placed', 'received', 'cancel'] as $route) {
            $this->actingAs($this->manager)
                ->patch(route("admin.orders.{$route}", $order))
                ->assertSessionHas('error');
        }

        $this->assertSame(Order::STATUS_RECEIVED, $order->fresh()->status);
    }

    /** @test */
    public function an_order_can_be_cancelled_from_draft_or_placed(): void
    {
        $draft = $this->order();
        $this->actingAs($this->manager)->patch(route('admin.orders.cancel', $draft))->assertRedirect();
        $this->assertSame(Order::STATUS_CANCELLED, $draft->fresh()->status);

        $placed = $this->order(['vendor_id' => $this->depot->id, 'status' => Order::STATUS_PLACED]);
        $this->actingAs($this->manager)->patch(route('admin.orders.cancel', $placed))->assertRedirect();
        $this->assertSame(Order::STATUS_CANCELLED, $placed->fresh()->status);
    }

    // ---- Locking -----------------------------------------------------------

    /** @test */
    public function a_placed_order_cannot_have_its_lines_edited(): void
    {
        $order = $this->order(['status' => Order::STATUS_PLACED]);
        $line = $this->line($order, $this->item(), ['quantity' => 5]);

        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $order), [
            'quantity' => [$line->id => 99],
        ])->assertSessionHas('error');

        $this->assertEqualsWithDelta(5, (float) $line->fresh()->quantity, 1e-4);
    }

    /** @test */
    public function a_placed_order_cannot_be_deleted_only_cancelled(): void
    {
        $order = $this->order(['status' => Order::STATUS_PLACED]);

        $this->actingAs($this->manager)->delete(route('admin.orders.destroy', $order))
            ->assertSessionHas('error');

        $this->assertNotNull(Order::find($order->id));
    }

    /** @test */
    public function the_detail_page_hides_the_edit_controls_once_placed(): void
    {
        $order = $this->order(['status' => Order::STATUS_PLACED]);
        $this->line($order, $this->item());

        $this->actingAs($this->manager)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('lines are locked')
            ->assertDontSee('Save changes');
    }

    // ---- Editing a draft ---------------------------------------------------

    /** @test */
    public function a_draft_line_can_have_its_quantity_price_and_note_changed(): void
    {
        $order = $this->order();
        $line = $this->line($order, $this->item(), ['quantity' => 5, 'suggested_quantity' => 5]);

        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $order), [
            'quantity' => [$line->id => 8],
            'unit_price' => [$line->id => 145.00],
            'line_notes' => [$line->id => 'Ask for the fresh pallet'],
            'notes' => 'Deliver before 10am',
        ])->assertRedirect();

        $line->refresh();
        $this->assertEqualsWithDelta(8, (float) $line->quantity, 1e-4);
        $this->assertEqualsWithDelta(1160.00, (float) $line->line_total, 0.001);
        $this->assertSame('Ask for the fresh pallet', $line->notes);
        // Changed away from the suggestion, so it is now an override.
        $this->assertTrue($line->is_manual_override);
        $this->assertSame('Deliver before 10am', $order->fresh()->notes);
    }

    /** @test */
    public function setting_a_quantity_to_zero_removes_the_line(): void
    {
        $order = $this->order();
        $keep = $this->line($order, $this->item('Steak'));
        $drop = $this->line($order, $this->item('Bread'));

        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $order), [
            'quantity' => [$keep->id => 5, $drop->id => 0],
        ])->assertRedirect();

        $this->assertNotNull(OrderItem::find($keep->id));
        $this->assertNull(OrderItem::find($drop->id));
    }

    /** @test */
    public function emptying_an_order_removes_the_order_itself(): void
    {
        $order = $this->order();
        $line = $this->line($order, $this->item());

        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $order), [
            'quantity' => [$line->id => 0],
        ])->assertRedirect(route('admin.orders.index', ['store_id' => $this->store->id]));

        $this->assertNull(Order::find($order->id));
    }

    // ---- Reassigning a line to another vendor ------------------------------

    /** @test */
    public function a_line_can_be_moved_to_another_vendors_order(): void
    {
        $order = $this->order();
        $stay = $this->line($order, $this->item('Steak'));
        $move = $this->line($order, $this->item('Bread'));

        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $order), [
            'quantity' => [$stay->id => 5, $move->id => 5],
            'move_to_vendor' => [$move->id => $this->depot->id],
        ])->assertRedirect();

        // A new draft order was opened for the receiving vendor.
        $depotOrder = Order::where('vendor_id', $this->depot->id)->firstOrFail();
        $this->assertSame($this->monday()->toDateString(), $depotOrder->week_start_date->toDateString());
        $this->assertSame(1, (int) $depotOrder->order_sequence);
        $this->assertSame($depotOrder->id, $move->fresh()->order_id);
        $this->assertSame($order->id, $stay->fresh()->order_id);
    }

    /** @test */
    public function moving_a_line_reuses_an_existing_draft_for_that_vendor(): void
    {
        $order = $this->order();
        $stay = $this->line($order, $this->item('Steak'));
        $move = $this->line($order, $this->item('Bread'));

        $existingDepotOrder = $this->order(['vendor_id' => $this->depot->id]);
        $this->line($existingDepotOrder, $this->item('Cheese'));

        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $order), [
            'quantity' => [$stay->id => 5, $move->id => 5],
            'move_to_vendor' => [$move->id => $this->depot->id],
        ])->assertRedirect();

        $this->assertSame(2, Order::where('store_id', $this->store->id)->count());
        $this->assertSame($existingDepotOrder->id, $move->fresh()->order_id);
        $this->assertSame(2, $existingDepotOrder->fresh()->items()->count());
    }

    // ---- Order 2 -----------------------------------------------------------

    /** @test */
    public function order_one_can_be_duplicated_into_order_two(): void
    {
        $order = $this->order();
        $this->line($order, $this->item('Steak'), ['quantity' => 9, 'unit_price' => 145.00, 'suggested_quantity' => 9]);
        $this->line($order, $this->item('Bread'), ['quantity' => 4, 'unit_price' => 32.00]);

        $this->actingAs($this->manager)->post(route('admin.orders.duplicate', $order))->assertRedirect();

        $copy = Order::where('order_sequence', 2)->firstOrFail();
        $this->assertSame($this->lisanti->id, $copy->vendor_id);
        $this->assertSame($this->monday()->toDateString(), $copy->week_start_date->toDateString());
        $this->assertSame(Order::STATUS_DRAFT, $copy->status);
        $this->assertSame(2, $copy->items()->count());

        $copiedLine = $copy->items()->whereHas('inventoryItem', fn ($q) => $q->where('name', 'Steak'))->firstOrFail();
        $this->assertEqualsWithDelta(9, (float) $copiedLine->quantity, 1e-4);
        // Order 2 is a fresh decision, so it carries no suggestion from Order 1's count.
        $this->assertNull($copiedLine->suggested_quantity);
        $this->assertFalse($copiedLine->is_manual_override);
    }

    /** @test */
    public function a_placed_order_one_can_still_be_duplicated(): void
    {
        // The common case: Order 1 went in Monday, a busy week needs a top-up.
        $order = $this->order(['status' => Order::STATUS_PLACED]);
        $this->line($order, $this->item());

        $this->actingAs($this->manager)->post(route('admin.orders.duplicate', $order))->assertRedirect();

        $this->assertSame(1, Order::where('order_sequence', 2)->count());
        $this->assertSame(Order::STATUS_PLACED, $order->fresh()->status);
    }

    /** @test */
    public function a_second_order_cannot_itself_be_duplicated(): void
    {
        $order = $this->order(['order_sequence' => 2]);
        $this->line($order, $this->item());

        $this->actingAs($this->manager)->post(route('admin.orders.duplicate', $order))
            ->assertSessionHas('error');

        $this->assertSame(1, Order::count());
    }

    /** @test */
    public function duplicating_twice_does_not_create_a_second_order_two(): void
    {
        $order = $this->order();
        $this->line($order, $this->item());

        $this->actingAs($this->manager)->post(route('admin.orders.duplicate', $order))->assertRedirect();
        $this->actingAs($this->manager)->post(route('admin.orders.duplicate', $order))
            ->assertSessionHas('error');

        $this->assertSame(1, Order::where('order_sequence', 2)->count());
    }

    /** @test */
    public function order_one_and_order_two_coexist_for_the_same_vendor_and_week(): void
    {
        $first = $this->order();
        $this->line($first, $this->item('Steak'));
        $this->actingAs($this->manager)->post(route('admin.orders.duplicate', $first))->assertRedirect();

        $orders = Order::where('store_id', $this->store->id)
            ->forWeek($this->monday()->toDateString())
            ->where('vendor_id', $this->lisanti->id)
            ->orderBy('order_sequence')->get();

        $this->assertSame([1, 2], $orders->pluck('order_sequence')->map(fn ($n) => (int) $n)->all());
    }

    // ---- Index filters -----------------------------------------------------

    /** @test */
    public function the_index_filters_by_week_vendor_and_status(): void
    {
        $thisWeek = $this->order();
        $this->line($thisWeek, $this->item('Zeta This Week'));

        $lastWeek = $this->order([
            'week_start_date' => $this->monday()->copy()->subWeek()->toDateString(),
            'vendor_id' => $this->depot->id,
            'status' => Order::STATUS_RECEIVED,
        ]);
        $this->line($lastWeek, $this->item('Omega Last Week'));

        $url = fn (array $params) => route('admin.orders.index', $params + ['store_id' => $this->store->id]);

        // Assert on the row marker, not the vendor name: every vendor name also
        // appears in the filter dropdown, so assertDontSee would always fail.
        $row = fn (Order $order) => 'data-order-id="'.$order->id.'"';

        // Default week filter shows only this week.
        $this->actingAs($this->manager)->get($url([]))
            ->assertOk()->assertSee($row($thisWeek), false)->assertDontSee($row($lastWeek), false);

        // All weeks shows both.
        $this->actingAs($this->manager)->get($url(['all_weeks' => 1]))
            ->assertOk()->assertSee($row($thisWeek), false)->assertSee($row($lastWeek), false);

        // Status filter.
        $this->actingAs($this->manager)->get($url(['all_weeks' => 1, 'status' => 'received']))
            ->assertOk()->assertSee($row($lastWeek), false)->assertDontSee($row($thisWeek), false);

        // Vendor filter.
        $this->actingAs($this->manager)->get($url(['all_weeks' => 1, 'vendor_id' => $this->depot->id]))
            ->assertOk()->assertSee($row($lastWeek), false)->assertDontSee($row($thisWeek), false);
    }

    /** @test */
    public function the_detail_page_shows_lines_and_the_total(): void
    {
        $order = $this->order();
        $this->line($order, $this->item('Ribeye Steak'), ['quantity' => 4, 'unit_price' => 145.00]);

        $this->actingAs($this->manager)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Ribeye Steak')
            ->assertSee('Lisanti')
            ->assertSee('$580.00')
            ->assertSee('Order total');
    }

    // ---- Scoping -----------------------------------------------------------

    /** @test */
    public function a_manager_cannot_touch_another_stores_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        $foreign = Order::create([
            'store_id' => $otherStore->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.show', $foreign))->assertForbidden();
        $this->actingAs($this->manager)->patch(route('admin.orders.placed', $foreign))->assertForbidden();
        $this->actingAs($this->manager)->post(route('admin.orders.duplicate', $foreign))->assertForbidden();
        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $foreign), ['quantity' => []])->assertForbidden();

        $this->assertSame(Order::STATUS_DRAFT, $foreign->fresh()->status);
    }
}
