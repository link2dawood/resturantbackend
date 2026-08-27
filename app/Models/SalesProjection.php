<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 4 — Daily sales projection (Sales Projection Calendar).
 *
 * TenantScoped by store: a user only sees/edits projections for their own
 * stores. Actuals are not stored here — they come from DailyReport.net_sales on
 * the matching store + date.
 */
class SalesProjection extends Model
{
    use TenantScoped;

    protected $fillable = [
        'store_id',
        'projection_date',
        'amount',
        'updated_by',
    ];

    protected $casts = [
        'projection_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
