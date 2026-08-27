<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;

/**
 * The single, explicit unit-conversion rule for inventory quantities. Used by
 * both the variance engine and recipe entry so units are never mixed silently.
 * A quantity may be expressed in the item's base unit or its purchase unit;
 * anything else is rejected.
 */
class UnitConverter
{
    public static function toBase(InventoryItem $item, float $qty, string $unit): float
    {
        if ($unit === $item->base_unit) {
            return $qty;
        }
        if ($unit === $item->purchase_unit) {
            return $qty * (float) $item->units_per_purchase;
        }

        throw new UnitMismatchException(
            "No conversion from '{$unit}' to base unit '{$item->base_unit}' for inventory item {$item->id}."
        );
    }
}
