<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PLACED = 'placed';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PLACED,
        self::STATUS_RECEIVED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'store_id', 'vendor_id', 'week_start_date', 'order_sequence',
        'status', 'placed_at', 'received_at', 'notes', 'created_by',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'order_sequence' => 'integer',
        'placed_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * A draft is the only editable state. Once placed, the lines are what the
     * vendor was told, so they must not change; only the status can move on to
     * received or cancelled.
     */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isLocked(): bool
    {
        return ! $this->isEditable();
    }

    /** The allowed next states from where this order is now. */
    public function allowedTransitions(): array
    {
        return match ($this->status) {
            self::STATUS_DRAFT => [self::STATUS_PLACED, self::STATUS_CANCELLED],
            self::STATUS_PLACED => [self::STATUS_RECEIVED, self::STATUS_CANCELLED],
            // Received is terminal: the stock is already on the shelf.
            default => [],
        };
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /** Sum of the line totals. */
    public function getTotalAttribute(): float
    {
        return round((float) $this->items->sum(fn ($item) => (float) $item->line_total), 2);
    }

    /** True when at least one line was changed away from its suggestion. */
    public function getHasOverridesAttribute(): bool
    {
        return $this->items->contains(fn ($item) => (bool) $item->is_manual_override);
    }

    public function scopeForWeek($query, string $weekStartDate)
    {
        return $query->whereDate('week_start_date', $weekStartDate);
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
