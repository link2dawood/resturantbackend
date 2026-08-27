<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\VendorPrice;
use Illuminate\Support\Collection;

/**
 * Phase 5.7 — vendor price comparison. "Current" price for a (vendor, item) is
 * the latest effective_date. To pick the cheapest vendor fairly, every quote is
 * normalized to a per-base-unit price via UnitConverter (a $/case quote and a
 * $/oz quote are compared on the same footing).
 */
class PriceComparisonService
{
    /**
     * @param  iterable<int>  $itemIds
     * @return Collection<int, Collection<int, VendorPrice>>  item_id => (vendor_id => latest VendorPrice)
     */
    public function currentPrices(iterable $itemIds): Collection
    {
        return VendorPrice::whereIn('inventory_item_id', collect($itemIds)->all())
            ->orderByDesc('effective_date')->orderByDesc('id')
            ->get()
            ->groupBy('inventory_item_id')
            ->map(fn ($rows) => $rows->groupBy('vendor_id')->map(fn ($v) => $v->first()));
    }

    /** Per-base-unit price for a quote, or null if its unit can't convert. */
    public function perBase(VendorPrice $price, InventoryItem $item): ?float
    {
        try {
            $baseUnitsPerPriceUnit = UnitConverter::toBase($item, 1.0, $price->price_unit);
        } catch (UnitMismatchException) {
            return null;
        }

        return $baseUnitsPerPriceUnit > 0 ? (float) $price->price / $baseUnitsPerPriceUnit : null;
    }

    /**
     * Cheapest vendor id for an item, given its (vendor_id => VendorPrice) map.
     *
     * @param  Collection<int, VendorPrice>  $vendorPrices
     */
    public function cheapestVendorId(InventoryItem $item, Collection $vendorPrices): ?int
    {
        $bestVendor = null;
        $bestPer = null;

        foreach ($vendorPrices as $vendorId => $price) {
            $per = $this->perBase($price, $item);
            if ($per === null) {
                continue;
            }
            if ($bestPer === null || $per < $bestPer) {
                $bestPer = $per;
                $bestVendor = (int) $vendorId;
            }
        }

        return $bestVendor;
    }

    /**
     * The price that was in force before the current one, per (item, vendor).
     * Used to show which way a price moved since it was last entered.
     *
     * @param  iterable<int>  $itemIds
     * @return Collection<int, Collection<int, VendorPrice>>  item_id => (vendor_id => previous VendorPrice)
     */
    public function previousPrices(iterable $itemIds): Collection
    {
        return VendorPrice::whereIn('inventory_item_id', collect($itemIds)->all())
            ->orderByDesc('effective_date')->orderByDesc('id')
            ->get()
            ->groupBy('inventory_item_id')
            ->map(fn ($rows) => $rows->groupBy('vendor_id')
                ->map(fn ($v) => $v->skip(1)->first())
                ->filter());
    }

    /**
     * Percentage move from the previous price to the current one. Positive means
     * it went up. Null when there is nothing to compare against.
     */
    public function changePercent(?VendorPrice $current, ?VendorPrice $previous): ?float
    {
        if ($current === null || $previous === null || (float) $previous->price == 0.0) {
            return null;
        }

        return round(((float) $current->price - (float) $previous->price) / (float) $previous->price * 100, 1);
    }

    /**
     * One comparison row per item: every vendor's current price normalized per
     * base unit, the cheapest vendor, and how each price moved.
     *
     * @param  \Illuminate\Support\Collection<int, InventoryItem>  $items
     * @param  \Illuminate\Support\Collection<int, \App\Models\Vendor>  $vendors
     * @return list<array<string, mixed>>
     */
    public function comparisonRows($items, $vendors): array
    {
        $itemIds = $items->pluck('id');
        $current = $this->currentPrices($itemIds);
        $previous = $this->previousPrices($itemIds);

        $rows = [];

        foreach ($items as $item) {
            $itemCurrent = $current->get($item->id) ?? collect();
            $itemPrevious = $previous->get($item->id) ?? collect();
            $cheapestVendorId = $this->cheapestVendorId($item, $itemCurrent);

            $cells = [];
            $pricedCount = 0;
            $cheapestPerBase = null;

            foreach ($vendors as $vendor) {
                $price = $itemCurrent->get($vendor->id);
                $perBase = $price ? $this->perBase($price, $item) : null;

                if ($price) {
                    $pricedCount++;
                }

                if ($vendor->id === $cheapestVendorId) {
                    $cheapestPerBase = $perBase;
                }

                $cells[$vendor->id] = [
                    'price' => $price ? (float) $price->price : null,
                    'price_unit' => $price?->price_unit,
                    'effective_date' => $price?->effective_date,
                    'per_base' => $perBase,
                    'is_cheapest' => $price !== null && $vendor->id === $cheapestVendorId,
                    'change_pct' => $this->changePercent($price, $itemPrevious->get($vendor->id)),
                ];
            }

            $rows[] = [
                'item' => $item,
                'cells' => $cells,
                'cheapest_vendor_id' => $cheapestVendorId,
                'cheapest_per_base' => $cheapestPerBase,
                'priced_count' => $pricedCount,
                // Yellow-flagged on the comparison screen.
                'has_no_price' => $pricedCount === 0,
                'is_preferred_cheapest' => $cheapestVendorId !== null
                    && $item->preferred_vendor_id === $cheapestVendorId,
            ];
        }

        return $rows;
    }
}
