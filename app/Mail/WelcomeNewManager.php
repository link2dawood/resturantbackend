<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WelcomeNewManager extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $manager;
    public Collection $stores;
    public User $assignedBy;
    public string $loginUrl;
    public string $supportEmail;
    public string $supportPhone;

    /**
     * Create a new message instance.
     */
    public function __construct(User $manager, Collection $stores, User $assignedBy)
    {
        $this->manager = $manager;
        $this->stores = $stores;
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
        return new Envelope(
            subject: 'Welcome to Restaurant Management System - Store Manager Access',
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
            view: 'emails.welcome-new-manager',
            with: [
                'managerName' => $this->manager->name,
                'managerEmail' => $this->manager->email,
                'assignedByName' => $this->assignedBy->name,
                'assignedByRole' => $this->assignedBy->role->label(),
                'stores' => $this->stores,
                'storeCount' => $this->stores->count(),
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