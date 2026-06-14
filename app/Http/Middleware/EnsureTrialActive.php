<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phase 4 — SaaS trial lockout.
 *
 * Blocks a user whose workspace trial has expired, redirecting them to the
 * "Trial Expired" screen. Admins and the franchisor are exempt; managers are
 * gated by their owner's subscription via User::billingOwner().
 *
 * Fails open: if we can't resolve a billing owner (data edge cases, users with
 * no workspace yet) we let the request through rather than lock out a
 * legitimate user. The trial routes themselves are NOT behind this middleware.
 */
class EnsureTrialActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request); // 'auth' middleware handles unauthenticated users
        }

        $owner = $user->billingOwner();

        // Exempt (admin/franchisor) or unresolved → allow through.
        if (! $owner) {
            return $next($request);
        }

        if ($owner->hasActiveAccess()) {
            return $next($request);
        }

        // Trial has expired — block access.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your free trial has expired.',
                'trial_expired' => true,
            ], 402);
        }

        return redirect()->route('trial.expired');
    }
}
