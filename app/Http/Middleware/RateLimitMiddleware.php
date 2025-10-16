<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request with rate limiting
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decaySeconds = '60'): Response
    {
        $maxAttempts = (int) $maxAttempts;
        $decaySeconds = (int) $decaySeconds;

        $key = $this->resolveRequestSignature($request);

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            $retryAfter = Cache::get($key.':lockout');

            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_id' => auth()->id(),
                'route' => $request->route()?->getName(),
                'user_agent' => $request->userAgent(),
                'attempts' => $attempts,
                'max_attempts' => $maxAttempts,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Too many requests',
                    'retry_after' => $retryAfter,
                ], 429);
            }

            return response()->view('errors.429', [
                'retry_after' => $retryAfter,
            ], 429);
        }

        // Increment attempt counter
        Cache::put($key, $attempts + 1, now()->addSeconds($decaySeconds));

        if ($attempts + 1 >= $maxAttempts) {
            Cache::put($key.':lockout', now()->addSeconds($decaySeconds)->timestamp, now()->addSeconds($decaySeconds));
        }

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - $attempts - 1));
        $response->headers->set('X-RateLimit-Reset', now()->addSeconds($decaySeconds)->timestamp);

        return $response;
    }

    /**
     * Resolve request signature for rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $userId = auth()->id();
        $ip = $request->ip();
        $route = $request->route()?->getName() ?? $request->path();

        // Use user ID if authenticated, otherwise fall back to IP
        $identifier = $userId ? "user:{$userId}" : "ip:{$ip}";

        return "rate_limit:{$identifier}:{$route}";
    }
}
