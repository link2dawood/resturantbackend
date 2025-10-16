<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoginRateLimitMiddleware
{
    /**
     * Handle an incoming request with strict login rate limiting
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->input('email');
        $ip = $request->ip();

        // Rate limiting keys
        $ipKey = "login_attempts:ip:{$ip}";
        $emailKey = $email ? "login_attempts:email:{$email}" : null;

        // Rate limits (max attempts in time period)
        $ipMaxAttempts = 10; // 10 attempts per IP per 15 minutes
        $emailMaxAttempts = 5; // 5 attempts per email per 15 minutes
        $lockoutTime = 900; // 15 minutes lockout

        // Check IP-based rate limiting
        $ipAttempts = Cache::get($ipKey, 0);
        if ($ipAttempts >= $ipMaxAttempts) {
            $this->logRateLimitExceeded($request, 'IP', $ipAttempts);

            return $this->rateLimitResponse($request, $lockoutTime);
        }

        // Check email-based rate limiting
        if ($emailKey) {
            $emailAttempts = Cache::get($emailKey, 0);
            if ($emailAttempts >= $emailMaxAttempts) {
                $this->logRateLimitExceeded($request, 'Email', $emailAttempts, $email);

                return $this->rateLimitResponse($request, $lockoutTime);
            }
        }

        $response = $next($request);

        // If login failed (typically redirected back or 422), increment counters
        if ($this->isLoginFailure($response)) {
            Cache::increment($ipKey, 1);
            Cache::expire($ipKey, $lockoutTime);

            if ($emailKey) {
                Cache::increment($emailKey, 1);
                Cache::expire($emailKey, $lockoutTime);
            }
        } elseif ($this->isLoginSuccess($response)) {
            // Clear rate limit counters on successful login
            Cache::forget($ipKey);
            if ($emailKey) {
                Cache::forget($emailKey);
            }
        }

        return $response;
    }

    /**
     * Determine if the response indicates a login failure
     */
    protected function isLoginFailure(Response $response): bool
    {
        // Check for redirect with errors or 422 validation error
        return $response->isRedirection() || $response->getStatusCode() === 422;
    }

    /**
     * Determine if the response indicates a login success
     */
    protected function isLoginSuccess(Response $response): bool
    {
        // Successful login typically redirects to intended location
        return $response->isRedirection() && ! session()->has('errors');
    }

    /**
     * Log rate limit exceeded event
     */
    protected function logRateLimitExceeded(Request $request, string $type, int $attempts, ?string $email = null): void
    {
        Log::warning('Login rate limit exceeded', [
            'type' => $type,
            'ip' => $request->ip(),
            'email' => $email,
            'attempts' => $attempts,
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Return rate limit response
     */
    protected function rateLimitResponse(Request $request, int $retryAfter): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Too many login attempts',
                'message' => 'Please wait before attempting to login again',
                'retry_after' => $retryAfter,
            ], 429);
        }

        return redirect()->back()
            ->withInput($request->except('password'))
            ->withErrors([
                'email' => 'Too many login attempts. Please try again in 15 minutes.',
            ]);
    }
}
