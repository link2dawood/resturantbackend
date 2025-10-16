<?php

namespace App\Listeners;

use App\Events\ManagerCreated;
use App\Mail\WelcomeNewManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendManagerWelcomeEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ManagerCreated $event): void
    {
        try {
            // Update the WelcomeNewManager mail to include password
            Mail::to($event->manager->email)
                ->send(new \App\Mail\WelcomeNewManagerWithPassword(
                    $event->manager,
                    $event->assignedStores,
                    $event->createdBy,
                    $event->temporaryPassword
                ));

            Log::info('Manager welcome email sent successfully', [
                'manager_id' => $event->manager->id,
                'manager_email' => $event->manager->email,
                'created_by' => $event->createdBy->id,
                'stores_count' => $event->assignedStores->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send manager welcome email', [
                'manager_id' => $event->manager->id,
                'manager_email' => $event->manager->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ManagerCreated $event, \Throwable $exception): void
    {
        Log::error('Manager welcome email job failed', [
            'manager_id' => $event->manager->id,
            'manager_email' => $event->manager->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
