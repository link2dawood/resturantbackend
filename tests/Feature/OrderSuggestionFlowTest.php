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

class OrderSuggestionFlowTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private InventoryCategory $meats;

    private Vendor $lisanti;

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
            'preferred_vendor_id' => $this->lisanti->id,
            'is_active' => true,
        ], $overrides));
    }

    private function target(InventoryItem $item, float $target): void
    {
        StoreInventoryTarget::create([
            'store_id' => $this->store->id,
            'inventory_item_id' => $item->id,
            'target_stock_level' => $target,
        ]);
    }

    private function countRow(InventoryItem $item, float $base): void
    {
        InventoryStock::updateOrCreate(
            ['inventory_item_id' => $item->id, 'week_start_date' => $this->monday()->toDateString()],
            [
                'store_id' => $this->store->id,
                'starting_stock' => $base,
                'status' => InventoryStock::STATUS_DRAFT,
                'counted_by' => $this->manager->id,
                'counted_at' => now(),
            ]
        );
    }

    // ---- The screen --------------------------------------------------------

    /** @test */
    public function the_suggestions_screen_shows_the_calculation(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 6);

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.suggestions', ['week' => $this->monday()->toDateString()]))
            ->assertOk()
            ->assertSee('Order Suggestions')
            ->assertSee('Steak')
            ->assertSee('Lisanti')
            ->assertSee('1 of 1 items');
    }

    /** @test */
    public function submitting_the_count_lands_on_the_suggestions_screen(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);

        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $row = InventoryStock::where('inventory_item_id', $steak->id)->firstOrFail();

        $this->actingAs($this->manager)
            ->post(route('inventory.weekly-count.submit'), [
                'store_id' => $this->store->id,
                'week' => $this->monday()->toDateString(),
                'counts' => [$row->id => 53 * 6],
            ])
            ->assertRedirect(route('inventory.weekly-count.suggestions', [
                'store_id' => $this->store->id,
                'week' => $this->monday()->toDateString(),
            ]));
    }

    /** @test */
    public function the_screen_warns_about_items_with_no_target_and_no_count(): void
    {
        $this->item('Untargeted Item');

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.suggestions', ['week' => $this->monday()->toDateString()]))
            ->assertOk()
            ->assertSee('no stock target')
            ->assertSee('not counted this week');
    }

    // ---- Generating orders -------------------------------------------------

    /** @test */
    public function generating_an_order_uses_the_suggested_quantity(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 6);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$steak->id => 9],
            'vendors' => [$steak->id => $this->lisanti->id],
        ])->assertRedirect(route('admin.orders.index', ['store_id' => $this->store->id]));

        $line = OrderItem::where('inventory_item_id', $steak->id)->firstOrFail();
        $this->assertEqualsWithDelta(9, (float) $line->quantity, 1e-4);
        $this->assertEqualsWithDelta(9, (float) $line->suggested_quantity, 1e-4);
        $this->assertFalse($line->is_manual_override);
        $this->assertSame('box', $line->unit);
    }

    /** @test */
    public function a_manager_override_is_recorded_on_the_order_line(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 6); // suggests 9

        $response = $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$steak->id => 12], // manager orders more
            'vendors' => [$steak->id => $this->lisanti->id],
        ])->assertRedirect();

        $response->assertSessionHas('success', fn ($m) => str_contains($m, '1 line(s) overridden'));

        $line = OrderItem::where('inventory_item_id', $steak->id)->firstOrFail();
        $this->assertEqualsWithDelta(12, (float) $line->quantity, 1e-4);
        $this->assertEqualsWithDelta(9, (float) $line->suggested_quantity, 1e-4);
        $this->assertTrue($line->is_manual_override);
        $this->assertEqualsWithDelta(3, $line->override_delta, 1e-4);
    }

    /** @test */
    public function an_override_down_to_zero_drops_the_line_entirely(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 53 * 6);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$steak->id => 0],
            'vendors' => [$steak->id => $this->lisanti->id],
        ])->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    /** @test */
    public function one_draft_order_is_created_per_vendor(): void
    {
        $depot = Vendor::factory()->create(['vendor_name' => 'Restaurant Depot', 'vendor_type' => 'Food']);

        $steak = $this->item('Steak');
        $bread = $this->item('Bread', ['preferred_vendor_id' => $depot->id]);
        $this->target($steak, 15);
        $this->target($bread, 10);
        $this->countRow($steak, 0);
        $this->countRow($bread, 0);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$steak->id => 15, $bread->id => 10],
            'vendors' => [$steak->id => $this->lisanti->id, $bread->id => $depot->id],
        ])->assertRedirect();

        $this->assertSame(2, Order::count());
        $this->assertSame(1, Order::where('vendor_id', $this->lisanti->id)->firstOrFail()->items()->count());
        $this->assertSame(1, Order::where('vendor_id', $depot->id)->firstOrFail()->items()->count());
    }

    /** @test */
    public function an_item_with_no_vendor_is_left_out_of_the_order(): void
    {
        $orphan = $this->item('Orphan Item', ['preferred_vendor_id' => null]);
        $this->target($orphan, 15);
        $this->countRow($orphan, 0);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$orphan->id => 15],
            'vendors' => [$orphan->id => null],
        ])->assertSessionHas('error');

        $this->assertSame(0, Order::count());
    }

    /** @test */
    public function regenerating_replaces_the_draft_but_keeps_placed_orders(): void
    {
        $steak = $this->item();
        $this->target($steak, 15);
        $this->countRow($steak, 0);

        $placed = Order::create([
            'store_id' => $this->store->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(),
            'order_sequence' => 1,
            'status' => 'placed',
        ]);

        foreach ([15, 12] as $quantity) {
            $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
                'store_id' => $this->store->id,
                'week' => $this->monday()->toDateString(),
                'quantities' => [$steak->id => $quantity],
                'vendors' => [$steak->id => $this->lisanti->id],
            ])->assertRedirect();
        }

        $this->assertSame(1, Order::where('status', 'draft')->count());
        $this->assertNotNull(Order::find($placed->id), 'A placed order is history and must survive a rebuild.');
        $this->assertEqualsWithDelta(
            12,
            (float) Order::where('status', 'draft')->firstOrFail()->items()->firstOrFail()->quantity,
            1e-4
        );
    }

    // ---- Scoping -----------------------------------------------------------

    /** @test */
    public function a_quantity_posted_for_another_stores_item_is_ignored(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        $foreign = InventoryItem::factory()->create([
            'store_id' => $otherStore->id,
            'inventory_category_id' => $this->meats->id,
            'preferred_vendor_id' => $this->lisanti->id,
        ]);

        $mine = $this->item('Mine');
        $this->target($mine, 15);
        $this->countRow($mine, 0);

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$mine->id => 15, $foreign->id => 99],
            'vendors' => [$mine->id => $this->lisanti->id, $foreign->id => $this->lisanti->id],
        ])->assertRedirect();

        $this->assertSame(1, OrderItem::count());
        $this->assertSame(0, OrderItem::where('inventory_item_id', $foreign->id)->count());
    }

    /** @test */
    public function a_manager_only_sees_suggestions_for_their_own_store(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        InventoryItem::factory()->create([
            'store_id' => $otherStore->id,
            'inventory_category_id' => $this->meats->id,
            'name' => 'Omega Foreign Fixture',
        ]);
        $this->item('Zeta Mine Fixture');

        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.suggestions', ['store_id' => $otherStore->id]))
            ->assertOk()
            ->assertSee('Zeta Mine Fixture')
            ->assertDontSee('Omega Foreign Fixture');
    }

    /** @test */
    public function a_guest_is_redirected_to_login(): void
    {
        $this->get(route('inventory.weekly-count.suggestions'))->assertRedirect(route('login'));
    }

    // ---- End to end --------------------------------------------------------

    /** @test */
    public function the_full_monday_flow_runs_count_then_suggest_then_order(): void
    {
        $steak = $this->item('Steak');                       // 53 per box, target 15
        $oil = $this->item('Frying Oil', [
            'base_unit' => 'jug', 'purchase_unit' => 'jug', 'units_per_purchase' => 1,
        ]);
        $this->target($steak, 15);
        $this->target($oil, 10);

        // 1. Open the count, enter what is on the shelf, submit.
        $this->actingAs($this->manager)->get(route('inventory.weekly-count.index'))->assertOk();
        $steakRow = InventoryStock::where('inventory_item_id', $steak->id)->firstOrFail();
        $oilRow = InventoryStock::where('inventory_item_id', $oil->id)->firstOrFail();

        $this->actingAs($this->manager)->post(route('inventory.weekly-count.submit'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'counts' => [$steakRow->id => 4, $oilRow->id => 3],
        ])->assertRedirect(route('inventory.weekly-count.suggestions', [
            'store_id' => $this->store->id, 'week' => $this->monday()->toDateString(),
        ]));

        // 2. The suggestions screen proposes 11 boxes and 7 jugs.
        $this->actingAs($this->manager)
            ->get(route('inventory.weekly-count.suggestions', ['week' => $this->monday()->toDateString()]))
            ->assertOk()
            ->assertSee('2 of 2 items');

        // 3. Accept steak, override oil down to 5.
        $this->actingAs($this->manager)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->store->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$steak->id => 11, $oil->id => 5],
            'vendors' => [$steak->id => $this->lisanti->id, $oil->id => $this->lisanti->id],
        ])->assertRedirect();

        $order = Order::where('vendor_id', $this->lisanti->id)->firstOrFail();
        $lines = $order->items()->get()->keyBy('inventory_item_id');

        $this->assertEqualsWithDelta(11, (float) $lines[$steak->id]->quantity, 1e-4);
        $this->assertFalse($lines[$steak->id]->is_manual_override);

        $this->assertEqualsWithDelta(5, (float) $lines[$oil->id]->quantity, 1e-4);
        $this->assertEqualsWithDelta(7, (float) $lines[$oil->id]->suggested_quantity, 1e-4);
        $this->assertTrue($lines[$oil->id]->is_manual_override);
        $this->assertEqualsWithDelta(-2, $lines[$oil->id]->override_delta, 1e-4);
    }
}
