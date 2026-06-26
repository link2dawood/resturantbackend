<?php

namespace App\Support;

use App\Mail\TrialMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the trial lifecycle emails to both the client (owner) and the sales
 * team. Every send is wrapped defensively so a mail-transport failure never
 * breaks the signup / lockout / request flow that triggered it.
 */
class TrialMailer
{
    protected static function salesEmail(): string
    {
        $email = trim((string) config('trial.sales_email'));

        // Skip the unset/placeholder sales address — emailing it just bounces
        // (mail servers reject @example.com) and spams the log. Set
        // SALES_TEAM_EMAIL in .env to a real inbox to enable these alerts.
        if ($email === '' || str_ends_with(strtolower($email), '@example.com')) {
            return '';
        }

        return $email;
    }

    protected static function send(string $to, string $subject, string $view, array $payload): void
    {
        if (empty($to)) {
            return;
        }

        try {
            Mail::to($to)->send(new TrialMail($subject, $view, $payload));
        } catch (\Throwable $e) {
            Log::error('Trial email failed to send', [
                'to' => $to,
                'view' => $view,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * New self-serve signup: welcome the client, alert the sales team.
     */
    public static function signup(User $owner): void
    {
        self::send(
            $owner->email,
            'Welcome to '.config('app.name').' — your free trial has started',
            'emails.trial.welcome',
            ['owner' => $owner]
        );

        self::send(
            self::salesEmail(),
            'New trial signup: '.$owner->email,
            'emails.trial.signup-sales',
            ['owner' => $owner]
        );
    }

    /**
     * Reminder to the client that the trial is about to end.
     */
    public static function expiringSoon(User $owner): void
    {
        self::send(
            $owner->email,
            'Your '.config('app.name').' trial ends in '.$owner->trialDaysLeft().' day(s)',
            'emails.trial.expiring-soon',
            ['owner' => $owner]
        );
    }

    /**
     * Trial has lapsed: notify both the client and the sales team.
     */
    public static function expired(User $owner): void
    {
        self::send(
            $owner->email,
            'Your '.config('app.name').' trial has expired',
            'emails.trial.expired-client',
            ['owner' => $owner]
        );

        self::send(
            self::salesEmail(),
            'Trial expired: '.$owner->email,
            'emails.trial.expired-sales',
            ['owner' => $owner]
        );
    }

    /**
     * Client clicked "Request to Continue" on the trial-expired screen.
     */
    public static function continueRequested(User $owner): void
    {
        self::send(
            self::salesEmail(),
            'Continue request from '.$owner->email,
            'emails.trial.continue-request',
            ['owner' => $owner]
        );

        self::send(
            $owner->email,
            'We received your request to continue with '.config('app.name'),
            'emails.trial.continue-confirm',
            ['owner' => $owner]
        );
    }
}
