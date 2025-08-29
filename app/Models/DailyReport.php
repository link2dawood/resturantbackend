<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'created_by'
    ];

    protected $casts = [
        'report_date' => 'date',
        'projected_sales' => 'decimal:2',
        'gross_sales' => 'decimal:2',
        'amount_of_cancels' => 'decimal:2',
        'amount_of_voids' => 'decimal:2',
        'coupons_received' => 'decimal:2',
        'adjustments_overrings' => 'decimal:2',
        'credit_cards' => 'decimal:2',
        'actual_deposit' => 'decimal:2'
    ];

    protected $appends = [
        'total_paid_outs',
        'net_sales',
        'tax',
        'sales_pre_tax',
        'cash_to_account_for',
        'short',
        'over',
        'average_ticket'
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(DailyReportTransaction::class);
    }

    public function getTotalPaidOutsAttribute(): float
    {
        return $this->transactions->sum('amount');
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
        return $this->getNetSalesAttribute() - $this->getTotalPaidOutsAttribute() - $this->credit_cards;
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
}
