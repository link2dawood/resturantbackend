<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminOrOwnerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            Log::warning('Unauthenticated admin/owner access attempt', [
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            abort(401);
        }

        $user = auth()->user();

        // Check if user account is soft deleted
        if ($user->trashed()) {
            auth()->logout();
            Log::warning('Deleted user admin/owner access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
            abort(401);
        }

        if (!in_array($user->role, [UserRole::ADMIN, UserRole::OWNER])) {
            Log::warning('Unauthorized admin/owner access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role?->value,
                'route' => $request->route()?->getName(),
                'ip' => $request->ip(),
            ]);
            abort(403);
        }

        return $next($request);
    }
}
