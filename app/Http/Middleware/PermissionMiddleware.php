<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! auth()->check()) {
            Log::warning('Unauthenticated permission access attempt', [
                'permission' => $permission,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user account is soft deleted
        if ($user->trashed()) {
            auth()->logout();
            Log::warning('Deleted user permission access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'permission' => $permission,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Account no longer active'], 401);
            }

            return redirect()->route('login')->with('error', 'Your account is no longer active.');
        }

        // Admin controls entire dashboard - bypass all permission checks
        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! $user->hasPermission($permission)) {
            Log::warning('Insufficient permissions access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role?->value,
                'required_permission' => $permission,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden - Insufficient permissions'], 403);
            }

            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
