<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'inventory_item_id', 'quantity', 'suggested_quantity',
        'is_manual_override', 'unit', 'unit_price', 'line_total', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
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
}
