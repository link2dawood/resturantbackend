<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueIncomeType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'is_active',
        'sort_order',
        'metadata',
        'default_coa_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function defaultCoa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_coa_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
