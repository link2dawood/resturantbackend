<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory;

    protected $table = 'inventory_stock';

    protected $fillable = [
        'inventory_item_id', 'store_id', 'week_start_date', 'starting_stock',
        'actual_ending_stock', 'status', 'counted_by', 'counted_at',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'starting_stock' => 'decimal:4',
        'actual_ending_stock' => 'decimal:4',
        'counted_at' => 'datetime',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counted_by');
    }
}
