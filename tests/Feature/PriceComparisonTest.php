<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPrice;
use App\Services\Inventory\PriceComparisonService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceComparisonTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $name, string $type = 'Food'): Vendor
    {
        return Vendor::create(['vendor_name' => $name, 'vendor_type' => $type, 'is_active' => true]);
    }

    private function steak(Store $store): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $store->id, 'base_unit' => 'oz', 'purchase_unit' => 'case', 'units_per_purchase' => 640,
        ]);
    }

    private function price(Vendor $v, InventoryItem $item, float $price, string $unit, string $date): void
    {
        VendorPrice::create([
            'vendor_id' => $v->id, 'inventory_item_id' => $item->id,
            'price' => $price, 'price_unit' => $unit, 'effective_date' => $date,
        ]);
    }

    /** @test */
    public function cheapest_is_compared_per_base_unit_not_sticker_price(): void
    {
        $store = Store::factory()->create();
        $item = $this->steak($store);
        $lisanti = $this->vendor('Lisanti');
        $walmart = $this->vendor('Walmart', 'Supplies');

        // $50/case = $0.078/oz  vs  $0.10/oz → Lisanti is cheaper despite the bigger number.
        $this->price($lisanti, $item, 50, 'case', '2026-08-01');
        $this->price($walmart, $item, 0.10, 'oz', '2026-08-01');

        $svc = app(PriceComparisonService::class);
        $current = $svc->currentPrices([$item->id]);

        $this->assertSame($lisanti->id, $svc->cheapestVendorId($item, $current->get($item->id)));
    }

    /** @test */
    public function the_current_price_uses_the_latest_effective_date(): void
    {
        $store = Store::factory()->create();
        $item = $this->steak($store);
        $lisanti = $this->vendor('Lisanti');
        $walmart = $this->vendor('Walmart', 'Supplies');

        $this->price($lisanti, $item, 50, 'case', '2026-08-01');   // 0.078/oz
        $this->price($lisanti, $item, 80, 'case', '2026-08-08');   // newer: 0.125/oz
        $this->price($walmart, $item, 0.10, 'oz', '2026-08-01');   // 0.10/oz

        $svc = app(PriceComparisonService::class);
        $current = $svc->currentPrices([$item->id]);

        // Lisanti's latest (0.125) is now dearer than Walmart (0.10).
        $this->assertSame($walmart->id, $svc->cheapestVendorId($item, $current->get($item->id)));
    }

    /** @test */
    public function bulk_update_records_history_only_when_the_price_changes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $item = $this->steak($store);
        $vendor = $this->vendor('Lisanti');

        $post = fn ($p) => $this->actingAs($admin)->post(route('admin.vendor-prices.bulk'), [
            'store_id' => $store->id, 'prices' => [$item->id => [$vendor->id => $p]],
        ]);

        $post(50);   // creates
        $post(50);   // unchanged → no new row
        $post(60);   // changed → new row

        $this->assertSame(2, VendorPrice::where('inventory_item_id', $item->id)->count());
    }

    /** @test */
    public function apply_cheapest_sets_the_preferred_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $item = $this->steak($store);
        $lisanti = $this->vendor('Lisanti');
        $walmart = $this->vendor('Walmart', 'Supplies');

        $this->price($lisanti, $item, 50, 'case', '2026-08-01'); // 0.078/oz (cheapest)
        $this->price($walmart, $item, 0.10, 'oz', '2026-08-01');

        $this->actingAs($admin)->post(route('admin.vendor-prices.apply-cheapest'), ['store_id' => $store->id])
            ->assertRedirect();

        $this->assertSame($lisanti->id, $item->fresh()->preferred_vendor_id);
    }

    /** @test */
    public function the_pages_render_and_are_gated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $item = InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Ribeye Steak']);
        $this->vendor('Lisanti');

        $this->actingAs($admin)->get(route('admin.vendor-prices.index', ['store_id' => $store->id]))
            ->assertOk()->assertSee('Ribeye Steak');
        $this->actingAs($admin)->get(route('admin.vendor-prices.history', $item))->assertOk();

        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $store->id]);
        $this->actingAs($employee)->get(route('admin.vendor-prices.index'))->assertStatus(403);
    }
}
