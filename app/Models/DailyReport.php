<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DailyReport extends Model
{
    use HasFactory, TenantScoped;
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
        'adjustments_cash',
        'adjustments_credit_card',
        'tips',
        'total_customers',
        'credit_cards',
        'credit_card_tips',
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
        'adjustments_cash' => 'decimal:2',
        'adjustments_credit_card' => 'decimal:2',
        'tips' => 'decimal:2',
        'credit_cards' => 'decimal:2',
        'credit_card_tips' => 'decimal:2',
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

    public function getTotalRevenueEntriesAttribute(): float
    {
        // Total Revenue Entries = sum of all revenue entries
        return $this->revenues_sum_amount ?? $this->revenues()->sum('amount');
    }

    public function getGrossSalesAttribute(): float
    {
        // Gross Sales = Total Revenue Entries + Coupons Received
        $totalRevenueEntries = $this->getTotalRevenueEntriesAttribute();
        $couponsReceived = (float) ($this->coupons_received ?? 0);
        return $totalRevenueEntries + $couponsReceived;
    }

    public function getNetSalesAttribute(): float
    {
        // Net Sales = Revenue Entries
        //   − Coupons Received
        //   − Adjustments: Overrings/Returns
        //   − Adjustments for Cash
        //   − Adjustments for Credit Card
        //   − Credit Card Tips   (tips are charged on the card but aren't sales)
        $totalRevenueEntries = $this->getTotalRevenueEntriesAttribute();
        $couponsReceived = (float) ($this->coupons_received ?? 0);
        $adjustmentsOverrings = (float) ($this->adjustments_overrings ?? 0);
        $adjustmentsCash = (float) ($this->adjustments_cash ?? 0);
        $adjustmentsCreditCard = (float) ($this->adjustments_credit_card ?? 0);
        $creditCardTips = (float) ($this->credit_card_tips ?? 0);

        return $totalRevenueEntries
            - $couponsReceived
            - $adjustmentsOverrings
            - $adjustmentsCash
            - $adjustmentsCreditCard
            - $creditCardTips;
    }

    /** Credit Card sales = the card total charged minus the tips portion. */
    public function getCreditCardSalesAttribute(): float
    {
        return round((float) ($this->credit_cards ?? 0) - (float) ($this->credit_card_tips ?? 0), 2);
    }

    public function getTaxAttribute(): float
    {
        $netSales = $this->getNetSalesAttribute();
        
        // Tax = Net Sales minus (Net Sales / 1.0825)
        return $netSales - ($netSales / 1.0825);
    }

    public function getSalesPreTaxAttribute(): float
    {
        return $this->getNetSalesAttribute() - $this->getTaxAttribute();
    }

    public function getCashToAccountForAttribute(): float
    {
        // Cash To Account For = Net Sales − Transaction Expenses − Online Platform
        //   − Credit Card SALES (= card total − tips) − Checks − Crypto.
        // Tips come out of the card total (not the cash drawer), so only the card
        // SALES reduce the cash owed.
        $netSales = $this->getNetSalesAttribute();
        $transactionExpenses = $this->getTotalTransactionExpensesAttribute();
        $onlinePlatformRevenue = $this->getOnlinePlatformRevenueAttribute();
        $creditCardSales = $this->getCreditCardSalesAttribute();
        $checksRevenue = $this->getChecksRevenueAttribute();
        $cryptoRevenue = $this->getCryptoRevenueAttribute();

        $result = $netSales - $transactionExpenses - $onlinePlatformRevenue - $creditCardSales - $checksRevenue - $cryptoRevenue;
        
        // Ensure result is not negative (numbers cannot go negative)
        return max(0, round($result, 2));
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
        // Average ticket is based on Sales (Pre-Tax), not Net Sales.
        return $this->total_customers > 0 ?
               $this->getSalesPreTaxAttribute() / $this->total_customers : 0;
    }

    public function getTotalRevenueIncomeAttribute(): float
    {
        // Alias for backward compatibility - same as Total Revenue Entries
        return $this->getTotalRevenueEntriesAttribute();
    }

    public function getOnlinePlatformRevenueAttribute(): float
    {
        // Prefer already-loaded relationships to avoid N+1 queries
        if ($this->relationLoaded('revenues')) {
            return $this->revenues
                ->filter(function ($revenue) {
                    return $revenue->revenueIncomeType
                        && $revenue->revenueIncomeType->category === 'online';
                })
                ->sum('amount');
        }

        // Fallback: query database when revenues are not preloaded
        return $this->revenues()
            ->whereHas('revenueIncomeType', function ($query) {
                $query->where('category', 'online');
            })
            ->sum('amount');
    }

    public function getChecksRevenueAttribute(): float
    {
        // Prefer already-loaded relationships to avoid N+1 queries
        if ($this->relationLoaded('revenues')) {
            return $this->revenues
                ->filter(function ($revenue) {
                    return $revenue->revenueIncomeType
                        && $revenue->revenueIncomeType->category === 'check';
                })
                ->sum('amount');
        }

        // Fallback: query database when revenues are not preloaded
        return $this->revenues()
            ->whereHas('revenueIncomeType', function ($query) {
                $query->where('category', 'check');
            })
            ->sum('amount');
    }

    public function getCryptoRevenueAttribute(): float
    {
        // Prefer already-loaded relationships to avoid N+1 queries
        if ($this->relationLoaded('revenues')) {
            return $this->revenues
                ->filter(function ($revenue) {
                    return $revenue->revenueIncomeType
                        && $revenue->revenueIncomeType->category === 'crypto';
                })
                ->sum('amount');
        }

        // Fallback: query database when revenues are not preloaded
        return $this->revenues()
            ->whereHas('revenueIncomeType', function ($query) {
                $query->where('category', 'crypto');
            })
            ->sum('amount');
    }
}
