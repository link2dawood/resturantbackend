<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 4 — Multi-tenant identification.
 *
 * Establishes the per-request tenant context. The actual query/file scoping is
 * driven by TenantManager (used by the TenantScoped global scope); this
 * middleware just clears any memoized state so each request resolves the
 * current user's tenant freshly.
 */
class InitializeTenant
{
    public function __construct(private TenantManager $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenant->flush();

        return $next($request);
    }
}
