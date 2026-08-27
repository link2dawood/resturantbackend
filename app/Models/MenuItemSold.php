<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemSold extends Model
{
    use HasFactory;

    protected $table = 'menu_items_sold';

    protected $fillable = [
        'store_id', 'week_start_date', 'menu_item_id', 'size_variant',
        'square_raw_name', 'quantity_sold', 'import_batch_id', 'is_matched',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'quantity_sold' => 'decimal:4',
        'is_matched' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
