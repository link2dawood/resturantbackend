<?php

namespace App\Listeners;

use App\Events\OwnerCreated;
use App\Mail\WelcomeNewOwner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOwnerWelcomeEmail implements ShouldQueue
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
    public function handle(OwnerCreated $event): void
    {
        try {
            Mail::to($event->owner->email)
                ->send(new WelcomeNewOwner(
                    $event->owner,
                    $event->temporaryPassword,
                    $event->createdBy
                ));

            Log::info('Owner welcome email sent successfully', [
                'owner_id' => $event->owner->id,
                'owner_email' => $event->owner->email,
                'created_by' => $event->createdBy->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send owner welcome email', [
                'owner_id' => $event->owner->id,
                'owner_email' => $event->owner->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(OwnerCreated $event, \Throwable $exception): void
    {
        Log::error('Owner welcome email job failed', [
            'owner_id' => $event->owner->id,
            'owner_email' => $event->owner->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
