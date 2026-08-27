<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorPrice;
use App\Services\Inventory\PriceComparisonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    private const SORTS = ['name', 'category', 'cheapest', 'priced'];

    public function __construct(private PriceComparisonService $prices)
    {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        $items = $this->itemsFor($store->id, $request);
        $vendors = $this->supplyVendors();

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
            'categories' => InventoryCategory::ordered()->get(),
            'current' => $current,
            'cheapest' => $cheapest,
            'service' => $this->prices,
        ]);
    }

    /**
     * Side-by-side comparison. Every vendor's current price per item, normalized
     * per base unit so a $/case quote and a $/oz quote are judged on the same
     * footing, with the cheapest flagged and the move since the last price shown.
     */
    public function compare(Request $request)
    {
        $store = $this->resolveStore($request);
        $items = $this->itemsFor($store->id, $request);
        $vendors = $this->supplyVendors();

        $rows = $this->prices->comparisonRows($items, $vendors);
        $rows = $this->sortRows($rows, $request->get('sort'), $request->get('direction'));

        return view('admin.vendor-prices.compare', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'vendors' => $vendors,
            'categories' => InventoryCategory::ordered()->get(),
            'rows' => $rows,
            'sort' => in_array($request->get('sort'), self::SORTS, true) ? $request->get('sort') : 'name',
            'direction' => $request->get('direction') === 'desc' ? 'desc' : 'asc',
            'unpricedCount' => collect($rows)->where('has_no_price', true)->count(),
        ]);
    }

    /** The same comparison, as a CSV download. */
    public function exportCompare(Request $request): StreamedResponse
    {
        $store = $this->resolveStore($request);
        $items = $this->itemsFor($store->id, $request);
        $vendors = $this->supplyVendors();

        $rows = $this->sortRows(
            $this->prices->comparisonRows($items, $vendors),
            $request->get('sort'),
            $request->get('direction')
        );

        $filename = 'price-comparison-'.str($store->store_info)->slug().'-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $vendors) {
            $handle = fopen('php://output', 'w');

            $header = ['Item', 'Category', 'Purchase Unit', 'Base Unit'];
            foreach ($vendors as $vendor) {
                $header[] = $vendor->vendor_name.' price';
                $header[] = $vendor->vendor_name.' per base unit';
                $header[] = $vendor->vendor_name.' change %';
            }
            $header[] = 'Cheapest vendor';
            $header[] = 'Cheapest per base unit';
            fputcsv($handle, $header);

            foreach ($rows as $row) {
                $item = $row['item'];
                $line = [
                    $item->name,
                    $item->inventoryCategory?->name ?? $item->category,
                    $item->purchase_unit,
                    $item->base_unit,
                ];

                foreach ($vendors as $vendor) {
                    $cell = $row['cells'][$vendor->id];
                    $line[] = $cell['price'] !== null ? number_format($cell['price'], 2, '.', '') : '';
                    $line[] = $cell['per_base'] !== null ? number_format($cell['per_base'], 4, '.', '') : '';
                    $line[] = $cell['change_pct'] !== null ? $cell['change_pct'] : '';
                }

                $cheapest = $vendors->firstWhere('id', $row['cheapest_vendor_id']);
                $line[] = $cheapest?->vendor_name ?? 'No price entered';
                $line[] = $row['cheapest_per_base'] !== null ? number_format($row['cheapest_per_base'], 4, '.', '') : '';

                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
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

    /**
     * Active items for a store, optionally narrowed to one category, in
     * order-guide sequence.
     */
    private function itemsFor(int $storeId, Request $request): Collection
    {
        $query = InventoryItem::with('inventoryCategory')
            ->where('store_id', $storeId)
            ->where('is_active', true);

        if ($request->filled('inventory_category_id')) {
            $query->where('inventory_category_id', (int) $request->inventory_category_id);
        }

        return $query->get()
            ->sortBy([
                fn ($a, $b) => ($a->inventoryCategory?->display_order ?? PHP_INT_MAX)
                    <=> ($b->inventoryCategory?->display_order ?? PHP_INT_MAX),
                fn ($a, $b) => strcasecmp($a->name, $b->name),
            ])
            ->values();
    }

    private function supplyVendors(): Collection
    {
        return Vendor::where('is_active', true)
            ->whereIn('vendor_type', self::SUPPLY_TYPES)
            ->orderBy('vendor_name')
            ->get();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortRows(array $rows, ?string $sort, ?string $direction): array
    {
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'name';
        $descending = $direction === 'desc';

        $key = match ($sort) {
            'cheapest' => fn ($row) => $row['cheapest_per_base'],
            'priced' => fn ($row) => $row['priced_count'],
            'category' => fn ($row) => $row['item']->inventoryCategory?->display_order ?? PHP_INT_MAX,
            default => fn ($row) => mb_strtolower($row['item']->name),
        };

        // When sorting by price, rows with no comparable price are held out and
        // appended, so they sort last in BOTH directions. A sentinel value would
        // put them first on a descending sort, burying the real prices.
        if ($sort === 'cheapest') {
            $comparable = collect($rows)->filter(fn ($row) => $row['cheapest_per_base'] !== null);
            $unpriced = collect($rows)->reject(fn ($row) => $row['cheapest_per_base'] !== null)
                ->sortBy(fn ($row) => mb_strtolower($row['item']->name));

            return $comparable->sortBy($key, SORT_REGULAR, $descending)
                ->concat($unpriced)
                ->values()
                ->all();
        }

        return collect($rows)->sortBy($key, SORT_REGULAR, $descending)->values()->all();
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
