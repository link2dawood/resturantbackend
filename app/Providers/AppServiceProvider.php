<?php

namespace App\Providers;

use App\Events\ManagerAssignedToStores;
use App\Listeners\SendManagerAssignmentEmail;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\DailyReport::observe(\App\Observers\DailyReportObserver::class);

        // Register event listeners
        Event::listen(
            ManagerAssignedToStores::class,
            SendManagerAssignmentEmail::class,
        );

        Event::listen(
            \App\Events\OwnerCreated::class,
            \App\Listeners\SendOwnerWelcomeEmail::class,
        );

        Event::listen(
            \App\Events\ManagerCreated::class,
            \App\Listeners\SendManagerWelcomeEmail::class,
        );

        // Register Blade directives for permissions
        Blade::if('can', function (string $resource, string $action) {
            $user = auth()->user();
            
            if (!$user) {
                return false;
            }

            // Admin has full access
            if ($user->isAdmin()) {
                return true;
            }

            // Permission matrix
            $permissions = [
                'manager' => [
                    'coa' => ['view'],
                    'vendors' => ['view'],
                    'expenses' => ['view', 'create'],
                    'reports' => ['view'],
                    'imports' => [],
                    'bank' => [],
                    'review' => [],
                ],
                'owner' => [
                    'coa' => ['view'],
                    'vendors' => ['view', 'create', 'update'],
                    'expenses' => ['view', 'create', 'update'],
                    'reports' => ['view', 'export'],
                    'imports' => ['upload'],
                    'bank' => ['view', 'reconcile'],
                    'review' => ['view', 'categorize'],
                ],
                'admin' => [
                    'coa' => ['view', 'create', 'update', 'delete'],
                    'vendors' => ['view', 'create', 'update', 'delete'],
                    'expenses' => ['view', 'create', 'update', 'delete'],
                    'reports' => ['view', 'export'],
                    'imports' => ['upload'],
                    'bank' => ['view', 'reconcile'],
                    'review' => ['view', 'categorize'],
                ],
            ];

            $role = $user->role?->value ?? 'manager';
            
            if (!isset($permissions[$role][$resource])) {
                return false;
            }

            return in_array($action, $permissions[$role][$resource]);
        });

        // Blade directive to check if user can access all stores
        Blade::if('canViewAllStores', function () {
            $user = auth()->user();
            return $user && ($user->isAdmin() || $user->isOwner());
        });
    }
}
