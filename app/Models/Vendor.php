<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_name',
        'vendor_identifier',
        'default_coa_id',
        'default_transaction_type_id',
        'vendor_type',
        'contact_name',
        'contact_email',
        'contact_phone',
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
              ->orWhere('vendor_identifier', 'like', "%{$search}%");
        });
    }
}
