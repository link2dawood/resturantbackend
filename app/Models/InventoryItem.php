<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id', 'preferred_vendor_id', 'category', 'name', 'base_unit', 'purchase_unit',
        'units_per_purchase', 'min_stock_level', 'safety_buffer_pct',
        'reorder_threshold', 'is_active',
    ];

    protected $casts = [
        'units_per_purchase' => 'decimal:4',
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

    public function stockRows(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
