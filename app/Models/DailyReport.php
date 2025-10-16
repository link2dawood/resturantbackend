<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DailyReport extends Model
{
    protected $fillable = [
        'restaurant_name',
        'address',
        'phone',
        'report_date',
        'page_number',
        'weather',
        'holiday_event',
        'projected_sales',
        'gross_sales',
        'amount_of_cancels',
        'amount_of_voids',
        'number_of_no_sales',
        'total_coupons',
        'coupons_received',
        'adjustments_overrings',
        'total_customers',
        'credit_cards',
        'actual_deposit',
        'store_id',
        'created_by',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    /**
     * Default relationships to eager load to prevent N+1 queries
     */
    protected $with = ['store', 'creator'];

    protected $casts = [
        'report_date' => 'date',
        'approved_at' => 'datetime',
        'projected_sales' => 'decimal:2',
        'gross_sales' => 'decimal:2',
        'amount_of_cancels' => 'decimal:2',
        'amount_of_voids' => 'decimal:2',
        'coupons_received' => 'decimal:2',
        'adjustments_overrings' => 'decimal:2',
        'credit_cards' => 'decimal:2',
        'actual_deposit' => 'decimal:2',
    ];

    protected $appends = [
        'total_transaction_expenses',
        'total_paid_outs',
        'net_sales',
        'tax',
        'sales_pre_tax',
        'cash_to_account_for',
        'short',
        'over',
        'average_ticket',
        'total_revenue_income',
        'total_revenue_entries',
        'online_platform_revenue',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(DailyReportTransaction::class);
    }

    public function revenues(): HasMany
    {
        return $this->hasMany(DailyReportRevenue::class);
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function getTotalTransactionExpensesAttribute(): float
    {
        // Use cached sum if available to avoid N+1 queries
        return $this->transactions_sum_amount ?? $this->transactions()->sum('amount');
    }

    public function getTotalPaidOutsAttribute(): float
    {
        // Alias for backward compatibility
        return $this->getTotalTransactionExpensesAttribute();
    }

    public function getNetSalesAttribute(): float
    {
        return $this->gross_sales - $this->coupons_received - $this->adjustments_overrings;
    }

    public function getTaxAttribute(): float
    {
        $netSales = $this->getNetSalesAttribute();

        return $netSales - ($netSales / 1.0825);
    }

    public function getSalesPreTaxAttribute(): float
    {
        return $this->getNetSalesAttribute() - $this->getTaxAttribute();
    }

    public function getCashToAccountForAttribute(): float
    {
        return $this->getNetSalesAttribute() - $this->getTotalTransactionExpensesAttribute() - $this->credit_cards - $this->getOnlinePlatformRevenueAttribute();
    }

    public function getShortAttribute(): float
    {
        $cashToAccountFor = $this->getCashToAccountForAttribute();

        return $this->actual_deposit < $cashToAccountFor ?
               $this->actual_deposit - $cashToAccountFor : 0;
    }

    public function getOverAttribute(): float
    {
        $cashToAccountFor = $this->getCashToAccountForAttribute();

        return $this->actual_deposit > $cashToAccountFor ?
               $this->actual_deposit - $cashToAccountFor : 0;
    }

    public function getAverageTicketAttribute(): float
    {
        return $this->total_customers > 0 ?
               $this->getNetSalesAttribute() / $this->total_customers : 0;
    }

    public function getTotalRevenueIncomeAttribute(): float
    {
        // Use cached sum if available to avoid N+1 queries
        return $this->revenues_sum_amount ?? $this->revenues()->sum('amount');
    }

    public function getTotalRevenueEntriesAttribute(): float
    {
        // Alias for backward compatibility
        return $this->getTotalRevenueIncomeAttribute();
    }

    public function getOnlinePlatformRevenueAttribute(): float
    {
        return $this->revenues()
            ->whereHas('revenueIncomeType', function ($query) {
                $query->where('category', 'online');
            })
            ->sum('amount');
    }
}
