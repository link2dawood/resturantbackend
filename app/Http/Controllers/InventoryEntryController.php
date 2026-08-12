<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryEntryRequest;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Phase 5 — weekly inventory entry (the Monday-morning workflow). Staff enter the
 * on-hand count per item for the current week. The count is stored as this week's
 * starting stock AND, on submit, back-filled as the prior week's actual ending
 * (which closes that week for variance). Employees may edit only until the
 * week's Monday end-of-day cutoff; managers/owners/admins can correct it after.
 */
class InventoryEntryController extends Controller
{
    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->currentWeek();
        $this->ensureWeekRows($store, $week);

        $rows = InventoryStock::with('inventoryItem')
            ->where('store_id', $store->id)
            ->whereDate('week_start_date', $week->toDateString())
            ->whereHas('inventoryItem', fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn ($r) => strtolower(($r->inventoryItem->category ?? '').'|'.($r->inventoryItem->name ?? '')))
            ->values();

        $grouped = $rows->groupBy(fn ($r) => $r->inventoryItem->category);
        $locked = $this->isLocked($week);
        $stores = $this->storeOptions();

        return view('inventory.entry.index', compact('store', 'week', 'grouped', 'locked', 'stores'));
    }

    public function saveDraft(InventoryEntryRequest $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->currentWeek();
        $this->guardNotLocked($week);
        $this->applyCounts($request, $store, $week, submit: false);

        return back()->with('success', 'Draft saved.');
    }

    public function submit(InventoryEntryRequest $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->currentWeek();
        $this->guardNotLocked($week);
        $this->applyCounts($request, $store, $week, submit: true);

        return back()->with('success', 'Inventory count submitted.');
    }

    private function currentWeek(): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    /** Employees may edit only through the week's Monday end-of-day cutoff. */
    private function isLocked(Carbon $week): bool
    {
        return auth()->user()->isEmployee() && now()->greaterThan($week->copy()->endOfDay());
    }

    private function guardNotLocked(Carbon $week): void
    {
        abort_if($this->isLocked($week), 403, 'The inventory count for this week is closed (past the Monday cutoff).');
    }

    private function resolveStore(Request $request): Store
    {
        $accessible = auth()->user()->getAccessibleStoreIds();
        abort_if(empty($accessible), 403, 'You are not assigned to a store.');

        // Employees/managers are pinned to their own store; admin/owner may pick.
        if (auth()->user()->isEmployee() || auth()->user()->isManager()) {
            $storeId = $accessible[0];
        } else {
            $requested = (int) $request->input('store_id');
            $storeId = in_array($requested, $accessible, true) ? $requested : $accessible[0];
        }

        return Store::findOrFail($storeId);
    }

    private function storeOptions()
    {
        if (auth()->user()->isEmployee() || auth()->user()->isManager()) {
            return collect();
        }

        return Store::whereIn('id', auth()->user()->getAccessibleStoreIds())->get();
    }

    /** Lazily create any missing rows for the store/week (in case the job hasn't run). */
    private function ensureWeekRows(Store $store, Carbon $week): void
    {
        $weekStr = $week->toDateString();
        $prevStr = $week->copy()->subWeek()->toDateString();

        $existing = InventoryStock::where('store_id', $store->id)
            ->whereDate('week_start_date', $weekStr)
            ->pluck('inventory_item_id')
            ->all();

        $items = InventoryItem::where('store_id', $store->id)
            ->where('is_active', true)
            ->whereNotIn('id', $existing)
            ->get();

        foreach ($items as $item) {
            $priorEnding = InventoryStock::where('inventory_item_id', $item->id)
                ->whereDate('week_start_date', $prevStr)
                ->value('actual_ending_stock');

            InventoryStock::create([
                'inventory_item_id' => $item->id,
                'store_id' => $store->id,
                'week_start_date' => $weekStr,
                'starting_stock' => $priorEnding ?? 0,
                'actual_ending_stock' => null,
                'status' => 'draft',
            ]);
        }
    }

    private function applyCounts(InventoryEntryRequest $request, Store $store, Carbon $week, bool $submit): void
    {
        $counts = $request->validated()['counts'] ?? [];
        $prevStr = $week->copy()->subWeek()->toDateString();

        $rows = InventoryStock::where('store_id', $store->id)
            ->whereDate('week_start_date', $week->toDateString())
            ->get()
            ->keyBy('id');

        foreach ($counts as $stockId => $value) {
            $row = $rows->get((int) $stockId);
            if (! $row || $value === null || $value === '') {
                continue;
            }
            $count = (float) $value;

            // The on-hand count is this week's starting stock…
            $row->starting_stock = $count;
            if ($submit) {
                $row->status = 'submitted';
                $row->counted_by = auth()->id();
                $row->counted_at = now();
            }
            $row->save();

            // …and it closes the PRIOR week (its actual ending) for variance,
            // unless that week was already counted.
            if ($submit) {
                InventoryStock::where('inventory_item_id', $row->inventory_item_id)
                    ->whereDate('week_start_date', $prevStr)
                    ->whereNull('actual_ending_stock')
                    ->update(['actual_ending_stock' => $count]);
            }
        }
    }
}
