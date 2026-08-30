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
use App\Services\Inventory\OrderSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Phase 5 Part 1 Task 15 — go-live hardening.
 *
 * Two things a feature test suite does not otherwise prove:
 *   1. store isolation, swept across every Phase 5 route in one place
 *   2. that the pages hold up at the client's real scale
 */
class GoLiveHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Store $storeA;

    private Store $storeB;

    private User $managerA;

    private User $admin;

    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-24')->startOfWeek(Carbon::MONDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo($this->monday()->copy()->setTime(9, 0));
        Notification::fake();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->storeA = Store::factory()->create(['created_by' => $this->admin->id, 'store_info' => 'Store Alpha']);
        $this->storeB = Store::factory()->create(['created_by' => $this->admin->id, 'store_info' => 'Store Bravo']);

        $this->managerA = User::factory()->create(['role' => 'manager', 'store_id' => $this->storeA->id]);
        $this->managerA->assignedStoresPivot()->attach($this->storeA->id);
    }

    private function itemFor(Store $store, string $name): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $store->id,
            'inventory_category_id' => InventoryCategory::where('name', 'Meats')->value('id'),
            'name' => $name,
            'base_unit' => 'portion', 'purchase_unit' => 'box', 'units_per_purchase' => 53,
            'is_active' => true,
        ]);
    }

    // ---- Cross-store security sweep ----------------------------------------

    /** @test */
    public function no_phase_five_page_leaks_another_stores_item_names(): void
    {
        $mine = $this->itemFor($this->storeA, 'Zeta Alpha Fixture');
        $theirs = $this->itemFor($this->storeB, 'Omega Bravo Fixture');

        InventoryStock::create([
            'inventory_item_id' => $theirs->id, 'store_id' => $this->storeB->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 5, 'status' => 'draft', 'counted_at' => now(),
        ]);
        StoreInventoryTarget::create([
            'store_id' => $this->storeB->id, 'inventory_item_id' => $theirs->id,
            'target_stock_level' => 99, 'min_stock_level' => 50,
        ]);

        // Every read route, asked for the OTHER store on purpose.
        $routes = [
            route('admin.inventory-items.index', ['store_id' => $this->storeB->id]),
            route('inventory.weekly-count.index', ['store_id' => $this->storeB->id]),
            route('inventory.weekly-count.suggestions', ['store_id' => $this->storeB->id]),
            route('admin.inventory-dashboard.index', ['store_id' => $this->storeB->id]),
            route('admin.orders.index', ['store_id' => $this->storeB->id]),
            route('admin.orders.history', ['store_id' => $this->storeB->id]),
            route('admin.vendor-prices.index', ['store_id' => $this->storeB->id]),
            route('admin.vendor-prices.compare', ['store_id' => $this->storeB->id]),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->managerA)->get($url);

            $this->assertContains($response->status(), [200, 302, 403], "Unexpected status for {$url}");

            if ($response->status() === 200) {
                $response->assertDontSee('Omega Bravo Fixture');
            }
        }

        $this->assertNotNull($mine);
    }

    /** @test */
    public function a_manager_cannot_reach_another_stores_records_by_id(): void
    {
        $theirItem = $this->itemFor($this->storeB, 'Bravo Steak');
        $theirOrder = Order::create([
            'store_id' => $this->storeB->id, 'vendor_id' => Vendor::factory()->create()->id,
            'week_start_date' => $this->monday()->toDateString(), 'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
        ]);

        $forbidden = [
            ['get', route('admin.inventory-items.show', $theirItem)],
            ['get', route('admin.orders.show', $theirOrder)],
            ['get', route('admin.orders.report', $theirOrder)],
            ['get', route('admin.orders.report.pdf', $theirOrder)],
            ['get', route('admin.inventory-targets.index', $this->storeB)],
            ['patch', route('admin.orders.placed', $theirOrder)],
            ['patch', route('admin.orders.received', $theirOrder)],
            ['patch', route('admin.orders.cancel', $theirOrder)],
            ['post', route('admin.orders.duplicate', $theirOrder)],
            ['post', route('admin.orders.reorder', $theirOrder)],
            ['delete', route('admin.orders.destroy', $theirOrder)],
        ];

        foreach ($forbidden as [$method, $url]) {
            $this->actingAs($this->managerA)->{$method}($url)
                ->assertForbidden("Expected 403 for {$method} {$url}");
        }

        $this->assertSame(Order::STATUS_DRAFT, $theirOrder->fresh()->status);
        $this->assertNotNull(InventoryItem::find($theirItem->id));
    }

    /** @test */
    public function posted_ids_belonging_to_another_store_are_ignored_not_written(): void
    {
        $mine = $this->itemFor($this->storeA, 'Alpha Steak');
        $theirs = $this->itemFor($this->storeB, 'Bravo Steak');
        $vendor = Vendor::factory()->create(['vendor_type' => 'Food']);

        // Bulk vendor assignment
        $this->actingAs($this->managerA)->postJson(route('admin.inventory-items.bulk-assign-vendor'), [
            'store_id' => $this->storeA->id,
            'vendor_id' => $vendor->id,
            'item_ids' => [$mine->id, $theirs->id],
        ])->assertOk();
        $this->assertCount(0, $theirs->fresh()->vendors);

        // Stock targets
        $this->actingAs($this->managerA)->post(route('admin.inventory-targets.update', $this->storeA), [
            'targets' => [$theirs->id => ['target_stock_level' => 99]],
        ])->assertRedirect();
        $this->assertDatabaseMissing('store_inventory_targets', ['inventory_item_id' => $theirs->id]);

        // Order generation from suggestions
        $this->actingAs($this->managerA)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->storeA->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$theirs->id => 99],
            'vendors' => [$theirs->id => $vendor->id],
        ]);
        $this->assertSame(0, Order::whereHas('items', fn ($q) => $q->where('inventory_item_id', $theirs->id))->count());
    }

    /** @test */
    public function a_manager_counts_and_receives_but_never_orders(): void
    {
        $item = $this->itemFor($this->storeA, 'Alpha Steak');
        $vendor = Vendor::factory()->create(['vendor_name' => 'Lisanti', 'vendor_type' => 'Food']);

        // The client drew this line: "manager can send just the inventory on
        // hand", and "right now only owners can place an order".
        $this->actingAs($this->managerA)
            ->get(route('inventory.weekly-count.suggestions', ['store_id' => $this->storeA->id]))
            ->assertForbidden();

        $this->actingAs($this->managerA)->post(route('inventory.weekly-count.generate-order'), [
            'store_id' => $this->storeA->id,
            'week' => $this->monday()->toDateString(),
            'quantities' => [$item->id => 5],
            'vendors' => [$item->id => $vendor->id],
        ])->assertForbidden();

        $this->assertSame(0, Order::count(), 'A manager must not be able to raise an order.');

        // Counting stays open to them.
        $this->actingAs($this->managerA)->get(route('inventory.weekly-count.index'))->assertOk();

        // So does checking a delivery in: "once orders are received managers
        // should go into the system and verify received".
        $order = Order::create([
            'store_id' => $this->storeA->id,
            'vendor_id' => $vendor->id,
            'order_date' => $this->monday()->toDateString(),
            'week_start_date' => $this->monday()->toDateString(),
            'status' => Order::STATUS_PLACED,
            'placed_at' => now(),
            'created_by' => $this->admin->id,
            'total_amount' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'inventory_item_id' => $item->id,
            'quantity' => 5,
            'unit' => 'box',
            'unit_price' => 10,
            'line_total' => 50,
        ]);

        $this->actingAs($this->managerA)->get(route('admin.orders.receive', $order))->assertOk();

        $this->actingAs($this->managerA)->patch(route('admin.orders.received', $order), [
            'received' => [$order->items()->value('id') => 4],
        ])->assertRedirect();

        $this->assertSame(Order::STATUS_RECEIVED, $order->fresh()->status);
        $this->assertEqualsWithDelta(4.0, (float) $order->items()->value('quantity_received'), 0.001);
    }

    /** @test */
    public function the_order_screens_hide_the_buttons_a_manager_cannot_use(): void
    {
        $item = $this->itemFor($this->storeA, 'Alpha Steak');
        $vendor = Vendor::factory()->create(['vendor_name' => 'Lisanti', 'vendor_type' => 'Food']);

        $order = Order::create([
            'store_id' => $this->storeA->id,
            'vendor_id' => $vendor->id,
            'week_start_date' => $this->monday()->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
            'created_by' => $this->admin->id,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'inventory_item_id' => $item->id,
            'quantity' => 5, 'unit' => 'box', 'unit_price' => 10, 'line_total' => 50,
        ]);

        // A dead button is worse than a missing one: it 403s after a click.
        $index = $this->actingAs($this->managerA)
            ->get(route('admin.orders.index', ['store_id' => $this->storeA->id]))
            ->assertOk();

        $index->assertDontSee(route('admin.orders.build', ['store_id' => $this->storeA->id]), false);
        $index->assertDontSee(route('admin.orders.history', ['store_id' => $this->storeA->id]), false);
        $index->assertDontSee(route('admin.orders.placed', $order), false);
        $index->assertDontSee(route('admin.orders.duplicate', $order), false);

        // What they do need stays.
        $index->assertSee(route('admin.orders.show', $order), false);

        $show = $this->actingAs($this->managerA)->get(route('admin.orders.show', $order))->assertOk();
        $show->assertDontSee(route('admin.orders.items.update', $order), false);
        $show->assertSee('Check in delivery');

        // The owner still gets the full set.
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerA->ownedStores()->attach($this->storeA->id);

        $this->actingAs($ownerA)->get(route('admin.orders.index', ['store_id' => $this->storeA->id]))
            ->assertOk()
            ->assertSee(route('admin.orders.build', ['store_id' => $this->storeA->id]), false);

        $this->actingAs($ownerA)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee(route('admin.orders.items.update', $order), false);
    }

    /** @test */
    public function an_employee_is_confined_to_the_count_screens(): void
    {
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $this->storeA->id]);
        $this->itemFor($this->storeA, 'Alpha Steak');

        // Allowed
        $this->actingAs($employee)->get(route('inventory.entry.index'))->assertOk();

        // Everything management-facing is closed.
        foreach ([
            route('admin.inventory-items.index'),
            route('admin.inventory-categories.index'),
            route('admin.vendors.index'),
            route('admin.orders.index'),
            route('admin.orders.history'),
            route('admin.vendor-prices.index'),
            route('admin.vendor-prices.compare'),
            route('admin.inventory-dashboard.index'),
        ] as $url) {
            $this->actingAs($employee)->get($url)->assertForbidden("Expected 403 for {$url}");
        }
    }

    /** @test */
    public function every_phase_five_route_requires_authentication(): void
    {
        $order = Order::create([
            'store_id' => $this->storeA->id, 'vendor_id' => Vendor::factory()->create()->id,
            'week_start_date' => $this->monday()->toDateString(), 'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
        ]);

        foreach ([
            route('admin.inventory-items.index'),
            route('admin.inventory-categories.index'),
            route('inventory.weekly-count.index'),
            route('inventory.weekly-count.suggestions'),
            route('admin.orders.index'),
            route('admin.orders.history'),
            route('admin.orders.report', $order),
            route('admin.vendor-prices.compare'),
            route('admin.inventory-targets.index', $this->storeA),
            route('notifications.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    // ---- Load ---------------------------------------------------------------

    /** @test */
    public function the_screens_hold_up_at_the_clients_real_scale(): void
    {
        // 120 items, 6 vendors, 3 stores: comfortably past what the client has.
        $vendors = collect(['Lisanti', 'Restaurant Depot', "Sam's Club", 'Coca-Cola', 'Walmart', 'HEB'])
            ->map(fn ($name) => Vendor::factory()->create(['vendor_name' => $name, 'vendor_type' => 'Food']));

        $categories = InventoryCategory::ordered()->get();
        $stores = collect([$this->storeA, $this->storeB, Store::factory()->create(['created_by' => $this->admin->id])]);

        foreach ($stores as $store) {
            $items = collect(range(1, 120))->map(fn ($n) => InventoryItem::factory()->create([
                'store_id' => $store->id,
                'inventory_category_id' => $categories[$n % $categories->count()]->id,
                'name' => "Item {$n} for store {$store->id}",
                'base_unit' => 'portion', 'purchase_unit' => 'box', 'units_per_purchase' => 24,
                'preferred_vendor_id' => $vendors[$n % 6]->id,
                'is_active' => true,
            ]));

            foreach ($items as $item) {
                $item->vendors()->attach($vendors[$item->id % 6]->id, [
                    'current_price' => 25.00, 'is_preferred_vendor' => true,
                ]);
                StoreInventoryTarget::create([
                    'store_id' => $store->id, 'inventory_item_id' => $item->id,
                    'target_stock_level' => 10, 'min_stock_level' => 3,
                ]);
                InventoryStock::create([
                    'inventory_item_id' => $item->id, 'store_id' => $store->id,
                    'week_start_date' => $this->monday()->toDateString(),
                    'starting_stock' => 24 * 4, 'status' => 'draft', 'counted_at' => now(),
                ]);
            }
        }

        $this->assertSame(360, InventoryItem::count());

        // Each page must render, and the suggestion engine must cover every item.
        $ownerA = User::factory()->create(['role' => 'owner']);
        $ownerA->ownedStores()->attach($this->storeA->id);

        // Suggestions is an owner screen; the rest the manager opens daily.
        $pages = [
            [$this->managerA, route('inventory.weekly-count.index', ['store_id' => $this->storeA->id])],
            [$ownerA, route('inventory.weekly-count.suggestions', ['store_id' => $this->storeA->id])],
            [$this->managerA, route('admin.inventory-items.index', ['store_id' => $this->storeA->id])],
            [$this->managerA, route('admin.vendor-prices.compare', ['store_id' => $this->storeA->id])],
            [$this->managerA, route('admin.inventory-dashboard.index', ['store_id' => $this->storeA->id])],
        ];

        foreach ($pages as [$actor, $url]) {
            $start = microtime(true);
            $this->actingAs($actor)->get($url)->assertOk();
            $elapsed = microtime(true) - $start;

            // Generous: this is a correctness guard against an accidental N+1
            // blowing up, not a performance benchmark.
            $this->assertLessThan(20, $elapsed, "{$url} took {$elapsed}s at 120 items.");
        }

        $suggestions = app(OrderSuggestionService::class)->generateSuggestions($this->storeA, $this->monday());
        $this->assertCount(120, $suggestions);
        // 10 target - 4 on hand = 6 boxes each.
        $this->assertEqualsWithDelta(6.0, $suggestions->first()['suggested_order'], 0.001);
    }

    /** @test */
    public function the_weekly_count_page_does_not_issue_a_query_per_item(): void
    {
        $vendor = Vendor::factory()->create(['vendor_type' => 'Food']);

        foreach (range(1, 60) as $n) {
            InventoryItem::factory()->create([
                'store_id' => $this->storeA->id,
                'inventory_category_id' => InventoryCategory::where('name', 'Meats')->value('id'),
                'name' => "Load Item {$n}",
                'preferred_vendor_id' => $vendor->id,
                'is_active' => true,
            ]);
        }

        // Warm the lazily-created stock rows first, so the measured request is
        // a normal page load rather than the one that opens the week.
        $this->actingAs($this->managerA)->get(route('inventory.weekly-count.index'))->assertOk();

        $queries = 0;
        \Illuminate\Support\Facades\DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->actingAs($this->managerA)->get(route('inventory.weekly-count.index'))->assertOk();

        // Eager loading means the count is flat in the number of items. A
        // per-item query would put this well past 60.
        $this->assertLessThan(40, $queries, "The count page ran {$queries} queries for 60 items.");
    }
}
