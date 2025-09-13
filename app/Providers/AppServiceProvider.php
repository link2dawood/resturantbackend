<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\ManagerAssignedToStores;
use App\Listeners\SendManagerAssignmentEmail;

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
    }
}
