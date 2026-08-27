<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\Inventory\InventoryItemCsvImporter;
use App\Services\Inventory\ItemVendorMapper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Phase 5 — the inventory item master: the list every order and variance figure
 * is built from. Store-scoped, because each store keeps its own item list.
 *
 * On the pack size: the spec calls it "portions per unit", the column is
 * units_per_purchase, and UnitConverter reads it as the number of base units in
 * one purchase unit. It stays editable for the life of the item, since suppliers
 * change pack sizes and a stale value corrupts every downstream number quietly.
 */
class InventoryItemController extends Controller
{
    private const SUPPLY_TYPES = ['Food', 'Beverage', 'Supplies'];

    public function __construct(
        private InventoryItemCsvImporter $importer,
        private ItemVendorMapper $mapper,
    ) {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);

        $sort = in_array($request->get('sort'), ['name', 'category', 'units_per_purchase', 'is_active'], true)
            ? $request->get('sort')
            : 'name';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';

        $query = InventoryItem::with(['inventoryCategory', 'preferredVendor', 'vendors'])
            ->forStore($store->id)
            ->search($request->get('search'));

        if ($request->filled('inventory_category_id')) {
            $query->where('inventory_category_id', (int) $request->inventory_category_id);
        }

        if ($request->filled('vendor_id')) {
            $vendorId = (int) $request->vendor_id;
            $query->where(function ($q) use ($vendorId) {
                $q->whereHas('vendors', fn ($v) => $v->where('vendors.id', $vendorId))
                    ->orWhere('preferred_vendor_id', $vendorId);
            });
        }

