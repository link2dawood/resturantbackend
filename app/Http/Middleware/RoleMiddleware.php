<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            Log::warning('Unauthenticated access attempt', [
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        
        // Check if user account is soft deleted
        if ($user->trashed()) {
            auth()->logout();
            Log::warning('Deleted user access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Account no longer active'], 401);
        }

        // Convert string roles to UserRole enums for comparison
        $allowedRoles = array_map(function($role) {
            return is_string($role) ? UserRole::from($role) : $role;
        }, $roles);

        if (!$user->role || !in_array($user->role, $allowedRoles)) {
            Log::warning('Insufficient permissions access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role?->value,
                'required_roles' => array_map(fn($r) => $r->value, $allowedRoles),
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Forbidden - Insufficient permissions'], 403);
        }

        return $next($request);
    }
}
