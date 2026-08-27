<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 — per-store stock targets. Same item list, different levels per store:
 * "Round Rock keeps 15 boxes of steak, Downtown keeps 8."
 *
 * Levels are held in the item's purchase unit, which is how the client talks
 * about stock and how orders are placed. The screen shows the base-unit
 * equivalent alongside so the two readings never get confused.
 */
class InventoryTargetController extends Controller
{
    public function index(Request $request, Store $store)
    {
        $this->authorizeStore($store);

        $items = $this->itemsFor($store, $request);
        $targets = StoreInventoryTarget::where('store_id', $store->id)
            ->get()
            ->keyBy('inventory_item_id');

        return view('admin.inventory-targets.index', [
            'store' => $store,
            'items' => $items,
            'targets' => $targets,
            'categories' => InventoryCategory::ordered()->get(),
            'otherStores' => $this->copyableStores($store),
            'setCount' => $targets->count(),
        ]);
    }

    /**
     * Save the edited grid. A blank target means "no target for this item", so
     * the row is removed rather than stored as a zero that would read as
     * "stock up to nothing".
     */
    public function update(Request $request, Store $store)
    {
        $this->authorizeStore($store);

        $data = $request->validate([
            'targets' => ['required', 'array'],
            'targets.*.target_stock_level' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'targets.*.min_stock_level' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $storeItemIds = InventoryItem::where('store_id', $store->id)->pluck('id')->all();
        $saved = 0;
        $cleared = 0;

        DB::transaction(function () use ($data, $store, $storeItemIds, &$saved, &$cleared) {
            foreach ($data['targets'] as $itemId => $row) {
                $itemId = (int) $itemId;

                // Never let a posted id reach across to another store's item.
                if (! in_array($itemId, $storeItemIds, true)) {
                    continue;
                }

                $target = $row['target_stock_level'] ?? null;
                $min = $row['min_stock_level'] ?? null;

                if (! filled($target) && ! filled($min)) {
                    $cleared += StoreInventoryTarget::where('store_id', $store->id)
                        ->where('inventory_item_id', $itemId)
                        ->delete();

                    continue;
                }

                StoreInventoryTarget::updateOrCreate(
                    ['store_id' => $store->id, 'inventory_item_id' => $itemId],
                    [
                        'target_stock_level' => filled($target) ? (float) $target : 0,
                        'min_stock_level' => filled($min) ? (float) $min : null,
                    ]
                );
                $saved++;
            }
        });

        return back()->with('success', sprintf(
            '%d target%s saved%s.',
            $saved,
            $saved === 1 ? '' : 's',
            $cleared > 0 ? ", {$cleared} cleared" : ''
        ));
    }

    /**
     * Set the same default on every item in the store that has no target yet, so
     * a new store can be brought up in one click. Existing targets are only
     * touched when the user explicitly asks to overwrite.
     */
    public function bulkDefault(Request $request, Store $store)
    {
        $this->authorizeStore($store);

        $data = $request->validate([
            'default_target' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'default_min' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'inventory_category_id' => ['nullable', 'integer', 'exists:inventory_categories,id'],
            'overwrite_existing' => ['nullable', 'boolean'],
        ]);

        $query = InventoryItem::where('store_id', $store->id)->where('is_active', true);

        if (! empty($data['inventory_category_id'])) {
            $query->where('inventory_category_id', (int) $data['inventory_category_id']);
        }

        $overwrite = (bool) ($data['overwrite_existing'] ?? false);

        if (! $overwrite) {
            $query->whereDoesntHave('targets', fn ($q) => $q->where('store_id', $store->id));
        }

        $items = $query->get();
        $written = 0;

        DB::transaction(function () use ($items, $store, $data, &$written) {
            foreach ($items as $item) {
                StoreInventoryTarget::updateOrCreate(
                    ['store_id' => $store->id, 'inventory_item_id' => $item->id],
                    [
                        'target_stock_level' => (float) $data['default_target'],
                        'min_stock_level' => filled($data['default_min'] ?? null) ? (float) $data['default_min'] : null,
                    ]
                );
                $written++;
            }
        });

        return back()->with('success', sprintf(
            'Default target applied to %d item%s%s.',
            $written,
            $written === 1 ? '' : 's',
            $overwrite ? '' : ' that had none'
        ));
    }

    /**
     * Copy another store's targets across, matched by item NAME rather than id:
     * each store keeps its own item rows, so ids never line up between stores.
     */
    public function copyFrom(Request $request, Store $store)
    {
        $this->authorizeStore($store);

        $data = $request->validate([
            'source_store_id' => ['required', 'integer', 'different:store_id'],
            'overwrite_existing' => ['nullable', 'boolean'],
        ]);

        $sourceId = (int) $data['source_store_id'];

        if ($sourceId === $store->id || ! in_array($sourceId, auth()->user()->getAccessibleStoreIds(), true)) {
            return back()->with('error', 'Pick a different store you have access to.');
        }

        $overwrite = (bool) ($data['overwrite_existing'] ?? false);

        $sourceTargets = StoreInventoryTarget::with('inventoryItem')
            ->where('store_id', $sourceId)
            ->get()
            ->filter(fn ($t) => $t->inventoryItem !== null)
            ->keyBy(fn ($t) => mb_strtolower($t->inventoryItem->name));

        $destinationItems = InventoryItem::where('store_id', $store->id)->get();
        $existing = StoreInventoryTarget::where('store_id', $store->id)
            ->pluck('inventory_item_id')
            ->all();

        $copied = 0;
        $skipped = 0;
        $unmatched = [];

        DB::transaction(function () use ($destinationItems, $sourceTargets, $store, $existing, $overwrite, &$copied, &$skipped) {
            foreach ($destinationItems as $item) {
                $source = $sourceTargets->get(mb_strtolower($item->name));

                if (! $source) {
                    continue;
                }

                if (! $overwrite && in_array($item->id, $existing, true)) {
                    $skipped++;

                    continue;
                }

                StoreInventoryTarget::updateOrCreate(
                    ['store_id' => $store->id, 'inventory_item_id' => $item->id],
                    [
                        'target_stock_level' => $source->target_stock_level,
                        'min_stock_level' => $source->min_stock_level,
                    ]
                );
                $copied++;
            }
        });

        // Items the source store has targets for that this store does not carry.
        $destinationNames = $destinationItems->map(fn ($i) => mb_strtolower($i->name))->all();
        foreach ($sourceTargets as $name => $target) {
            if (! in_array($name, $destinationNames, true)) {
                $unmatched[] = $target->inventoryItem->name;
            }
        }

        $sourceName = Store::find($sourceId)?->store_info ?? 'the other store';
        $message = "Copied {$copied} target(s) from {$sourceName}.";

        if ($skipped > 0) {
            $message .= " {$skipped} item(s) already had a target and were left alone.";
        }

        if (! empty($unmatched)) {
            $message .= ' '.count($unmatched).' item(s) on the other store are not in this store\'s list: '
                .implode(', ', array_slice($unmatched, 0, 5))
                .(count($unmatched) > 5 ? '...' : '');
        }

        return back()->with('success', $message);
    }

    // ---- helpers -----------------------------------------------------------

    private function itemsFor(Store $store, Request $request)
    {
        $query = InventoryItem::with('inventoryCategory')
            ->where('store_id', $store->id)
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

    /** Stores the user can copy targets from, which is every other store they can see. */
    private function copyableStores(Store $store)
    {
        return Store::whereIn('id', auth()->user()->getAccessibleStoreIds())
            ->where('id', '!=', $store->id)
            ->orderBy('store_info')
            ->get();
    }

    private function authorizeStore(Store $store): void
    {
        abort_unless(
            in_array($store->id, auth()->user()->getAccessibleStoreIds(), true),
            403,
            'You do not have access to that store.'
        );
    }
}
