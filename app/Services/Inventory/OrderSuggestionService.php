<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use App\Models\StoreInventoryTarget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Phase 5 Part 1 Task 9 — the simple stock-up calculator.
 *
 *     suggested order = target stock level - current stock
 *
 * The two sides of that subtraction are held in different units, which is the
 * whole reason this class exists rather than the sum being done inline:
 *
 *   - store_inventory_targets.target_stock_level is in the item's PURCHASE unit
 *     ("Round Rock keeps 15 boxes of steak"), because that is how stock is
 *     talked about and how orders are placed.
 *   - inventory_stock.starting_stock is in the item's BASE unit, because that is
 *     what the weekly count and the variance engine work in.
 *
 * So the count is converted to purchase units before the subtraction, and the
 * answer comes back in purchase units, which is what you actually order.
 *
 * Distinct from StockUpService (Phase 5.5), which projects usage from sales
 * history. This one only ever looks at what is on the shelf against the target.
 */
class OrderSuggestionService
{
    /**
     * One row per active item in the store.
     *
     * @return Collection<int, array{
     *     item: InventoryItem,
     *     current_stock: float,
     *     current_stock_base: float,
     *     target_stock: float,
     *     suggested_order: float,
     *     suggested_order_exact: float,
     *     preferred_vendor: ?\App\Models\Vendor,
     *     has_target: bool,
     *     is_counted: bool,
     *     needs_order: bool,
     *     unit: string,
     * }>
     */
    public function generateSuggestions(Store $store, Carbon $weekStart): Collection
    {
        $week = $weekStart->copy()->startOfWeek(Carbon::MONDAY);

        $items = InventoryItem::with(['preferredVendor', 'inventoryCategory'])
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
            ->whereIn('inventory_item_id', $items->pluck('id'))
            ->get()
            ->keyBy('inventory_item_id');

        return $items
            ->map(fn (InventoryItem $item) => $this->suggestionFor(
                $item,
                $counts->get($item->id),
                $targets->get($item->id)
            ))
            ->sortBy([
                fn ($a, $b) => ($a['item']->inventoryCategory?->display_order ?? PHP_INT_MAX)
                    <=> ($b['item']->inventoryCategory?->display_order ?? PHP_INT_MAX),
                fn ($a, $b) => strcasecmp($a['item']->name, $b['item']->name),
            ])
            ->values();
    }

    /** Only the rows that actually need ordering. */
    public function ordersNeeded(Store $store, Carbon $weekStart): Collection
    {
        return $this->generateSuggestions($store, $weekStart)
            ->filter(fn (array $row) => $row['needs_order'])
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function suggestionFor(InventoryItem $item, ?InventoryStock $count, ?StoreInventoryTarget $target): array
    {
        $perPurchase = (float) $item->units_per_purchase;
        $currentBase = $count !== null ? (float) $count->starting_stock : 0.0;

        // A zero or missing pack size would divide by zero. Treat the item as
        // being counted directly in its purchase unit rather than crashing.
        $currentPurchase = $perPurchase > 0
            ? round($currentBase / $perPurchase, 4)
            : round($currentBase, 4);

        $targetPurchase = $target !== null ? (float) $target->target_stock_level : 0.0;

        // The formula. Never negative: being over target means order nothing,
        // not order a negative amount.
        $exact = round($targetPurchase - $currentPurchase, 4);
        $exact = max(0.0, $exact);

        // You cannot buy part of a box, so round up to whole purchase units.
        $suggested = $exact > 0 ? (float) ceil($exact) : 0.0;

        return [
            'item' => $item,
            'current_stock' => $currentPurchase,
            'current_stock_base' => $currentBase,
            'target_stock' => $targetPurchase,
            'suggested_order' => $suggested,
            'suggested_order_exact' => $exact,
            'preferred_vendor' => $item->preferredVendor,
            'has_target' => $target !== null,
            'is_counted' => $count !== null,
            'needs_order' => $suggested > 0,
            'unit' => $item->purchase_unit,
        ];
    }
}
