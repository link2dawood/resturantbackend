<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStock extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    protected $table = 'inventory_stock';

    protected $fillable = [
        'inventory_item_id', 'store_id', 'week_start_date', 'starting_stock',
        'actual_ending_stock', 'status', 'counted_by', 'counted_at', 'notes',
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

    /**
     * The weekly count screen speaks in "submitted / not submitted"; the column
     * is a status string. Keep one source of truth and derive the boolean.
     */
    public function getIsSubmittedAttribute(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /** True once a count has actually been entered, blank rows do not count. */
    public function getIsCountedAttribute(): bool
    {
        return $this->counted_at !== null;
    }

    public function scopeForWeek($query, string $weekStartDate)
    {
        return $query->whereDate('week_start_date', $weekStartDate);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }
}
