<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ManagerStoreAssignment extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $manager;

    public Collection $newStores;

    public Collection $removedStores;

    public Collection $allStores;

    public User $assignedBy;

    public string $loginUrl;

    public string $supportEmail;

    public string $supportPhone;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $manager,
        Collection $newStores,
        Collection $removedStores,
        Collection $allStores,
        User $assignedBy
    ) {
        $this->manager = $manager;
        $this->newStores = $newStores;
        $this->removedStores = $removedStores;
        $this->allStores = $allStores;
        $this->assignedBy = $assignedBy;
        $this->loginUrl = url('/login');
        $this->supportEmail = config('mail.support_email', 'support@restaurant.com');
        $this->supportPhone = config('mail.support_phone', '(555) 123-4567');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = 'Store Assignment Update - Restaurant Management System';

        if ($this->newStores->isNotEmpty() && $this->removedStores->isNotEmpty()) {
            $subject = 'Store Assignments Updated - Restaurant Management System';
        } elseif ($this->newStores->isNotEmpty()) {
            $subject = 'New Store Assignment - Restaurant Management System';
        } elseif ($this->removedStores->isNotEmpty()) {
            $subject = 'Store Assignment Removed - Restaurant Management System';
        }

        return new Envelope(
            subject: $subject,
            replyTo: [
                ['address' => $this->supportEmail, 'name' => 'Restaurant Support Team'],
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.manager-store-assignment',
            with: [
                'managerName' => $this->manager->name,
                'managerEmail' => $this->manager->email,
                'assignedByName' => $this->assignedBy->name,
                'assignedByRole' => $this->assignedBy->role->label(),
                'newStores' => $this->newStores,
                'removedStores' => $this->removedStores,
                'allStores' => $this->allStores,
                'hasNewStores' => $this->newStores->isNotEmpty(),
                'hasRemovedStores' => $this->removedStores->isNotEmpty(),
                'totalStoreCount' => $this->allStores->count(),
                'loginUrl' => $this->loginUrl,
                'supportEmail' => $this->supportEmail,
                'supportPhone' => $this->supportPhone,
                'companyName' => config('app.name', 'Restaurant Management System'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
