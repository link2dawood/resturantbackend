<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'transaction_date',
        'post_date',
        'store_id',
        'vendor_id',
        'vendor_name_raw',
        'coa_id',
        'amount',
        'description',
        'reference_number',
        'payment_method',
        'card_last_four',
        'receipt_url',
        'notes',
        'is_reconciled',
        'reconciled_date',
        'reconciled_by',
        'needs_review',
        'review_reason',
        'duplicate_check_hash',
        'import_batch_id',
        'daily_report_id',
        'third_party_statement_id',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'post_date' => 'date',
        'amount' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'needs_review' => 'boolean',
        'reconciled_date' => 'datetime',
    ];

    // Relationships
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'coa_id');
    }

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'daily_report_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reconciledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function thirdPartyStatement(): BelongsTo
    {
        return $this->belongsTo(ThirdPartyStatement::class, 'third_party_statement_id');
    }

    // Scopes
    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }

    public function scopeReconciled($query)
    {
        return $query->where('is_reconciled', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeForCoa($query, $coaId)
    {
        return $query->where('coa_id', $coaId);
    }
}
