<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_account_id',
        'transaction_date',
        'post_date',
        'description',
        'transaction_type',
        'amount',
        'balance',
        'reference_number',
        'matched_expense_id',
        'matched_revenue_id',
        'reconciliation_status',
        'reconciliation_notes',
        'import_batch_id',
        'duplicate_check_hash',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'post_date' => 'date',
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    // Relationships
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function matchedExpense(): BelongsTo
    {
        return $this->belongsTo(ExpenseTransaction::class, 'matched_expense_id');
    }

    public function matchedRevenue(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'matched_revenue_id');
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    // Scopes
    public function scopeUnmatched($query)
    {
        return $query->where('reconciliation_status', 'unmatched');
    }

    public function scopeMatched($query)
    {
        return $query->where('reconciliation_status', 'matched');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('bank_account_id', $accountId);
    }
}
