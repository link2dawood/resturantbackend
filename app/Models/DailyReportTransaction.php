<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReportTransaction extends Model
{
    protected $fillable = [
        'daily_report_id',
        'transaction_type_id',
        'company',
        'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class);
    }

    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
    }
}
