<?php

namespace App\Models\Concerns;

use App\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;

/**
 * Phase 4 — Multi-tenant isolation.
 *
 * Adds an automatic global scope so every Eloquent query on the model is
 * constrained to the current user's accessible stores (the tenant boundary).
 * Admins/franchisor/impersonation and non-web contexts are exempt — see
 * TenantManager. Apply only to models that carry a `store_id` column.
 *
 * Escape hatch: Model::withoutTenantScope() (e.g. for cross-tenant admin tools).
 */
trait TenantScoped
{
    public static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            app(TenantManager::class)->applyTo($builder, $builder->getModel());
        });
    }

    /**
     * Query the model without the tenant constraint.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
