<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    public const MERCHANT_PROCESSING_FEE_CODES = ['6100', '6000'];

    /**
     * Account codes that are rollup "totals" (sum of rows below). Hidden from
     * transaction-type/COA dropdowns on daily reports and owner CC statements.
     */
    public static function totalRollupAccountCodes(): array
    {
        return [
            '6200', // Equipment Total
            '6300', // Insurance Total
            '6400', // Marketing Total
            '6450', // Online Merchant Expenses - Total
            '6500', // Rent Total
            '6600', // Payroll Total
            '6700', // Professional Services Total
            '6800', // Permits and Fees Total
            '6900', // Travel and Expense Total
            '6950', // Utilities Total
        ];
    }

    public static function merchantProcessingFeesAccount(): ?self
    {
        return static::query()
            ->where(function ($query) {
                $query->whereIn('account_code', self::MERCHANT_PROCESSING_FEE_CODES)
                    ->orWhere('account_name', 'Merchant Processing Fees')
                    ->orWhere('account_name', 'Merchant Processing Fees (CC)')
                    ->orWhere('account_name', 'like', 'Merchant Processing Fees%');
            })
            ->orderByRaw("CASE WHEN account_code = '6100' THEN 0 WHEN account_name = 'Merchant Processing Fees' THEN 1 ELSE 2 END")
            ->first();
    }

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'is_active',
        'is_system_account',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system_account' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'coa_store_assignments', 'coa_id', 'store_id')
                    ->withPivot('is_global')
                    ->withTimestamps();
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
        return $query->where('account_type', $type);
    }
}
