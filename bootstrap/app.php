<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web([
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\UpdateLastOnline::class,
        ]);

        // Add rate limiting to API routes if they exist
        if (file_exists(__DIR__.'/../routes/api.php')) {
            $middleware->api([
                \App\Http\Middleware\RateLimitMiddleware::class.':120,60', // 120 requests per minute
            ]);
        }

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin_or_owner' => \App\Http\Middleware\AdminOrOwnerMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'daily_report_access' => \App\Http\Middleware\CheckDailyReportAccess::class,
            'convert_date_format' => \App\Http\Middleware\ConvertDateFormat::class,
            'rate_limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'login_rate_limit' => \App\Http\Middleware\LoginRateLimitMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
