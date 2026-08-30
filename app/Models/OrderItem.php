<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'inventory_item_id', 'quantity', 'quantity_received',
        'received_notes', 'suggested_quantity', 'is_manual_override', 'unit',
        'unit_price', 'line_total', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'suggested_quantity' => 'decimal:4',
        'is_manual_override' => 'boolean',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // line_total is stored rather than computed on read, so a price change
        // after the fact cannot silently rewrite what an order was worth. Keep
        // it in step here so no caller can forget.
        static::saving(function (OrderItem $item) {
            $item->line_total = $item->unit_price === null
                ? null
                : round((float) $item->quantity * (float) $item->unit_price, 2);
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /** How far the ordered quantity moved from what was suggested. */
    public function getOverrideDeltaAttribute(): ?float
    {
        if ($this->suggested_quantity === null) {
            return null;
        }

        return round((float) $this->quantity - (float) $this->suggested_quantity, 4);
    }

    /** Has anyone actually checked this line in? */
    public function getIsCheckedAttribute(): bool
    {
        return $this->quantity_received !== null;
    }

    /**
     * How far the delivery differed from the order. Positive means they sent
     * more than was asked for, which is the case that has been costing money.
     */
    public function getReceivedDeltaAttribute(): ?float
    {
        if ($this->quantity_received === null) {
            return null;
        }

        return round((float) $this->quantity_received - (float) $this->quantity, 4);
    }

    public function getHasDiscrepancyAttribute(): bool
    {
        $delta = $this->received_delta;

        return $delta !== null && abs($delta) >= 0.0001;
    }

    /** short | over | exact | unchecked */
    public function getReceivedStatusAttribute(): string
    {
        if ($this->quantity_received === null) {
            return 'unchecked';
        }

        $delta = $this->received_delta;

        return $delta > 0 ? 'over' : ($delta < 0 ? 'short' : 'exact');
    }

    /** What the delivery was actually worth, if it differed from the order. */
    public function getReceivedValueAttribute(): ?float
    {
        if ($this->quantity_received === null || $this->unit_price === null) {
            return null;
        }

        return round((float) $this->quantity_received * (float) $this->unit_price, 2);
    }
}
