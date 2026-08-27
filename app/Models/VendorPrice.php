<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id', 'inventory_item_id', 'price', 'price_unit', 'effective_date', 'entered_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'effective_date' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function enterer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
