<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeNewOwner extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $owner;

    public string $temporaryPassword;

    public User $createdBy;

    /**
     * Create a new message instance.
     */
    public function __construct(User $owner, string $temporaryPassword, User $createdBy)
    {
        $this->owner = $owner;
        $this->temporaryPassword = $temporaryPassword;
        $this->createdBy = $createdBy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to '.config('app.name').' - Owner Account Created',
            replyTo: [
                config('mail.from.address') => config('mail.from.name'),
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.owner.welcome',
            with: [
                'owner' => $this->owner,
                'temporaryPassword' => $this->temporaryPassword,
                'createdBy' => $this->createdBy,
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
