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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 5 Part 1 Task 15 — the whole Monday workflow, driven through real HTTP
 * requests exactly as the manager would: count, submit, review suggestions,
 * generate orders, send the vendor report, mark placed, mark received.
 */
class GoLiveEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private User $admin;

    private User $owner;

    private Vendor $lisanti;

    private Vendor $depot;

    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-24')->startOfWeek(Carbon::MONDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo($this->monday()->copy()->setTime(7, 30));

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Owner']);
        $this->store = Store::factory()->create([
            'created_by' => $this->admin->id,
            'store_info' => 'Round Rock',
            'address' => '1200 Sam Bass Rd', 'city' => 'Round Rock', 'state' => 'TX', 'zip' => '78681',
            'phone' => '512-555-0100',
        ]);
        $this->manager = User::factory()->create([
            'role' => 'manager', 'store_id' => $this->store->id, 'name' => 'Dana Reed',
        ]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);

        // History and reorder are owner-facing now.
        $this->owner = User::factory()->create(['role' => 'owner', 'state' => 'TX', 'name' => 'Owner Two']);
        $this->owner->ownedStores()->attach($this->store->id);

        $this->lisanti = Vendor::factory()->create([
            'vendor_name' => 'Lisanti', 'vendor_type' => 'Food', 'contact_email' => 'orders@lisanti.test',
        ]);
        $this->depot = Vendor::factory()->create(['vendor_name' => 'Restaurant Depot', 'vendor_type' => 'Food']);
    }

    private function item(string $name, Vendor $vendor, float $perPurchase, string $purchaseUnit, float $target, float $price): InventoryItem
    {
        $item = InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => InventoryCategory::where('name', 'Meats')->value('id'),
            'name' => $name,
            'base_unit' => 'portion',
            'purchase_unit' => $purchaseUnit,
            'units_per_purchase' => $perPurchase,
            'preferred_vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $item->vendors()->attach($vendor->id, [
            'current_price' => $price, 'is_preferred_vendor' => true, 'price_updated_at' => now(),
        ]);

        StoreInventoryTarget::create([
            'store_id' => $this->store->id,
            'inventory_item_id' => $item->id,
            'target_stock_level' => $target,
            'min_stock_level' => 2,
        ]);

        return $item;
    }

    /** @test */
    public function the_whole_monday_workflow_runs_from_count_to_received(): void
    {
        Notification::fake();

        // ── Setup: two items, two vendors, targets and prices in place.
        $steak = $this->item('Steak', $this->lisanti, 53, 'box', 15, 145.00);
        $bread = $this->item('8 inch Bread', $this->depot, 60, 'case', 10, 32.00);

        // ── 1. The reminder is no longer scheduled (the client counts every
        // Monday by habit), but the command still works when run by hand.
        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()])->assertExitCode(0);
        Notification::assertSentTo($this->manager, \App\Notifications\InventoryReminderNotification::class);

        // ── 2. The manager opens the weekly count. Rows are created on demand.
        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.index'))
            ->assertOk()
            ->assertSee('Steak')
            ->assertSee('8 inch Bread')
            ->assertSee('0 of 2 items counted');

        $steakRow = InventoryStock::where('inventory_item_id', $steak->id)->firstOrFail();
        $breadRow = InventoryStock::where('inventory_item_id', $bread->id)->firstOrFail();

        // ── 3. Counting: a draft saves partway through, as it would on a phone.
        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$steakRow->id => 4],   // 4 boxes on hand
        ])->assertOk()->assertJsonPath('counted_items', 1);

        // ── 4. Submit the finished count. The week locks.
        $this->actingAs($this->manager)->post(route('inventory.weekly-count.submit'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$steakRow->id => 4, $breadRow->id => 3],
        ])->assertRedirect(route('inventory.weekly-count.suggestions', [
            'store_id' => $this->store->id, 'week' => $this->monday()->toDateString(),
        ]));

        $this->assertSame(InventoryStock::STATUS_SUBMITTED, $steakRow->fresh()->status);

        // A locked week refuses further edits.
        $this->actingAs($this->manager)->postJson(route('inventory.weekly-count.autosave'), [
            'store_id' => $this->store->id, 'week' => $this->monday()->toDateString(),
            'counts' => [$steakRow->id => 999],
        ])->assertStatus(422);

        // ── 5. Suggestions: target minus counted, per item.
        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.suggestions', ['week' => $this->monday()->toDateString()]))
            ->assertOk()
            ->assertSee('2 of 2 items');

        // Steak 15 - 4 = 11 boxes. Bread 10 - 3 = 7 cases.
        $suggestions = app(\App\Services\Inventory\OrderSuggestionService::class)
            ->generateSuggestions($this->store, $this->monday())
            ->keyBy(fn ($row) => $row['item']->name);

        $this->assertEqualsWithDelta(11.0, $suggestions['Steak']['suggested_order'], 0.001);
        $this->assertEqualsWithDelta(7.0, $suggestions['8 inch Bread']['suggested_order'], 0.001);

        // ── 6. Generate orders, overriding the bread down to 5.
        $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$steak->id => 11, $bread->id => 5],
            'vendors' => [$steak->id => $this->lisanti->id, $bread->id => $this->depot->id],
        ])->assertRedirect(route('admin.orders.index', ['store_id' => $this->store->id]));

        $this->assertSame(2, Order::count(), 'One draft order per vendor.');

        $lisantiOrder = Order::where('vendor_id', $this->lisanti->id)->firstOrFail();
        $depotOrder = Order::where('vendor_id', $this->depot->id)->firstOrFail();

        $breadLine = $depotOrder->items()->firstOrFail();
        $this->assertTrue($breadLine->is_manual_override, 'The override must be recorded.');
        $this->assertEqualsWithDelta(7.0, (float) $breadLine->suggested_quantity, 0.001);

        // ── 7. Price the Lisanti order and check the total.
        $steakLine = $lisantiOrder->items()->firstOrFail();
        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $lisantiOrder), [
            'quantity' => [$steakLine->id => 11],
            'unit_price' => [$steakLine->id => 145.00],
            'notes' => 'Deliver before 10am',
        ])->assertRedirect();

        $this->assertEqualsWithDelta(1595.00, $lisantiOrder->fresh()->load('items')->total, 0.001);

        // ── 8. The vendor report renders, in HTML and as a PDF.
        $this->actingAs($this->manager)->get(route('admin.orders.report', $lisantiOrder))
            ->assertOk()
            ->assertSee('Purchase Order')
            ->assertSee('Lisanti')
            ->assertSee('Round Rock')
            ->assertSee('$1,595.00');

        $pdf = $this->actingAs($this->manager)->get(route('admin.orders.report.pdf', $lisantiOrder))->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        // ── 9. Mark placed. The order locks and management is notified.
        $this->actingAs($this->manager)->patch(route('admin.orders.placed', $lisantiOrder))->assertRedirect();
        $this->assertSame(Order::STATUS_PLACED, $lisantiOrder->fresh()->status);
        Notification::assertSentTo($this->admin, \App\Notifications\OrderStatusChangedNotification::class);

        $this->actingAs($this->manager)->put(route('admin.orders.items.update', $lisantiOrder), [
            'quantity' => [$steakLine->id => 99],
        ])->assertSessionHas('error');

        // ── 10. Mark received.
        $this->actingAs($this->manager)->patch(route('admin.orders.received', $lisantiOrder))->assertRedirect();
        $this->assertSame(Order::STATUS_RECEIVED, $lisantiOrder->fresh()->status);
        $this->assertNotNull($lisantiOrder->fresh()->received_at);

        // ── 11. The dashboard reflects the finished week.
        $this->actingAs($this->manager)->get(route('admin.inventory-dashboard.index'))
            ->assertOk()
            ->assertSee('Recent activity')
            ->assertSee('Lisanti');

        // ── 12. History shows it, and the owner can reorder it next week.
        // The manager counts; reviewing and re-sending is the owner's job.
        $this->travelTo($this->monday()->copy()->addWeek()->setTime(8, 0));

        $this->actingAs($this->manager)->get(route('admin.orders.history'))->assertForbidden();

        $this->actingAs($this->owner)->get(route('admin.orders.history'))
            ->assertOk()
            ->assertSee('data-order-id="'.$lisantiOrder->id.'"', false);

        $this->actingAs($this->owner)->post(route('admin.orders.reorder', $lisantiOrder), [
            'week' => $this->monday()->copy()->addWeek()->toDateString(),
        ])->assertRedirect();

        $nextWeek = Order::forWeek($this->monday()->copy()->addWeek()->toDateString())->firstOrFail();
        $this->assertSame(Order::STATUS_DRAFT, $nextWeek->status);
        $this->assertEqualsWithDelta(11.0, (float) $nextWeek->items()->firstOrFail()->quantity, 0.001);
    }

    /** @test */
    public function the_wednesday_chase_fires_only_for_a_store_that_did_not_finish(): void
    {
        Notification::fake();

        $laggard = $this->store;
        $this->item('Steak', $this->lisanti, 53, 'box', 15, 145.00);

        // A second store that did finish.
        $goodStore = Store::factory()->create(['created_by' => $this->admin->id, 'store_info' => 'Downtown']);
        $goodManager = User::factory()->create(['role' => 'manager', 'store_id' => $goodStore->id]);
        $goodItem = InventoryItem::factory()->create([
            'store_id' => $goodStore->id,
            'inventory_category_id' => InventoryCategory::where('name', 'Meats')->value('id'),
        ]);
        InventoryStock::create([
            'inventory_item_id' => $goodItem->id, 'store_id' => $goodStore->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 10, 'status' => InventoryStock::STATUS_SUBMITTED, 'counted_at' => now(),
        ]);

        $this->travelTo($this->monday()->copy()->addDays(2)->setTime(8, 0));
        $this->artisan('inventory:remind-overdue', ['--week' => $this->monday()->toDateString()])->assertExitCode(0);

        Notification::assertSentTo($this->manager, \App\Notifications\InventoryOverdueNotification::class);
        Notification::assertNotSentTo($goodManager, \App\Notifications\InventoryOverdueNotification::class);
        $this->assertNotNull($laggard);
    }
}
