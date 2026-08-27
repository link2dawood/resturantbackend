<?php

namespace App\Services\Inventory;

/**
 * Immutable result of a single item's variance calculation for one store/week.
 * All quantities are in the item's base unit. variance/variancePct/severity are
 * null when the line is incomplete (missing starting or ending count).
 */
class VarianceLine
{
    /** @param string[] $warnings */
    public function __construct(
        public readonly int $inventoryItemId,
        public readonly string $baseUnit,
        public readonly float $startingStock,
        public readonly float $orderedQty,
        public readonly float $totalAvailable,
        public readonly float $theoreticalUsage,
        public readonly float $theoreticalEnding,
        public readonly ?float $actualEnding,
        public readonly ?float $variance,
        public readonly ?float $variancePct,
        public readonly ?string $severity,
        public readonly bool $isIncomplete,
        public readonly array $warnings = [],
    ) {
    }

    /** Shape matches variance_report_lines columns for persistence. */
    public function toArray(): array
    {
        return [
            'inventory_item_id' => $this->inventoryItemId,
            'base_unit' => $this->baseUnit,
            'starting_stock' => $this->startingStock,
            'ordered_qty' => $this->orderedQty,
            'total_available' => $this->totalAvailable,
            'theoretical_usage' => $this->theoreticalUsage,
            'theoretical_ending' => $this->theoreticalEnding,
            'actual_ending' => $this->actualEnding,
            'variance' => $this->variance,
            'variance_pct' => $this->variancePct,
            'severity' => $this->severity,
            'is_incomplete' => $this->isIncomplete,
        ];
    }
}
