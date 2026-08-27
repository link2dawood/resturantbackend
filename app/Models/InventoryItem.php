<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'store_id', 'preferred_vendor_id', 'inventory_category_id', 'category', 'name',
        'base_unit', 'purchase_unit', 'units_per_purchase', 'portion_size', 'portion_unit',
        'min_stock_level', 'safety_buffer_pct', 'reorder_threshold', 'notes', 'is_active',
    ];

    protected $casts = [
        'units_per_purchase' => 'decimal:4',
        'portion_size' => 'decimal:2',
        'min_stock_level' => 'decimal:4',
        'safety_buffer_pct' => 'decimal:2',
        'reorder_threshold' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function preferredVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'preferred_vendor_id');
    }

    /** The order-guide category this item is filed under. */
    public function inventoryCategory(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    /** Per-store stock targets. One row per store that has set one. */
    public function targets(): HasMany
    {
        return $this->hasMany(StoreInventoryTarget::class, 'inventory_item_id');
    }

    public function stockRows(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Every vendor that sells this item. Steak can come from Lisanti or
     * Restaurant Depot; the pivot holds each vendor's SKU and current price.
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'inventory_item_vendor')
                    ->withPivot(['vendor_sku', 'current_price', 'price_updated_at', 'is_preferred_vendor', 'notes'])
                    ->withTimestamps();
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    /**
     * Total base units in one purchase unit as described by the portion fields,
     * e.g. 53 portions x 3.00 oz = 159.00 oz in a box of steak. Null when the
     * item has no portion definition.
     */
    public function getPortionTotalAttribute(): ?float
    {
        if ($this->portion_size === null) {
            return null;
        }

        return round((float) $this->units_per_purchase * (float) $this->portion_size, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! filled($search)) {
            return $query;
        }

        return $query->where('name', 'like', '%'.$search.'%');
    }
}
