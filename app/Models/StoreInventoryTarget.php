<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 5 — how much of an item a particular store keeps on hand.
 *
 * "Round Rock keeps 15 boxes of steak, Downtown keeps 8." Both levels are held
 * in the item's PURCHASE unit (boxes, cases, bags), because that is how the
 * client talks about stock and how orders are placed. `baseTargetFor()` converts
 * to the base unit for anything that does its maths there.
 *
 * Note this is a different quantity from inventory_items.min_stock_level, which
 * is in BASE units and is what StockUpService currently reads. This table is the
 * per-store override the order guide is built from.
 */
class StoreInventoryTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'inventory_item_id',
        'target_stock_level',
        'min_stock_level',
    ];

    protected $casts = [
        'target_stock_level' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** The target expressed in the item's base unit, e.g. 15 boxes -> 795 portions. */
    public function baseTargetFor(InventoryItem $item): float
    {
        return round((float) $this->target_stock_level * (float) $item->units_per_purchase, 4);
    }

    /** True once the item is at or below its reorder point. */
    public function needsReorder(float $onHandPurchaseUnits): bool
    {
        return $this->min_stock_level !== null
            && $onHandPurchaseUnits <= (float) $this->min_stock_level;
    }
}
