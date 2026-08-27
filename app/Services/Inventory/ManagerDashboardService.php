<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Phase 5 Part 1 Task 13 — the four manager dashboard widgets, kept out of the
 * controller so each one can be tested for accuracy on its own.
 */
class ManagerDashboardService
{
    /** Widget 1: how far through this week's count the store is. */
    public function countStatus(Store $store, Carbon $week): array
    {
        $rows = InventoryStock::where('store_id', $store->id)
            ->forWeek($week->toDateString())
            ->whereHas('inventoryItem', fn ($q) => $q->where('is_active', true))
            ->get();

        $activeItems = InventoryItem::where('store_id', $store->id)->where('is_active', true)->count();

        // Rows are opened lazily by the count screen, so before anyone visits it
        // there are none. Fall back to the item count so the denominator is not
        // a misleading zero.
        $total = $rows->count() > 0 ? $rows->count() : $activeItems;
        $counted = $rows->filter(fn ($row) => $row->counted_at !== null)->count();

        return [
            'total' => $total,
            'counted' => $counted,
            'submitted' => $rows->contains(fn ($row) => $row->status === InventoryStock::STATUS_SUBMITTED),
            'started' => $counted > 0,
            'percent' => $total > 0 ? (int) round($counted / $total * 100) : 0,
        ];
    }

    /**
     * Widget 2: orders still needing attention. A draft has not been sent; a
     * placed order has not arrived. Received and cancelled are done with.
     */
    public function pendingOrders(Store $store, Carbon $week): Collection
    {
        return Order::with('vendor')
            ->where('store_id', $store->id)
            ->whereIn('status', [Order::STATUS_DRAFT, Order::STATUS_PLACED])
            ->whereDate('week_start_date', '>=', $week->copy()->subWeeks(4)->toDateString())
            ->orderByDesc('week_start_date')
            ->orderBy('order_sequence')
            ->get();
    }

    /**
     * Widget 3: items at or below their reorder point.
     *
     * The two reorder points live in different units, so both are handled
     * explicitly rather than being averaged into a wrong answer:
     *   store_inventory_targets.min_stock_level is in PURCHASE units
     *   inventory_items.min_stock_level        is in BASE units
     * The per-store target wins when there is one.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function lowStock(Store $store, Carbon $week): Collection
    {
        $items = InventoryItem::with('inventoryCategory')
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->get();

        if ($items->isEmpty()) {
            return collect();
        }

        $counts = InventoryStock::where('store_id', $store->id)
            ->forWeek($week->toDateString())
            ->whereNotNull('counted_at')
            ->get()
            ->keyBy('inventory_item_id');

        $targets = StoreInventoryTarget::where('store_id', $store->id)
            ->get()
            ->keyBy('inventory_item_id');

        return $items
            ->map(function (InventoryItem $item) use ($counts, $targets) {
                $count = $counts->get($item->id);

                // An uncounted item is unknown, not low. Flagging it would bury
                // the real alerts under every item nobody has counted yet.
                if ($count === null) {
                    return null;
                }

                $perPurchase = (float) $item->units_per_purchase;
                $onHandBase = (float) $count->starting_stock;
                $onHandPurchase = $perPurchase > 0 ? $onHandBase / $perPurchase : $onHandBase;

                $target = $targets->get($item->id);

                if ($target !== null && $target->min_stock_level !== null) {
                    $threshold = (float) $target->min_stock_level;
                    $onHand = round($onHandPurchase, 2);
                    $unit = $item->purchase_unit;
                } elseif ((float) $item->min_stock_level > 0) {
                    $threshold = (float) $item->min_stock_level;
                    $onHand = round($onHandBase, 2);
                    $unit = $item->base_unit;
                } else {
                    return null; // no reorder point set, nothing to be below
                }

                if ($onHand > $threshold) {
                    return null;
                }

                return [
                    'item' => $item,
                    'on_hand' => $onHand,
                    'threshold' => round($threshold, 2),
                    'unit' => $unit,
                    'shortfall' => round($threshold - $onHand, 2),
                    'is_out' => $onHand <= 0,
                ];
            })
            ->filter()
            ->sortByDesc('shortfall')
            ->values();
    }

    /**
     * Widget 4: the last few things that happened, derived from the timestamps
     * already on the records rather than a separate activity log, so it cannot
     * drift out of step with reality.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function recentActivity(Store $store, int $limit = 5): Collection
    {
        $events = collect();

        $counts = InventoryStock::with('counter')
            ->where('store_id', $store->id)
            ->whereNotNull('counted_at')
            ->orderByDesc('counted_at')
            ->limit(50)
            ->get()
            // One line per week, not one per item: counting 90 items would
            // otherwise flood the feed with 90 identical-looking entries.
            ->groupBy(fn ($row) => $row->week_start_date->toDateString().'|'.$row->status);

        foreach ($counts as $key => $rows) {
            $first = $rows->first();
            [$week] = explode('|', $key);
            $submitted = $first->status === InventoryStock::STATUS_SUBMITTED;

            $events->push([
                'at' => $rows->max('counted_at'),
                'type' => $submitted ? 'count_submitted' : 'count_saved',
                'description' => $submitted
                    ? 'Inventory count submitted for the week of '.Carbon::parse($week)->format('M j')
                    : $rows->count().' item(s) counted for the week of '.Carbon::parse($week)->format('M j'),
                'actor' => $first->counter?->name,
                'url' => route('inventory.weekly-count.index', ['store_id' => $store->id, 'week' => $week]),
            ]);
        }

        $orders = Order::with('vendor')
            ->where('store_id', $store->id)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        foreach ($orders as $order) {
            $vendor = $order->vendor->vendor_name ?? 'a vendor';

            if ($order->received_at) {
                $events->push([
                    'at' => $order->received_at,
                    'type' => 'order_received',
                    'description' => "Order from {$vendor} marked received",
                    'actor' => null,
                    'url' => route('admin.orders.show', $order),
                ]);
            }

            if ($order->placed_at) {
                $events->push([
                    'at' => $order->placed_at,
                    'type' => 'order_placed',
                    'description' => "Order placed with {$vendor}"
                        .($order->total > 0 ? ' for $'.number_format($order->total, 2) : ''),
                    'actor' => null,
                    'url' => route('admin.orders.show', $order),
                ]);
            }

            if ($order->status === Order::STATUS_DRAFT) {
                $events->push([
                    'at' => $order->created_at,
                    'type' => 'order_drafted',
                    'description' => "Draft order created for {$vendor}",
                    'actor' => null,
                    'url' => route('admin.orders.show', $order),
                ]);
            }
        }

        return $events
            ->filter(fn ($event) => $event['at'] !== null)
            ->sortByDesc(fn ($event) => $event['at']->getTimestamp())
            ->take($limit)
            ->values();
    }
}
