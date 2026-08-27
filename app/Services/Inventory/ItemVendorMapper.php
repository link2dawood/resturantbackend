<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\VendorPrice;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 — the single place item/vendor mappings are written.
 *
 * Two rules it exists to guarantee:
 *  1. At most one preferred vendor per item, and inventory_items.preferred_vendor_id
 *     always agrees with the pivot flag. Order generation reads the column
 *     (OrderController), the mapping UI reads the pivot, and they must not drift.
 *  2. A price typed on the mapping table also lands in vendor_prices, so the
 *     comparison screen (PriceComparisonService reads that table, not the pivot)
 *     sees the same number the admin just entered.
 */
class ItemVendorMapper
{
    /**
     * Replace an item's vendor mappings with the given rows.
     *
     * @param  array<int, array{enabled?: bool, vendor_sku?: ?string, current_price?: mixed, is_preferred?: bool}>  $rows
     *         keyed by vendor id
     */
    public function sync(InventoryItem $item, array $rows): void
    {
        DB::transaction(function () use ($item, $rows) {
            $existing = $item->vendors()->get()->keyBy('id');
            $pivot = [];
            $preferredId = null;

            foreach ($rows as $vendorId => $row) {
                $vendorId = (int) $vendorId;

                if (! ($row['enabled'] ?? false)) {
                    continue;
                }

                $price = $this->money($row['current_price'] ?? null);
                $previous = $existing->get($vendorId)?->pivot;
                $priceChanged = $price !== null
                    && ($previous === null || abs((float) $previous->current_price - $price) >= 0.005);

                $pivot[$vendorId] = [
                    'vendor_sku' => filled($row['vendor_sku'] ?? null) ? trim((string) $row['vendor_sku']) : null,
                    'current_price' => $price,
                    // Keep the old stamp when the price did not move, so "last
                    // updated" means what it says.
                    'price_updated_at' => $priceChanged ? now() : $previous?->price_updated_at,
                    'is_preferred_vendor' => false,
                ];

                // Last preferred row wins; the UI sends a radio, so there is one.
                if ($row['is_preferred'] ?? false) {
                    $preferredId = $vendorId;
                }

                if ($priceChanged) {
                    $this->recordPriceHistory($item, $vendorId, $price);
                }
            }

            // Exactly one preferred, and only ever one that is actually enabled.
            if ($preferredId !== null && isset($pivot[$preferredId])) {
                $pivot[$preferredId]['is_preferred_vendor'] = true;
            } else {
                $preferredId = null;
            }

            $item->vendors()->sync($pivot);
            $item->forceFill(['preferred_vendor_id' => $preferredId])->save();
        });
    }

    /**
     * Attach one vendor to many items at once, leaving each item's other vendors
     * alone. Used by the bulk-assign toolbar on the item list.
     *
     * @param  iterable<InventoryItem>  $items
     * @return array{attached: int, updated: int, preferred: int}
     */
    public function assignVendorToItems(iterable $items, int $vendorId, bool $makePreferred = false): array
    {
        $result = ['attached' => 0, 'updated' => 0, 'preferred' => 0];

        DB::transaction(function () use ($items, $vendorId, $makePreferred, &$result) {
            foreach ($items as $item) {
                $already = $item->vendors()->where('vendors.id', $vendorId)->exists();

                if ($already) {
                    $result['updated']++;
                } else {
                    $item->vendors()->attach($vendorId, [
                        'is_preferred_vendor' => false,
                        'current_price' => null,
                        'price_updated_at' => null,
                    ]);
                    $result['attached']++;
                }

                if ($makePreferred) {
                    $this->setPreferred($item, $vendorId);
                    $result['preferred']++;
                }
            }
        });

        return $result;
    }

    /**
     * Make one vendor the preferred one for an item, clearing whichever vendor
     * held the flag before. Pass null to clear it entirely.
     */
    public function setPreferred(InventoryItem $item, ?int $vendorId): void
    {
        DB::transaction(function () use ($item, $vendorId) {
            $item->vendors()->newPivotStatement()
                ->where('inventory_item_id', $item->id)
                ->update(['is_preferred_vendor' => false]);

            if ($vendorId === null) {
                $item->forceFill(['preferred_vendor_id' => null])->save();

                return;
            }

            // A preferred vendor must also be one of the item's vendors.
            if (! $item->vendors()->where('vendors.id', $vendorId)->exists()) {
                $item->vendors()->attach($vendorId, ['is_preferred_vendor' => false]);
            }

            $item->vendors()->updateExistingPivot($vendorId, ['is_preferred_vendor' => true]);
            $item->forceFill(['preferred_vendor_id' => $vendorId])->save();
        });
    }

    /**
     * Mirror a mapping-table price into the dated history the comparison screen
     * reads. Quoted per the item's purchase unit, matching VendorPriceController.
     */
    private function recordPriceHistory(InventoryItem $item, int $vendorId, float $price): void
    {
        $latest = VendorPrice::where('inventory_item_id', $item->id)
            ->where('vendor_id', $vendorId)
            ->orderByDesc('effective_date')->orderByDesc('id')
            ->first();

        if ($latest && abs((float) $latest->price - $price) < 0.005) {
            return;
        }

        VendorPrice::create([
            'vendor_id' => $vendorId,
            'inventory_item_id' => $item->id,
            'price' => $price,
            'price_unit' => $item->purchase_unit,
            'effective_date' => now()->toDateString(),
            'entered_by' => auth()->id(),
        ]);
    }

    private function money(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }
}
