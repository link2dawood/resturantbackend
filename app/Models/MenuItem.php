<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id', 'name', 'category', 'square_name', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    /** The current recipe for a given size variant, if any. */
    public function currentRecipe(string $sizeVariant): ?Recipe
    {
        return $this->recipes()
            ->where('size_variant', $sizeVariant)
            ->where('is_current', true)
            ->first();
    }
}
