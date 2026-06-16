<?php

namespace App\Providers;

use App\Events\ManagerAssignedToStores;
use App\Listeners\HandleStripeWebhook;
use App\Listeners\SendManagerAssignmentEmail;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookHandled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Shared per-request tenant context (used by the TenantScoped global scope).
        $this->app->singleton(\App\Tenancy\TenantManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\DailyReport::observe(\App\Observers\DailyReportObserver::class);

        // Register event listeners
        // (Email verification is sent directly from RegisterController, wrapped in a
        // try/catch, so an SMTP failure can't 500 the signup request.)

        // Stripe webhooks: Cashier syncs the DB, then we send receipts/dunning
        // emails and mirror subscription state onto the owner's access flag.
        Event::listen(
            WebhookHandled::class,
            HandleStripeWebhook::class,
        );

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
                    // Owners can view and ADD chart-of-accounts, but not modify existing ones.
                    'coa' => ['view', 'create'],
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
