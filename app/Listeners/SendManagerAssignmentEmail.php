<?php

namespace App\Listeners;

use App\Events\ManagerAssignedToStores;
use App\Mail\WelcomeNewManager;
use App\Mail\ManagerStoreAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendManagerAssignmentEmail implements ShouldQueue
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
    public function handle(ManagerAssignedToStores $event): void
    {
        try {
            // Ensure manager has a valid email address
            if (empty($event->manager->email) || !filter_var($event->manager->email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Cannot send manager assignment email: Invalid email address', [
                    'manager_id' => $event->manager->id,
                    'manager_email' => $event->manager->email,
                ]);
                return;
            }

            // For new managers, send welcome email
            if ($event->isNewManager) {
                $this->sendWelcomeEmail($event);
            } else {
                // For existing managers, send assignment update email
                $this->sendAssignmentUpdateEmail($event);
            }

            Log::info('Manager assignment email sent successfully', [
                'manager_id' => $event->manager->id,
                'manager_email' => $event->manager->email,
                'assigned_by' => $event->assignedBy->id,
                'is_new_manager' => $event->isNewManager,
                'store_count' => $event->stores->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send manager assignment email', [
                'manager_id' => $event->manager->id,
                'manager_email' => $event->manager->email,
                'assigned_by' => $event->assignedBy->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to trigger job retry if using queues
            throw $e;
        }
    }

    /**
     * Send welcome email to new manager
     */
    private function sendWelcomeEmail(ManagerAssignedToStores $event): void
    {
        $welcomeMail = new WelcomeNewManager(
            $event->manager,
            $event->stores,
            $event->assignedBy
        );

        Mail::to($event->manager->email)
            ->send($welcomeMail);
    }

    /**
     * Send assignment update email to existing manager
     */
    private function sendAssignmentUpdateEmail(ManagerAssignedToStores $event): void
    {
        $newStores = $event->getNewlyAssignedStores();
        $removedStores = $event->getRemovedStores();

        // Only send email if there are actual changes
        if ($newStores->isEmpty() && $removedStores->isEmpty()) {
            Log::info('No store assignment changes detected, skipping email', [
                'manager_id' => $event->manager->id,
            ]);
            return;
        }

        $assignmentMail = new ManagerStoreAssignment(
            $event->manager,
            $newStores,
            $removedStores,
            $event->stores,
            $event->assignedBy
        );

        Mail::to($event->manager->email)
            ->send($assignmentMail);
    }

    /**
     * Handle a job failure.
     */
    public function failed(ManagerAssignedToStores $event, \Throwable $exception): void
    {
        Log::error('Manager assignment email job failed permanently', [
            'manager_id' => $event->manager->id,
            'manager_email' => $event->manager->email,
            'assigned_by' => $event->assignedBy->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // You could send a notification to admins here
        // or store the failed email in a retry queue
    }
}