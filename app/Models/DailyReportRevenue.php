<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReportRevenue extends Model
{
    protected $fillable = [
        'daily_report_id',
        'revenue_income_type_id',
        'amount',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function revenueIncomeType(): BelongsTo
    {
        return $this->belongsTo(RevenueIncomeType::class);
    }
}
