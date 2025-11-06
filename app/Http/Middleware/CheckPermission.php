<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resource, string $action): Response
    {
        $user = auth()->user();

        if (!$user) {
            Log::warning('Unauthenticated permission check attempt', [
                'resource' => $resource,
                'action' => $action,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user account is soft deleted
        if ($user->trashed()) {
            auth()->logout();
            return response()->json(['error' => 'Account no longer active'], 401);
        }

        // Super Admin: Full access (if we add this role in the future)
        // For now, Admin is the highest role
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Get store ID from request
        $storeId = $request->input('store_id') 
            ?? $request->route('store_id') 
            ?? $request->route('id');

        // Check permission based on resource and action
        if (!$this->hasPermission($user, $resource, $action, $storeId)) {
            Log::warning('Permission denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role?->value,
                'resource' => $resource,
                'action' => $action,
                'store_id' => $storeId,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Permission denied'], 403);
        }

        // Check store access if store_id is provided
        if ($storeId && !$user->hasStoreAccess($storeId)) {
            Log::warning('Store access denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'store_id' => $storeId,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Store access denied'], 403);
        }

        return $next($request);
    }

    /**
     * Check if user has permission for resource and action
     */
    protected function hasPermission($user, string $resource, string $action, $storeId = null): bool
    {
        // Permission matrix based on role
        $permissions = [
            'manager' => [
                'coa' => ['view'],
                'vendors' => ['view'],
                'expenses' => ['view', 'create'], // Can view and create expenses for their store
                'reports' => ['view'], // Can view P&L for their store
                'imports' => [], // Cannot upload CSV
                'bank' => [], // Cannot access bank reconciliation
                'review' => [], // Cannot review/categorize
            ],
            'owner' => [
                'coa' => ['view'],
                'vendors' => ['view', 'create', 'update'],
                'expenses' => ['view', 'create', 'update'], // Can view all stores, create, update
                'reports' => ['view', 'export'], // Can view P&L for all their stores, export
                'imports' => ['upload'], // Can upload CSV
                'bank' => ['view', 'reconcile'], // Can access bank reconciliation
                'review' => ['view', 'categorize'], // Can review/categorize
            ],
            'admin' => [
                'coa' => ['view', 'create', 'update', 'delete'],
                'vendors' => ['view', 'create', 'update', 'delete'],
                'expenses' => ['view', 'create', 'update', 'delete'], // Full access
                'reports' => ['view', 'export'], // Full access to all stores
                'imports' => ['upload'], // Can upload CSV
                'bank' => ['view', 'reconcile'], // Full access
                'review' => ['view', 'categorize'], // Full access
            ],
        ];

        $role = $user->role?->value ?? 'manager';
        
        // Check if resource exists in permissions
        if (!isset($permissions[$role][$resource])) {
            return false;
        }

        // Check if action is allowed for this resource
        return in_array($action, $permissions[$role][$resource]);
    }
}
