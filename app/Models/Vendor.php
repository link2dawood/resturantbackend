<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'vendor_name',
        'vendor_identifier',
        'default_coa_id',
        'default_transaction_type_id',
        'vendor_type',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website',
        'order_method',
        'order_notes',
        'address',
        'notes',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function defaultCoa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'default_coa_id');
    }

    public function defaultTransactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'default_transaction_type_id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'vendor_store_assignments', 'vendor_id', 'store_id')
                    ->withPivot('is_global')
                    ->withTimestamps();
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(VendorAlias::class, 'vendor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Inventory items this vendor supplies (Phase 5). An item can be bought from
     * several vendors, so the pivot carries the per-vendor SKU and price.
     */
    public function inventoryItems(): BelongsToMany
    {
        return $this->belongsToMany(InventoryItem::class, 'inventory_item_vendor')
                    ->withPivot(['vendor_sku', 'current_price', 'price_updated_at', 'is_preferred_vendor', 'notes'])
                    ->withTimestamps();
    }

    /** Items that name this vendor as their default ordering vendor. */
    public function preferredForItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'preferred_vendor_id');
    }

    /** Dated price history for this vendor's items. */
    public function vendorPrices(): HasMany
    {
        return $this->hasMany(VendorPrice::class, 'vendor_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('vendor_type', $type);
    }

    public function scopeHasCoa($query)
    {
        return $query->whereNotNull('default_coa_id');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('vendor_name', 'like', "%{$search}%")
              ->orWhere('vendor_identifier', 'like', "%{$search}%")
              ->orWhere('contact_name', 'like', "%{$search}%")
              ->orWhere('contact_email', 'like', "%{$search}%");
        });
    }

    /** How this vendor takes an order. */
    public const ORDER_METHODS = [
        'online' => 'Order online',
        'phone' => 'Phone the vendor',
        'in_person' => 'Order in person',
        'email' => 'Email the vendor',
    ];

    public function getOrderMethodLabelAttribute(): string
    {
        return self::ORDER_METHODS[$this->order_method] ?? 'Method not set';
    }

    /**
     * A one-line instruction for whoever has to place this order, including the
     * detail they need to actually do it.
     */
    public function getOrderInstructionAttribute(): string
    {
        $detail = match ($this->order_method) {
            'online' => filled($this->website) ? $this->website : 'no website on file',
            'phone' => filled($this->contact_phone) ? $this->contact_phone : 'no phone number on file',
            'email' => filled($this->contact_email) ? $this->contact_email : 'no email on file',
            'in_person' => filled($this->address) ? $this->address : 'take the printed sheet',
            default => 'set how this vendor takes orders under Vendors',
        };

        return $this->order_method_label.': '.$detail;
    }
}
