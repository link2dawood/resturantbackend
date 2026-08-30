<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The client: "we had a problem a few weeks ago when a vendor was sending more
 * items than what we ordered." A single "mark received" button cannot catch
 * that. These cover the line-by-line check that can.
 */
class OrderReceivingTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private Vendor $lisanti;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->travelTo(Carbon::parse('2026-08-24')->setTime(9, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id]);
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
        $this->lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti', 'vendor_type' => 'Food']);
    }

    /** An order of $lines: [name, quantity, unitPrice]. */
    private function placedOrder(array $lines): Order
    {
        $order = Order::create([
            'store_id' => $this->store->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_PLACED,
            'placed_at' => now(),
        ]);

        foreach ($lines as [$name, $quantity, $price]) {
            $item = InventoryItem::factory()->create([
                'store_id' => $this->store->id,
                'inventory_category_id' => InventoryCategory::where('name', 'Meats')->value('id'),
                'name' => $name,
            ]);
            $order->items()->create([
                'inventory_item_id' => $item->id,
                'quantity' => $quantity, 'unit' => 'box', 'unit_price' => $price,
            ]);
        }

        return $order->fresh()->load('items.inventoryItem');
    }

    private function checkIn(Order $order, array $received, array $notes = [])
    {
        return $this->actingAs($this->manager)->patch(route('admin.orders.received', $order), [
            'received' => $received,
            'received_notes' => $notes,
        ]);
    }

    // ---- The case that costs money -----------------------------------------

    /** @test */
    public function an_over_delivery_is_caught_and_priced(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00]]);
        $line = $order->items->first();

        // The vendor sent 12 against an order of 10.
        $response = $this->checkIn($order, [$line->id => 12]);

        $line->refresh();
        $this->assertEqualsWithDelta(12, (float) $line->quantity_received, 1e-4);
        $this->assertEqualsWithDelta(2, $line->received_delta, 1e-4);
        $this->assertTrue($line->has_discrepancy);
        $this->assertSame('over', $line->received_status);

        $order->refresh()->load('items');
        $this->assertTrue($order->has_discrepancies);
        $this->assertEqualsWithDelta(290.00, $order->discrepancy_value, 0.01);

        // Surfaced loudly, not buried in a success message.
        $response->assertSessionHas('error', fn ($m) => str_contains($m, 'did not match')
            && str_contains($m, '$290.00'));
    }

    /** @test */
    public function a_short_delivery_is_caught_too(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00]]);
        $line = $order->items->first();

        $this->checkIn($order, [$line->id => 7]);

        $line->refresh();
        $this->assertEqualsWithDelta(-3, $line->received_delta, 1e-4);
        $this->assertSame('short', $line->received_status);
        $this->assertEqualsWithDelta(-435.00, $order->fresh()->load('items')->discrepancy_value, 0.01);
    }

    /** @test */
    public function a_matching_delivery_reports_success_with_no_flags(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00], ['Chicken', 4, 96.00]]);
        $lines = $order->items;

        $response = $this->checkIn($order, [
            $lines[0]->id => 10,
            $lines[1]->id => 4,
        ]);

        $order->refresh()->load('items');
        $this->assertFalse($order->has_discrepancies);
        $this->assertTrue($order->is_fully_checked);
        $this->assertEqualsWithDelta(0.0, $order->discrepancy_value, 0.01);
        $response->assertSessionHas('success');
    }

    /** @test */
    public function only_the_mismatched_lines_are_reported(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00], ['Chicken', 4, 96.00], ['Pita', 6, 41.00]]);
        $lines = $order->items;

        $this->checkIn($order, [
            $lines[0]->id => 10,   // exact
            $lines[1]->id => 6,    // over by 2
            $lines[2]->id => 6,    // exact
        ]);

        $order->refresh()->load('items.inventoryItem');
        $this->assertCount(1, $order->discrepancies);
        $this->assertSame('Chicken', $order->discrepancies->first()->inventoryItem->name);
    }

    // ---- Unchecked lines ----------------------------------------------------

    /** @test */
    public function a_blank_line_stays_unchecked_rather_than_reading_as_none_arrived(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00], ['Chicken', 4, 96.00]]);
        $lines = $order->items;

        $response = $this->checkIn($order, [
            $lines[0]->id => 10,
            $lines[1]->id => null,
        ]);

        $this->assertNull($lines[1]->fresh()->quantity_received);
        $this->assertSame('unchecked', $lines[1]->fresh()->received_status);
        $this->assertFalse($lines[1]->fresh()->has_discrepancy, 'Unknown is not a discrepancy.');
        $this->assertFalse($order->fresh()->load('items')->is_fully_checked);
        $response->assertSessionHas('success', fn ($m) => str_contains($m, '1 line(s) were left unchecked'));
    }

    /** @test */
    public function zero_received_is_a_real_answer_and_flags_as_short(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00]]);
        $line = $order->items->first();

        $this->checkIn($order, [$line->id => 0]);

        $line->refresh();
        $this->assertEqualsWithDelta(0, (float) $line->quantity_received, 1e-4);
        $this->assertSame('short', $line->received_status);
        $this->assertTrue($line->is_checked);
    }

    // ---- Notes and audit ----------------------------------------------------

    /** @test */
    public function a_note_can_be_left_against_a_line(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00]]);
        $line = $order->items->first();

        $this->checkIn($order, [$line->id => 8], [$line->id => '2 boxes damaged, refused']);

        $this->assertSame('2 boxes damaged, refused', $line->fresh()->received_notes);
    }

    /** @test */
    public function who_checked_the_delivery_in_is_recorded(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00]]);
        $this->checkIn($order, [$order->items->first()->id => 10]);

        $order->refresh();
        $this->assertSame($this->manager->id, $order->received_by);
        $this->assertNotNull($order->received_at);
        $this->assertSame($this->manager->id, $order->receiver->id);
    }

    // ---- Screens ------------------------------------------------------------

    /** @test */
    public function the_check_in_screen_shows_ordered_against_arrived(): void
    {
        $order = $this->placedOrder([['Ribeye Steak', 10, 145.00]]);

        $this->actingAs($this->manager)->get(route('admin.orders.receive', $order))
            ->assertOk()
            ->assertSee('Check In Delivery')
            ->assertSee('Ribeye Steak')
            ->assertSee('Enter what actually arrived')
            ->assertSee('Everything arrived as ordered');
    }

    /** @test */
    public function the_order_page_warns_when_the_delivery_did_not_match(): void
    {
        $order = $this->placedOrder([['Ribeye Steak', 10, 145.00]]);
        $this->checkIn($order, [$order->items->first()->id => 12]);

        $this->actingAs($this->manager)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('This delivery did not match the order')
            ->assertSee('Ribeye Steak')
            ->assertSee('over by 2');
    }

    /** @test */
    public function the_orders_list_flags_a_mismatched_delivery(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00]]);
        $this->checkIn($order, [$order->items->first()->id => 12]);

        $this->actingAs($this->manager)
            ->get(route('admin.orders.index', ['store_id' => $this->store->id]))
            ->assertOk()
            ->assertSee('mismatch');
    }

    /** @test */
    public function a_draft_cannot_be_checked_in(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00]]);
        $order->update(['status' => Order::STATUS_DRAFT, 'placed_at' => null]);

        $this->actingAs($this->manager)->get(route('admin.orders.receive', $order))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->checkIn($order, [$order->items->first()->id => 10])->assertSessionHas('error');
        $this->assertSame(Order::STATUS_DRAFT, $order->fresh()->status);
    }

    /** @test */
    public function a_manager_cannot_check_in_another_stores_delivery(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        $foreign = Order::create([
            'store_id' => $otherStore->id, 'vendor_id' => $this->lisanti->id,
            'week_start_date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'order_sequence' => 1, 'status' => Order::STATUS_PLACED,
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.receive', $foreign))->assertForbidden();
        $this->checkIn($foreign, [])->assertForbidden();
    }

    /** @test */
    public function a_negative_received_quantity_is_refused(): void
    {
        $order = $this->placedOrder([['Steak', 10, 145.00]]);
        $line = $order->items->first();

        $this->actingAs($this->manager)
            ->patchJson(route('admin.orders.received', $order), ['received' => [$line->id => -5]])
            ->assertStatus(422);

        $this->assertNull($line->fresh()->quantity_received);
        $this->assertSame(Order::STATUS_PLACED, $order->fresh()->status);
    }
}
