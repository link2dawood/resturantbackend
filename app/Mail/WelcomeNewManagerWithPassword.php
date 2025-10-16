<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WelcomeNewManagerWithPassword extends Mailable
{
    use SerializesModels;

    public User $manager;

    public Collection $stores;

    public User $createdBy;

    public string $temporaryPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $manager, Collection $stores, User $createdBy, string $temporaryPassword)
    {
        $this->manager = $manager;
        $this->stores = $stores;
        $this->createdBy = $createdBy;
        $this->temporaryPassword = $temporaryPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name').' - Manager Account Created'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.manager.welcome',
            with: [
                'manager' => $this->manager,
                'stores' => $this->stores,
                'createdBy' => $this->createdBy,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => route('login'),
                'appName' => config('app.name'),
            ]
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
