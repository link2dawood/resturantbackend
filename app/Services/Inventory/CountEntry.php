<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;

/**
 * Phase 5 Part 2 — the two-box count entry.
 *
 * The client asked for each item to be counted as whole units plus a partial,
 * because that is how a person reads a shelf: "two boxes and fifteen loose
 * steaks", not "2.283 boxes".
 *
 * What the partial means depends on the item:
 *
 *   Pack size known (steak 53/box):  partial is LOOSE PIECES, 0 to 52.
 *   Pack size unknown (a jug, 1/1):  partial is a FRACTION, 0.25 / 0.5 / 0.75.
 *
 * Both end up as base units, which is the only thing stored, so variance and
 * ordering keep working off one number.
 */
class CountEntry
{
    /** The fractions the count screen offers when there is no pack size. */
    public const FRACTIONS = [0.25, 0.5, 0.75];

    /** Does this item get counted in loose pieces rather than a fraction? */
    public static function countsInPieces(?InventoryItem $item): bool
    {
        return self::packSize($item) > 1;
    }

    /** Base units in one purchase unit, never zero. */
    public static function packSize(?InventoryItem $item): float
    {
        $size = (float) ($item->units_per_purchase ?? 1);

        return $size > 0 ? $size : 1.0;
    }

    /**
     * The largest partial the item accepts.
     *
     * The client set this as the pack size itself: "steak partial cannot exceed
     * 53, hamburger meat cannot exceed 40". A partial of exactly one full pack
     * is therefore allowed and simply totals as another whole unit, which is
     * harmless; anything above it is a miscount and is refused.
     */
    public static function maxPartial(?InventoryItem $item): float
    {
        return self::countsInPieces($item)
            ? self::packSize($item)
            : 0.75;
    }

    /** Whole units plus a partial, as base units for storage. */
    public static function toBaseUnits(?InventoryItem $item, float $whole, float $partial): float
    {
        $pack = self::packSize($item);

        // A piece is already a base unit; a fraction is a fraction of a pack.
        $partialBase = self::countsInPieces($item) ? $partial : $partial * $pack;

        return round(($whole * $pack) + $partialBase, 4);
    }

    /**
     * Split stored base units back into the two boxes, so reopening the screen
     * shows what was typed rather than a decimal the counter never entered.
     *
     * @return array{whole: float, partial: float}
     */
    public static function split(?InventoryItem $item, ?float $baseUnits): array
    {
        if ($baseUnits === null) {
            return ['whole' => null, 'partial' => null];
        }

        $pack = self::packSize($item);
        $whole = (float) floor(round($baseUnits / $pack, 6));
        $remainderBase = round($baseUnits - ($whole * $pack), 4);

        if (self::countsInPieces($item)) {
            return ['whole' => $whole, 'partial' => round($remainderBase, 4)];
        }

        // Fraction of one unit, snapped to the quarters the screen offers so a
        // legacy 0.3 does not come back as an un-tappable value.
        $fraction = $pack > 0 ? $remainderBase / $pack : 0.0;

        return ['whole' => $whole, 'partial' => self::snapFraction($fraction)];
    }

    /** Nearest offered quarter, rounding a hair of float drift away. */
    public static function snapFraction(float $fraction): float
    {
        if ($fraction <= 0.0) {
            return 0.0;
        }

        $nearest = 0.0;
        foreach ([0.0, 0.25, 0.5, 0.75, 1.0] as $step) {
            if (abs($fraction - $step) < abs($fraction - $nearest)) {
                $nearest = $step;
            }
        }

        // A value that rounds to a whole unit belongs in the whole box, but the
        // caller has already taken the floor, so keep it inside one unit.
        return $nearest >= 1.0 ? 0.75 : $nearest;
    }
}
