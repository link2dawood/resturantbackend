<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\Inventory\OrderSuggestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 Part 1 Task 8 — the manager's Monday-morning count.
 *
 * Backed by inventory_stock, the same table the variance engine reads, so a
 * count entered here is immediately visible to every downstream number.
 *
 * Week rules:
 *  - the current week (Mon-Sun) is the default
 *  - a future week can never be counted
 *  - a past week stays editable until it is submitted, after which only an
 *    admin can unlock it
 */
class WeeklyCountController extends Controller
{
    public function __construct(private OrderSuggestionService $suggestions)
    {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->resolveWeek($request);

        $this->openWeek($store, $week);

        $rows = $this->rowsFor($store, $week);
        $previous = $this->previousWeekCounts($store, $week);
        $submitted = $this->weekIsSubmitted($store, $week);

        $groupBy = $request->get('group_by') === 'vendor' ? 'vendor' : 'category';
        $groups = $groupBy === 'vendor' ? $this->groupByVendor($rows) : $this->groupByCategory($rows);

        return view('inventory.weekly-count.index', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'week' => $week,
            'weekEnd' => $week->copy()->endOfWeek(Carbon::SUNDAY),
            'groups' => $groups,
            'groupBy' => $groupBy,
            'previous' => $previous,
            'submitted' => $submitted,
            'editable' => $this->canEdit($store, $week),
            'isFutureWeek' => $this->isFutureWeek($week),
            'canUnlock' => auth()->user()->isAdmin(),
            'totalItems' => $rows->count(),
            'countedItems' => $rows->filter(fn ($r) => $r->counted_at !== null)->count(),
            'previousWeekUrl' => $this->weekUrl($store, $week->copy()->subWeek(), $groupBy),
            'nextWeekUrl' => $this->isFutureWeek($week->copy()->addWeek())
                ? null
                : $this->weekUrl($store, $week->copy()->addWeek(), $groupBy),
        ]);
    }

    /**
     * Auto-save. Called every 30 seconds by the page and on navigate-away, so it
     * must be cheap, idempotent, and must never flip the week into submitted.
     */
    public function autosave(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->resolveWeek($request);

        if ($error = $this->editabilityError($store, $week)) {
            return response()->json(['error' => $error], 422);
        }

        $data = $request->validate([
            'counts' => ['required', 'array'],
            'counts.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
        ]);

        $saved = $this->applyCounts($store, $week, $data['counts'], $data['notes'] ?? [], submit: false);
        $rows = $this->rowsFor($store, $week);

        return response()->json([
            'message' => $saved.' '.str('line')->plural($saved).' saved',
            'saved' => $saved,
            'counted_items' => $rows->filter(fn ($r) => $r->counted_at !== null)->count(),
            'total_items' => $rows->count(),
            'saved_at' => now()->format('g:i:s A'),
        ]);
    }

    /**
     * Final submit. Locks the week and closes the prior week for variance, the
     * same back-fill the older entry screen does.
     */
    public function submit(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->resolveWeek($request);

        if ($error = $this->editabilityError($store, $week)) {
            return back()->with('error', $error);
        }

        $data = $request->validate([
            'counts' => ['required', 'array'],
            'counts.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'array'],
            'notes.*' => ['nullable', 'string', 'max:500'],
        ]);

        $this->applyCounts($store, $week, $data['counts'], $data['notes'] ?? [], submit: true);

        $rows = $this->rowsFor($store, $week);
        $uncounted = $rows->filter(fn ($r) => $r->counted_at === null)->count();

        // Straight on to the order suggestions: counting and ordering are one
        // Monday-morning job, not two.
        return redirect()
            ->route('inventory.weekly-count.suggestions', ['store_id' => $store->id, 'week' => $week->toDateString()])
            ->with('success', $uncounted > 0
                ? "Week submitted and locked. {$uncounted} item(s) were left uncounted."
                : 'Week submitted and locked. Every item was counted.');
    }

    /**
     * Order suggestions for the week: target minus what was counted, per item.
     * Readable whether or not the week has been submitted, so a manager can
     * preview where the order is heading mid-count.
     */
    public function suggestions(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->resolveWeek($request);

        $rows = $this->suggestions->generateSuggestions($store, $week);

        return view('inventory.weekly-count.suggestions', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'week' => $week,
            'weekEnd' => $week->copy()->endOfWeek(Carbon::SUNDAY),
            'rows' => $rows,
            'needingOrder' => $rows->where('needs_order', true)->count(),
            'withoutTarget' => $rows->where('has_target', false)->count(),
            'uncounted' => $rows->where('is_counted', false)->count(),
            'vendors' => Vendor::where('is_active', true)->orderBy('vendor_name')->get(),
            'submitted' => $this->weekIsSubmitted($store, $week),
        ]);
    }

    /**
     * Turn the suggestions screen into draft orders, one per vendor. Whatever
     * the manager typed wins, and the line records what was suggested so an
     * override stays visible afterwards.
     */
    public function generateOrder(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->resolveWeek($request);

        $data = $request->validate([
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'vendors' => ['nullable', 'array'],
            'vendors.*' => ['nullable', 'integer', 'exists:vendors,id'],
        ]);

        $suggestions = $this->suggestions->generateSuggestions($store, $week)->keyBy(fn ($row) => $row['item']->id);
        $byVendor = [];
        $overrides = 0;

        foreach ($data['quantities'] as $itemId => $quantity) {
            $row = $suggestions->get((int) $itemId);

            // An id the manager cannot see is simply not in the suggestion set.
            if (! $row || ! filled($quantity) || (float) $quantity <= 0) {
                continue;
            }

            $vendorId = (int) ($data['vendors'][$itemId] ?? 0) ?: $row['preferred_vendor']?->id;

            if (! $vendorId) {
                continue;
            }

            $ordered = round((float) $quantity, 4);
            $suggested = (float) $row['suggested_order'];
            $isOverride = abs($ordered - $suggested) >= 0.0001;
            $overrides += $isOverride ? 1 : 0;

            $byVendor[$vendorId][] = [
                'inventory_item_id' => $row['item']->id,
                'quantity' => $ordered,
                'suggested_quantity' => $suggested,
                'is_manual_override' => $isOverride,
                'unit' => $row['unit'],
            ];
        }

        if (empty($byVendor)) {
            return back()->with('error', 'Nothing to order. Set a quantity on at least one item with a vendor.');
        }

        $created = 0;
        DB::transaction(function () use ($store, $week, $byVendor, &$created) {
            // Rebuild only the drafts for this week; placed and received orders
            // are history and are left alone.
            Order::where('store_id', $store->id)
                ->whereDate('week_start_date', $week->toDateString())
                ->where('order_sequence', 1)
                ->where('status', 'draft')
                ->delete();

            foreach ($byVendor as $vendorId => $lines) {
                $order = Order::create([
                    'store_id' => $store->id,
                    'vendor_id' => $vendorId,
                    'week_start_date' => $week->toDateString(),
                    'order_sequence' => 1,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);

                foreach ($lines as $line) {
                    $order->items()->create($line);
                }

                $created++;
            }
        });

        return redirect()
            ->route('admin.orders.index', ['store_id' => $store->id])
            ->with('success', sprintf(
                '%d draft order%s created%s.',
                $created,
                $created === 1 ? '' : 's',
                $overrides > 0 ? ", {$overrides} line(s) overridden" : ''
            ));
    }

    /** Admin-only: reopen a submitted week so a mistake can be corrected. */
    public function unlock(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only an admin can unlock a submitted week.');

        $store = $this->resolveStore($request);
        $week = $this->resolveWeek($request);

        InventoryStock::where('store_id', $store->id)
            ->forWeek($week->toDateString())
            ->update(['status' => InventoryStock::STATUS_DRAFT]);

        return back()->with('success', 'Week unlocked for editing.');
    }

    // ---- week + permission rules ------------------------------------------

    private function currentWeek(): Carbon
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    private function resolveWeek(Request $request): Carbon
    {
        $requested = $request->input('week');

        if (! $requested) {
            return $this->currentWeek();
        }

        try {
            return Carbon::parse($requested)->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            return $this->currentWeek();
        }
    }

    private function isFutureWeek(Carbon $week): bool
    {
        return $week->greaterThan($this->currentWeek());
    }

    private function weekIsSubmitted(Store $store, Carbon $week): bool
    {
        return InventoryStock::where('store_id', $store->id)
            ->forWeek($week->toDateString())
            ->submitted()
            ->exists();
    }

    private function canEdit(Store $store, Carbon $week): bool
    {
        return $this->editabilityError($store, $week) === null;
    }

    /** The reason this week cannot be edited, or null when it can. */
    private function editabilityError(Store $store, Carbon $week): ?string
    {
        if ($this->isFutureWeek($week)) {
            return 'That week has not started yet. You cannot count ahead.';
        }

        if ($this->weekIsSubmitted($store, $week)) {
            return 'This week has been submitted and is locked. An admin can unlock it.';
        }

        return null;
    }

    // ---- data --------------------------------------------------------------

    /** Create any missing rows for the week, seeding from the prior week's close. */
    private function openWeek(Store $store, Carbon $week): void
    {
        if ($this->isFutureWeek($week)) {
            return;
        }

        $weekStr = $week->toDateString();
        $priorStr = $week->copy()->subWeek()->toDateString();

        $existing = InventoryStock::where('store_id', $store->id)
            ->forWeek($weekStr)
            ->pluck('inventory_item_id')
            ->all();

        $items = InventoryItem::where('store_id', $store->id)
            ->where('is_active', true)
            ->whereNotIn('id', $existing)
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $priorEndings = InventoryStock::where('store_id', $store->id)
            ->forWeek($priorStr)
            ->pluck('actual_ending_stock', 'inventory_item_id');

        foreach ($items as $item) {
            InventoryStock::create([
                'inventory_item_id' => $item->id,
                'store_id' => $store->id,
                'week_start_date' => $weekStr,
                'starting_stock' => $priorEndings[$item->id] ?? 0,
                'actual_ending_stock' => null,
                'status' => InventoryStock::STATUS_DRAFT,
            ]);
        }
    }

    private function rowsFor(Store $store, Carbon $week)
    {
        return InventoryStock::with([
            'inventoryItem.inventoryCategory',
            'inventoryItem.preferredVendor',
        ])
            ->where('store_id', $store->id)
            ->forWeek($week->toDateString())
            ->whereHas('inventoryItem', fn ($q) => $q->where('is_active', true))
            ->get()
            ->filter(fn ($r) => $r->inventoryItem !== null)
            ->values();
    }

    /** Last week's counted quantity per item, shown greyed beside each input. */
    private function previousWeekCounts(Store $store, Carbon $week)
    {
        return InventoryStock::where('store_id', $store->id)
            ->forWeek($week->copy()->subWeek()->toDateString())
            ->whereNotNull('counted_at')
            ->pluck('starting_stock', 'inventory_item_id');
    }

    /** Order-guide sequence: Meats first, then Breads, and so on. */
    private function groupByCategory($rows)
    {
        return $rows
            ->sortBy([
                fn ($a, $b) => ($a->inventoryItem->inventoryCategory?->display_order ?? PHP_INT_MAX)
                    <=> ($b->inventoryItem->inventoryCategory?->display_order ?? PHP_INT_MAX),
                fn ($a, $b) => strcasecmp($a->inventoryItem->name, $b->inventoryItem->name),
            ])
            ->groupBy(fn ($r) => $r->inventoryItem->inventoryCategory?->name
                ?? $r->inventoryItem->category
                ?? 'Uncategorised');
    }

    /**
     * The client's paper guide is one sheet per vendor, so this mirrors it:
     * items grouped under the vendor they are ordered from.
     */
    private function groupByVendor($rows)
    {
        return $rows
            ->sortBy([
                fn ($a, $b) => strcasecmp(
                    $a->inventoryItem->preferredVendor?->vendor_name ?? 'zzz',
                    $b->inventoryItem->preferredVendor?->vendor_name ?? 'zzz'
                ),
                fn ($a, $b) => strcasecmp($a->inventoryItem->name, $b->inventoryItem->name),
            ])
            ->groupBy(fn ($r) => $r->inventoryItem->preferredVendor?->vendor_name ?? 'No vendor assigned');
    }

    /**
     * Write the counts. A blank input is left alone rather than stored as zero,
     * because "not counted yet" and "counted, none on hand" are different facts.
     */
    private function applyCounts(Store $store, Carbon $week, array $counts, array $notes, bool $submit): int
    {
        $rows = $this->rowsFor($store, $week)->keyBy('id');
        $priorStr = $week->copy()->subWeek()->toDateString();
        $saved = 0;

        DB::transaction(function () use ($rows, $counts, $notes, $submit, $priorStr, &$saved) {
            foreach ($rows as $row) {
                $hasCount = array_key_exists($row->id, $counts)
                    && $counts[$row->id] !== null
                    && $counts[$row->id] !== '';
                $hasNote = array_key_exists($row->id, $notes);

                if (! $hasCount && ! $hasNote && ! $submit) {
                    continue;
                }

                if ($hasCount) {
                    $count = (float) $counts[$row->id];
                    $row->starting_stock = $count;
                    $row->counted_by = auth()->id();
                    $row->counted_at = now();

                    // The count also closes the prior week for variance, unless
                    // that week was already counted.
                    InventoryStock::where('inventory_item_id', $row->inventory_item_id)
                        ->forWeek($priorStr)
                        ->whereNull('actual_ending_stock')
                        ->update(['actual_ending_stock' => $count]);
                }

                if ($hasNote) {
                    $row->notes = filled($notes[$row->id]) ? $notes[$row->id] : null;
                }

                if ($submit) {
                    $row->status = InventoryStock::STATUS_SUBMITTED;
                }

                if ($row->isDirty()) {
                    $row->save();
                    $saved++;
                }
            }
        });

        return $saved;
    }

    // ---- store scoping -----------------------------------------------------

    private function resolveStore(Request $request): Store
    {
        $accessible = auth()->user()->getAccessibleStoreIds();
        abort_if(empty($accessible), 403, 'You are not assigned to a store.');

        // Employees and managers are pinned to their own store; admin/owner pick.
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

        return Store::whereIn('id', auth()->user()->getAccessibleStoreIds())
            ->orderBy('store_info')
            ->get();
    }

    private function weekUrl(Store $store, Carbon $week, string $groupBy): string
    {
        return route('inventory.weekly-count.index', [
            'store_id' => $store->id,
            'week' => $week->toDateString(),
            'group_by' => $groupBy,
        ]);
    }
}
