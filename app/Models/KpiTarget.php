<?php

namespace App\Models;

use App\Models\Concerns\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Phase 4 — Per-tenant (per-store) KPI targets.
 *
 * TenantScoped: a user only ever sees/edits targets for stores they can access.
 * Columns may be NULL — callers fall back to config('dashboard.targets').
 */
class KpiTarget extends Model
{
    use TenantScoped;

    protected $fillable = [
        'store_id',
        'food_cost_pct',
        'payroll_pct',
        'rent_pct',
        'updated_by',
    ];

    protected $casts = [
        'food_cost_pct' => 'float',
        'payroll_pct' => 'float',
        'rent_pct' => 'float',
    ];

    /** Maps a dashboard metric key to its target column. */
    public const COLUMN_FOR = [
        'food' => 'food_cost_pct',
        'payroll' => 'payroll_pct',
        'rent' => 'rent_pct',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