        if ($request->filled('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Shortcut from the "no vendor yet" banner.
        if ($request->boolean('unmapped')) {
            $query->whereDoesntHave('vendors');
        }

        // Category sorts follow the order-guide sequence, not the category name.
        if ($sort === 'category') {
            $query->leftJoin('inventory_categories', 'inventory_categories.id', '=', 'inventory_items.inventory_category_id')
                ->orderBy('inventory_categories.display_order', $direction)
                ->orderBy('inventory_items.name')
                ->select('inventory_items.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $items = $query->paginate(50)->withQueryString();

        return view('admin.inventory-items.index', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'items' => $items,
            'categories' => InventoryCategory::ordered()->get(),
            'vendors' => $this->supplyVendors(),
            'sort' => $sort,
            'direction' => $direction,
            // A warning, never a blocker: the client sets items up before pricing.
            'unmappedCount' => InventoryItem::forStore($store->id)->active()
                ->whereDoesntHave('vendors')->count(),
        ]);
    }

    public function store(StoreInventoryItemRequest $request)
    {
        $item = DB::transaction(function () use ($request) {
            $item = InventoryItem::create($this->attributes($request) + [
                'store_id' => (int) $request->store_id,
                'name' => trim($request->name),
            ]);

            $this->applyVendorMapping($item, $request);

            return $item;
        });

        return response()->json([
            'message' => 'Item added.',
            'data' => $item->load(['inventoryCategory', 'vendors']),
        ], 201);
    }

    public function show(InventoryItem $inventoryItem)
    {
        $this->authorizeStore($inventoryItem);

        return response()->json(
            $inventoryItem->load(['inventoryCategory', 'preferredVendor', 'vendors'])
        );
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $inventoryItem)
    {
        $this->authorizeStore($inventoryItem);

        DB::transaction(function () use ($request, $inventoryItem) {
            $inventoryItem->update($this->attributes($request) + ['name' => trim($request->name)]);
            $this->applyVendorMapping($inventoryItem, $request);
        });

        return response()->json([
            'message' => 'Item updated.',
            'data' => $inventoryItem->fresh()->load(['inventoryCategory', 'vendors']),
        ]);
    }

    /**
     * Hide an item. Soft delete, never a hard one: stock counts, order lines and
     * recipe ingredients all point here, and losing the row would break the
     * history those numbers were calculated from.
     */
    public function destroy(InventoryItem $inventoryItem)
    {
        $this->authorizeStore($inventoryItem);

        $inventoryItem->delete();

        return response()->json([
            'message' => 'Item hidden. Past counts, orders and recipes are unchanged.',
        ]);
    }

    /**
     * Attach one vendor to several items at once. Each item keeps whatever other
     * vendors it already had; this only adds. Items outside the current store are
     * dropped rather than silently reassigned.
     */
    public function bulkAssignVendor(Request $request)
    {
        $store = $this->resolveStore($request);
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
            'make_preferred' => ['nullable', 'boolean'],
        ]);

        $items = InventoryItem::forStore($store->id)
            ->whereIn('id', $data['item_ids'])
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'error' => 'None of those items belong to this store.',
            ], 422);
        }

        $result = $this->mapper->assignVendorToItems(
            $items,
            (int) $data['vendor_id'],
            (bool) ($data['make_preferred'] ?? false)
        );

        $vendorName = Vendor::find($data['vendor_id'])?->vendor_name ?? 'vendor';
        $skipped = count($data['item_ids']) - $items->count();

        return response()->json([
            'message' => sprintf(
                '%s assigned to %d item%s%s.%s',
                $vendorName,
                $items->count(),
                $items->count() === 1 ? '' : 's',
                $result['preferred'] > 0 ? ', set as preferred' : '',
                $skipped > 0 ? " {$skipped} item(s) from another store were skipped." : ''
            ),
            'data' => $result,
        ]);
    }

    // ---- CSV import wizard -------------------------------------------------

    public function importForm(Request $request)
    {
        $store = $this->resolveStore($request);

        return view('admin.inventory-items.import', [
            'store' => $store,
            'stores' => $this->storeOptions(),
        ]);
    }

    public function importPreview(Request $request)
    {
        $store = $this->resolveStore($request);
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $parsed = $this->importer->parse($request->file('file')->getRealPath(), $store->id);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $rows = collect($parsed['rows']);

        return view('admin.inventory-items.import-preview', [
            'store' => $store,
            'rows' => $parsed['rows'],
            'missingCategories' => $parsed['missing_categories'],
            'newCount' => $rows->where('is_duplicate', false)->where('errors', [])->count(),
            'duplicateCount' => $rows->where('is_duplicate', true)->count(),
            'errorCount' => $rows->filter(fn ($r) => ! empty($r['errors']))->count(),
        ]);
    }

    public function importCommit(Request $request)
    {
        $store = $this->resolveStore($request);
        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.name' => ['required', 'string', 'max:150'],
            'rows.*.category' => ['nullable', 'string', 'max:100'],
            'rows.*.unit' => ['nullable', 'string', 'max:20'],
            'rows.*.portion_unit' => ['nullable', 'string', 'max:20'],
            'rows.*.portions_per_unit' => ['nullable', 'numeric'],
            'rows.*.portion_size' => ['nullable', 'numeric'],
            'rows.*.inventory_category_id' => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'rows.*.existing_item_id' => ['nullable', 'integer'],
            'rows.*.action' => ['required', 'in:create,update,skip'],
            'create_missing_categories' => ['nullable', 'boolean'],
        ]);

        $rows = array_map(function (array $row) {
            // Errors are recomputed from the submitted values; a hand-edited
            // form must not be able to smuggle a bad row past the preview.
            $row['errors'] = [];
            $row['portions_per_unit'] = isset($row['portions_per_unit']) ? (float) $row['portions_per_unit'] : null;
            $row['portion_size'] = isset($row['portion_size']) && $row['portion_size'] !== null
                ? (float) $row['portion_size']
                : null;
            $row['category'] = $row['category'] ?? '';
            $row['unit'] = $row['unit'] ?? '';
            $row['portion_unit'] = $row['portion_unit'] ?? '';

            if ($row['portions_per_unit'] === null || $row['portions_per_unit'] <= 0) {
                $row['errors'][] = 'Portions per unit must be greater than zero.';
            }

            return $row;
        }, $data['rows']);

        $result = $this->importer->commit(
            $rows,
            $store->id,
            (bool) ($data['create_missing_categories'] ?? false)
        );

        return redirect()->route('admin.inventory-items.index', ['store_id' => $store->id])
            ->with('success', sprintf(
                'Import finished: %d created, %d updated, %d skipped.',
                $result['created'], $result['updated'], $result['skipped']
            ));
    }

    // ---- helpers -----------------------------------------------------------

    /** @return array<string, mixed> */
    private function attributes($request): array
    {
        $category = InventoryCategory::find($request->inventory_category_id);

        return [
            'inventory_category_id' => (int) $request->inventory_category_id,
            // The legacy free-text column is kept in step so the older screens
            // that still read it do not go blank. See migration 000003.
            'category' => $category?->name,
            'base_unit' => trim($request->base_unit),
            'purchase_unit' => trim($request->purchase_unit),
            'units_per_purchase' => (float) $request->units_per_purchase,
            'portion_size' => $request->filled('portion_size') ? (float) $request->portion_size : null,
            'portion_unit' => $request->filled('portion_size') ? trim((string) $request->portion_unit) : null,
            'min_stock_level' => (float) ($request->min_stock_level ?? 0),
            'safety_buffer_pct' => (float) ($request->safety_buffer_pct ?? 0),
            'reorder_threshold' => (float) ($request->reorder_threshold ?? 0),
            'notes' => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ];
    }

    /**
     * Hand the submitted mapping table to the mapper, which owns the
     * one-preferred-vendor rule and the price history mirror. Absent means the
     * caller is not editing mappings, so existing ones are left alone.
     */
    private function applyVendorMapping(InventoryItem $item, $request): void
    {
        if (! $request->has('vendors')) {
            return;
        }

        $this->mapper->sync($item, (array) $request->input('vendors', []));
    }

    private function authorizeStore(InventoryItem $item): void
    {
        abort_unless(
            in_array($item->store_id, auth()->user()->getAccessibleStoreIds(), true),
            403,
            'That item belongs to a store you cannot access.'
        );
    }

    private function supplyVendors()
    {
        return Vendor::where('is_active', true)
            ->whereIn('vendor_type', self::SUPPLY_TYPES)
            ->orderBy('vendor_name')
            ->get();
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

    private function storeOptions()
    {
        if (auth()->user()->isManager()) {
            return collect();
        }

        return Store::whereIn('id', auth()->user()->getAccessibleStoreIds())
            ->orderBy('store_info')
            ->get();
    }
}
