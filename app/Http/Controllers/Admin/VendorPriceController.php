<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorPrice;
use App\Services\Inventory\PriceComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Phase 5.7 — vendor price entry & comparison. A matrix of items × supply vendors
 * doubles as the bulk-entry form and the side-by-side comparison; the cheapest
 * vendor per item (normalized per base unit) is flagged, and "apply cheapest" can
 * set each item's preferred vendor. Prices are entered per the item's purchase
 * unit; every save records history. Admin/owner/manager, store-scoped.
 */
class VendorPriceController extends Controller
{
    private const SUPPLY_TYPES = ['Food', 'Beverage', 'Supplies'];

    public function __construct(private PriceComparisonService $prices)
    {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        $items = InventoryItem::where('store_id', $store->id)->where('is_active', true)
            ->orderBy('category')->orderBy('name')->get();
        $vendors = Vendor::where('is_active', true)->whereIn('vendor_type', self::SUPPLY_TYPES)
            ->orderBy('vendor_name')->get();

        $current = $this->prices->currentPrices($items->pluck('id'));
        $cheapest = [];
        foreach ($items as $item) {
            $cheapest[$item->id] = $this->prices->cheapestVendorId($item, $current->get($item->id) ?? collect());
        }

        return view('admin.vendor-prices.index', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'items' => $items,
            'vendors' => $vendors,
            'current' => $current,
            'cheapest' => $cheapest,
            'service' => $this->prices,
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $store = $this->resolveStore($request);
        $data = $request->validate([
            'prices' => ['required', 'array'],
            'prices.*' => ['array'],
            'prices.*.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = InventoryItem::where('store_id', $store->id)->get()->keyBy('id');
        $vendorIds = Vendor::where('is_active', true)->pluck('id')->all();
        $current = $this->prices->currentPrices($items->keys());

        $saved = 0;
        DB::transaction(function () use ($data, $items, $vendorIds, $current, &$saved) {
            foreach ($data['prices'] as $itemId => $vendorPrices) {
                $item = $items->get((int) $itemId);
                if (! $item) {
                    continue;
                }
                foreach ($vendorPrices as $vendorId => $value) {
                    if ($value === null || $value === '' || ! in_array((int) $vendorId, $vendorIds, true)) {
                        continue;
                    }
                    $new = round((float) $value, 2);
                    $existing = $current->get((int) $itemId)?->get((int) $vendorId);
                    // Only record a new history row when the price actually changed.
                    if ($existing && abs((float) $existing->price - $new) < 0.005) {
                        continue;
                    }

                    VendorPrice::create([
                        'vendor_id' => (int) $vendorId,
                        'inventory_item_id' => $item->id,
                        'price' => $new,
                        'price_unit' => $item->purchase_unit,
                        'effective_date' => now()->toDateString(),
                        'entered_by' => auth()->id(),
                    ]);
                    $saved++;
                }
            }
        });

        return back()->with('success', "{$saved} price(s) saved.");
    }

    public function applyCheapest(Request $request)
    {
        $store = $this->resolveStore($request);
        $items = InventoryItem::where('store_id', $store->id)->where('is_active', true)->get();
        $current = $this->prices->currentPrices($items->pluck('id'));

        $applied = 0;
        foreach ($items as $item) {
            $vendorId = $this->prices->cheapestVendorId($item, $current->get($item->id) ?? collect());
            if ($vendorId && $item->preferred_vendor_id !== $vendorId) {
                $item->update(['preferred_vendor_id' => $vendorId]);
                $applied++;
            }
        }

        return back()->with('success', "Set the cheapest vendor as preferred on {$applied} item(s).");
    }

    public function history(InventoryItem $inventoryItem)
    {
        abort_unless(in_array($inventoryItem->store_id, auth()->user()->getAccessibleStoreIds(), true), 403);

        $prices = VendorPrice::with('vendor')
            ->where('inventory_item_id', $inventoryItem->id)
            ->orderByDesc('effective_date')->orderByDesc('id')
            ->get();

        return view('admin.vendor-prices.history', ['item' => $inventoryItem, 'prices' => $prices]);
    }

    private function resolveStore(Request $request): Store
    {
        $accessible = auth()->user()->getAccessibleStoreIds();
        abort_if(empty($accessible), 403, 'No accessible store.');

        if (auth()->user()->isManager()) {
            $storeId = $accessible[0];
        } else {
            $requested = (int) $request->input('store_id');
            $storeId = in_array($requested, $accessible, true) ? $requested : $accessible[0];
        }

        return Store::findOrFail($storeId);
    }

    private function storeOptions(): Collection
    {
        if (auth()->user()->isManager()) {
            return collect();
        }

        return Store::whereIn('id', auth()->user()->getAccessibleStoreIds())->get();
    }
}
