<?php

namespace App\Services\Inventory;

use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Phase 5 Part 1 Task 12 — "you ordered about 5 boxes of steak a week".
 *
 * Only orders that were actually sent count: a draft is a plan and a cancelled
 * order never happened, so both are excluded. Otherwise a half-built draft
 * sitting on Monday morning would drag every average down.
 *
 * The divisor is the number of DISTINCT WEEKS the item was ordered in, not the
 * number of orders, so a week with an Order 1 and an Order 2 reads as one week's
 * worth rather than halving the average.
 */
class OrderTrendService
{
    /** Orders that represent something the store actually bought. */
    public const COUNTED_STATUSES = [Order::STATUS_PLACED, Order::STATUS_RECEIVED];

    /**
     * Per-item ordering trend across the given orders.
     *
     * @param  Collection<int, Order>  $orders  already filtered and with items loaded
     * @return Collection<int, array{
     *     item_name: string, unit: string, total_quantity: float, weeks: int,
     *     average_per_week: float, order_count: int, last_ordered: ?string
     * }>
     */
    public function perItem(Collection $orders): Collection
    {
        $counted = $orders->filter(fn (Order $order) => in_array($order->status, self::COUNTED_STATUSES, true));

        $byItem = [];

        foreach ($counted as $order) {
            $week = $order->week_start_date->toDateString();

            foreach ($order->items as $line) {
                $itemId = $line->inventory_item_id;

                $byItem[$itemId] ??= [
                    'item_name' => $line->inventoryItem->name ?? 'Item',
                    'unit' => $line->unit,
                    'total_quantity' => 0.0,
                    'weeks' => [],
                    'order_count' => 0,
                    'last_ordered' => null,
                ];

                $byItem[$itemId]['total_quantity'] += (float) $line->quantity;
                $byItem[$itemId]['weeks'][$week] = true;
                $byItem[$itemId]['order_count']++;

                if ($byItem[$itemId]['last_ordered'] === null || $week > $byItem[$itemId]['last_ordered']) {
                    $byItem[$itemId]['last_ordered'] = $week;
                }
            }
        }

        return collect($byItem)
            ->map(function (array $row) {
                $weeks = count($row['weeks']);

                return [
                    'item_name' => $row['item_name'],
                    'unit' => $row['unit'],
                    'total_quantity' => round($row['total_quantity'], 4),
                    'weeks' => $weeks,
                    'average_per_week' => $weeks > 0 ? round($row['total_quantity'] / $weeks, 2) : 0.0,
                    'order_count' => $row['order_count'],
                    'last_ordered' => $row['last_ordered'],
                ];
            })
            ->sortByDesc('average_per_week')
            ->values();
    }

    /**
     * Headline numbers for the filtered window.
     *
     * @param  Collection<int, Order>  $orders
     * @return array<string, mixed>
     */
    public function summary(Collection $orders): array
    {
        $counted = $orders->filter(fn (Order $order) => in_array($order->status, self::COUNTED_STATUSES, true));
        $weeks = $counted->map(fn (Order $order) => $order->week_start_date->toDateString())->unique();

        $spend = $counted->sum(fn (Order $order) => $order->total);

        return [
            'order_count' => $counted->count(),
            'week_count' => $weeks->count(),
            'vendor_count' => $counted->pluck('vendor_id')->filter()->unique()->count(),
            'total_spend' => round((float) $spend, 2),
            'average_weekly_spend' => $weeks->count() > 0 ? round((float) $spend / $weeks->count(), 2) : 0.0,
            // Everything in the window, including the drafts the averages ignore.
            'listed_count' => $orders->count(),
            'excluded_count' => $orders->count() - $counted->count(),
        ];
    }
}
