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
}
