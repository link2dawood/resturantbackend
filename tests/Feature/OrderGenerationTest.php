<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $name): Vendor
    {
        return Vendor::create(['vendor_name' => $name, 'vendor_type' => 'Food', 'is_active' => true]);
    }

    /** @test */
    public function generate_creates_one_order_per_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $lisanti = $this->vendor('Lisanti');
        $depot = $this->vendor('Restaurant Depot');
        $steak = InventoryItem::factory()->create(['store_id' => $store->id]);
        $roll = InventoryItem::factory()->create(['store_id' => $store->id]);

        $this->actingAs($admin)->post(route('admin.orders.generate'), [
            'store_id' => $store->id, 'week_start_date' => '2026-08-17', 'order_sequence' => '1',
            'item_id' => [$steak->id, $roll->id],
            'vendor_id' => [$lisanti->id, $depot->id],
            'quantity' => [5, 10],
            'unit' => ['case', 'each'],
        ])->assertRedirect();

        $this->assertSame(2, Order::where('store_id', $store->id)->count());
        $order = Order::where('vendor_id', $lisanti->id)->with('items')->first();
        $this->assertSame(1, $order->items->count());
        $this->assertEqualsWithDelta(5, (float) $order->items->first()->quantity, 1e-4);
    }

    /** @test */
    public function order_1_and_order_2_coexist_in_the_same_week(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $vendor = $this->vendor('Sam\'s Club');
        $item = InventoryItem::factory()->create(['store_id' => $store->id]);

        foreach ([1, 2] as $seq) {
            $this->actingAs($admin)->post(route('admin.orders.generate'), [
                'store_id' => $store->id, 'week_start_date' => '2026-08-17', 'order_sequence' => (string) $seq,
                'item_id' => [$item->id], 'vendor_id' => [$vendor->id], 'quantity' => [3], 'unit' => ['case'],
            ]);
        }

        $this->assertSame(1, Order::where('store_id', $store->id)->where('order_sequence', 1)->count());
        $this->assertSame(1, Order::where('store_id', $store->id)->where('order_sequence', 2)->count());
    }

    /** @test */
    public function regenerating_replaces_the_draft_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $vendor = $this->vendor('Walmart');
        $item = InventoryItem::factory()->create(['store_id' => $store->id]);

        $payload = [
            'store_id' => $store->id, 'week_start_date' => '2026-08-17', 'order_sequence' => '1',
            'item_id' => [$item->id], 'vendor_id' => [$vendor->id], 'quantity' => [4], 'unit' => ['case'],
        ];
        $this->actingAs($admin)->post(route('admin.orders.generate'), $payload);
        $this->actingAs($admin)->post(route('admin.orders.generate'), $payload);

        $this->assertSame(1, Order::where('store_id', $store->id)->where('order_sequence', 1)->count());
    }

    /** @test */
    public function orders_move_through_placed_then_received(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $order = Order::create([
            'store_id' => $store->id, 'vendor_id' => $this->vendor('HEB')->id,
            'week_start_date' => '2026-08-17', 'order_sequence' => 1, 'status' => 'draft',
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.placed', $order));
        $this->assertSame('placed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->placed_at);

        $this->actingAs($admin)->patch(route('admin.orders.received', $order), ['received' => $order->items()->pluck('quantity', 'id')->all()]);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->received_at);
    }

    /** @test */
    public function the_order_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $item = InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Ribeye Steak']);
        $order = Order::create([
            'store_id' => $store->id, 'vendor_id' => $this->vendor('Lisanti')->id,
            'week_start_date' => '2026-08-17', 'order_sequence' => 1, 'status' => 'draft',
        ]);
        $order->items()->create(['inventory_item_id' => $item->id, 'quantity' => 5, 'unit' => 'case']);

        $this->actingAs($admin)->get(route('admin.orders.build', ['store_id' => $store->id]))
            ->assertOk()->assertSee('Ribeye Steak');
        $this->actingAs($admin)->get(route('admin.orders.index', ['store_id' => $store->id, 'week_start_date' => '2026-08-17']))
            ->assertOk();
        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()->assertSee('Ribeye Steak');
    }

    /** @test */
    public function employees_cannot_reach_orders(): void
    {
        $store = Store::factory()->create();
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $store->id]);

        $this->actingAs($employee)->get(route('admin.orders.index'))->assertStatus(403);
    }

    /** @test */
    public function the_vendor_seeder_adds_supply_vendors_and_retires_sysco(): void
    {
        // A pre-existing outdated vendor.
        Vendor::create(['vendor_name' => 'Sysco Foods', 'vendor_type' => 'Food', 'is_active' => true]);

        $this->seed(\Database\Seeders\VendorsSeeder::class);

        $this->assertDatabaseHas('vendors', ['vendor_name' => 'Lisanti', 'is_active' => true]);
        $this->assertDatabaseHas('vendors', ['vendor_name' => 'Restaurant Depot', 'is_active' => true]);
        $this->assertDatabaseHas('vendors', ['vendor_name' => 'HEB', 'is_active' => true]);
        $this->assertDatabaseHas('vendors', ['vendor_name' => 'Sysco Foods', 'is_active' => false]);
    }
}
