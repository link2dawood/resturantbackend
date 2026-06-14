<?php

namespace App\Tenancy;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Phase 4 — Multi-tenant architecture.
 *
 * The tenant boundary is the individual store/restaurant: `store_id` is the
 * tenant key on every financial record. This manager resolves the set of store
 * ids the current user is allowed to touch and decides who is exempt from
 * scoping (admins, the franchisor, and admin-level impersonation — who are
 * deliberately cross-tenant in this product).
 *
 * It is the single source of truth used by the TenantScoped global scope, so a
 * controller that forgets to filter by store can no longer leak another
 * tenant's data (defense-in-depth over the existing manual filtering).
 */
class TenantManager
{
    /** Memoized accessible store ids, keyed by user id (per request). */
    private array $storeIdCache = [];

    /**
     * Users who see across tenants by design and must NOT be scoped.
     */
    public function isExempt(User $user): bool
    {
        return $user->isAdmin()
            || $user->isFranchisor()
            || $user->isBeingImpersonated();
    }

    /**
     * The tenant store ids the given user may access.
     */
    public function storeIdsFor(User $user): array
    {
        return $this->storeIdCache[$user->id]
            ??= $user->getAccessibleStoreIds();
    }

    /**
     * Apply the tenant constraint to an Eloquent query for the given model.
     *
     * No-ops when there is no authenticated web user (console, queue jobs,
     * Stripe webhooks, seeders) or when the user is exempt — so background work
     * and admin/franchisor views are unaffected.
     *
     * NULL store_id means "corporate / not tied to one restaurant" and stays
     * visible to everyone, matching the app's existing shared-corporate
     * semantics (e.g. corporate bank accounts).
     */
    public function applyTo(Builder $builder, Model $model): void
    {
        if (! Auth::hasUser()) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User || $this->isExempt($user)) {
            return;
        }

        $storeIds = $this->storeIdsFor($user);
        $column = $model->qualifyColumn('store_id');

        $builder->where(function (Builder $query) use ($column, $storeIds) {
            $query->whereIn($column, $storeIds)
                ->orWhereNull($column);
        });
    }

    /**
     * Forget memoized data (used between requests in tests).
     */
    public function flush(): void
    {
        $this->storeIdCache = [];
    }
}
