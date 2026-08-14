<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MenuItemSold;
use App\Models\OrderItem;
use App\Models\Recipe;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Computes weekly inventory variance for one item at one store — the "most
 * important module." Pure and deterministic: it reads inputs and returns a
 * VarianceLine; it never writes. See
 * docs/features/inventory-variance/variance-formula.md for the exact math.
 */
class VarianceCalculationService
{
    public function __construct(
        private float $greenThreshold = 2.0,
        private float $yellowThreshold = 5.0,
    ) {
    }

    public function calculate(int $storeId, int $itemId, string|CarbonInterface $weekStart): VarianceLine
    {
        $week = Carbon::parse($weekStart)->toDateString();
        $item = InventoryItem::findOrFail($itemId);

        $stock = InventoryStock::where('inventory_item_id', $itemId)
            ->whereDate('week_start_date', $week)
            ->first();

        $starting = $stock && $stock->starting_stock !== null ? (float) $stock->starting_stock : null;
        $actualEnding = $stock && $stock->actual_ending_stock !== null ? (float) $stock->actual_ending_stock : null;

        $ordered = $this->orderedBaseQty($storeId, $item, $week);
        [$usage, $warnings] = $this->theoreticalUsage($storeId, $itemId, $week);

        $available = ($starting ?? 0.0) + $ordered;
        $theoreticalEnding = $available - $usage;

        $incomplete = $starting === null || $actualEnding === null;

        $variance = null;
        $variancePct = null;
        $severity = null;
        if (! $incomplete) {
            $variance = $theoreticalEnding - $actualEnding;
            $variancePct = $available == 0.0 ? 0.0 : $variance / $available * 100.0;
            $severity = $this->severityFor($variancePct);
        }

        return new VarianceLine(
            inventoryItemId: $itemId,
            baseUnit: $item->base_unit,
            startingStock: $starting ?? 0.0,
            orderedQty: $ordered,
            totalAvailable: $available,
            theoreticalUsage: $usage,
            theoreticalEnding: $theoreticalEnding,
            actualEnding: $actualEnding,
            variance: $variance,
            variancePct: $variancePct,
            severity: $severity,
            isIncomplete: $incomplete,
            warnings: $warnings,
        );
    }

    /** Convert a quantity to the item's base unit via an EXPLICIT factor, or throw. */
    public function convertToBase(float $qty, string $unit, InventoryItem $item): float
    {
        return UnitConverter::toBase($item, $qty, $unit);
    }

    /**
     * Theoretical usage (base units) of one item at one store for a week — the
     * same calculation the variance uses, exposed for the stock-up engine so
     * usage is computed in exactly one place.
     */
    public function usage(int $storeId, int $itemId, string|CarbonInterface $week): float
    {
        [$usage] = $this->theoreticalUsage($storeId, $itemId, Carbon::parse($week)->toDateString());

        return $usage;
    }

    /** Green ≤ green threshold; yellow ≤ yellow threshold; red above (by |%|). */
    public function severityFor(float $variancePct): string
    {
        $abs = abs($variancePct);

        if ($abs <= $this->greenThreshold) {
            return 'green';
        }
        if ($abs <= $this->yellowThreshold) {
            return 'yellow';
        }

        return 'red';
    }

    /** Sum of RECEIVED order quantities for the item this week, in base unit. */
    private function orderedBaseQty(int $storeId, InventoryItem $item, string $week): float
    {
        $rows = OrderItem::query()
            ->where('inventory_item_id', $item->id)
            ->whereHas('order', function ($q) use ($storeId, $week) {
                $q->where('store_id', $storeId)
                    ->whereDate('week_start_date', $week)
                    ->where('status', 'received');
            })
            ->get();

        $total = 0.0;
        foreach ($rows as $row) {
            $total += $this->convertToBase((float) $row->quantity, $row->unit, $item);
        }

        return $total;
    }

    /**
     * Theoretical usage = Σ (qty sold × portion of this item in the sold menu
     * item's current recipe for its size variant). Unmatched sales / missing
     * recipes are excluded and reported as warnings.
     *
     * @return array{0: float, 1: string[]}
     */
    private function theoreticalUsage(int $storeId, int $itemId, string $week): array
    {
        $sold = MenuItemSold::where('store_id', $storeId)
            ->whereDate('week_start_date', $week)
            ->get();

        $usage = 0.0;
        $warnings = [];

        foreach ($sold as $row) {
            if (! $row->is_matched || $row->menu_item_id === null) {
                $warnings[] = "Unmatched Square item '{$row->square_raw_name}' excluded from usage.";
                continue;
            }

            $recipe = Recipe::where('menu_item_id', $row->menu_item_id)
                ->where('size_variant', $row->size_variant)
                ->where('is_current', true)
                ->first();

            if (! $recipe) {
                $warnings[] = "No current recipe for menu item {$row->menu_item_id} ({$row->size_variant}); usage may be understated.";
                continue;
            }

            $ingredient = $recipe->ingredients()->where('inventory_item_id', $itemId)->first();
            if (! $ingredient) {
                continue; // this recipe does not use the item — normal, not a warning
            }

            $usage += (float) $row->quantity_sold * (float) $ingredient->quantity_base;
        }

        return [$usage, $warnings];
    }
}
