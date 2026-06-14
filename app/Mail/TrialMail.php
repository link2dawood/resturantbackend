<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generic markdown-backed mailable for the trial system. Centralising the
 * envelope/content plumbing here keeps us from spawning a class per email —
 * the subject, markdown view and payload are passed in by TrialMailer.
 */
class TrialMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $markdownView,
        public array $payload = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(markdown: $this->markdownView, with: $this->payload);
    }
}
