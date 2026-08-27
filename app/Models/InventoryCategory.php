<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 5 — order-guide categories (Meats, Breads, Cheese, ...). Shared reference
 * data: every store orders from the same category list, so there is no store_id
 * here. `display_order` drives the sort on the weekly count and order screens.
 */
class InventoryCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /** Every item filed under this category, across all stores. */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'inventory_category_id');
    }

    /** Items that would block a delete: still in use, not hidden. */
    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    /** The list order used everywhere the categories are shown. */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    /** Next slot at the end of the list, leaving gaps for manual reordering. */
    public static function nextDisplayOrder(): int
    {
        return ((int) static::max('display_order')) + 10;
    }
}
