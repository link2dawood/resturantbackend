<?php

namespace App\Support;

use App\Mail\BillingMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Billing lifecycle emails (receipt, failed payment / dunning, cancellation).
 * Every send is defensive so a transport failure never breaks webhook handling.
 */
class BillingMailer
{
    protected static function send(string $to, string $subject, string $view, array $payload): void
    {
        if (empty($to)) {
            return;
        }

        try {
            Mail::to($to)->send(new BillingMail($subject, $view, $payload));
        } catch (\Throwable $e) {
            Log::error('Billing email failed to send', [
                'to' => $to,
                'view' => $view,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Receipt after a successful payment. $amount is a formatted string e.g. "99.00". */
    public static function receipt(User $owner, string $amount, ?string $invoiceUrl = null): void
    {
        self::send($owner->email, 'Your '.config('app.name').' receipt', 'emails.billing.receipt', [
            'owner' => $owner,
            'amount' => $amount,
            'invoiceUrl' => $invoiceUrl,
        ]);
    }

    /** Failed payment — start of the dunning flow. $attemptCount + $nextAttempt optional. */
    public static function paymentFailed(User $owner, ?string $nextAttempt = null): void
    {
        self::send($owner->email, 'Action needed: your '.config('app.name').' payment failed', 'emails.billing.payment-failed', [
            'owner' => $owner,
            'nextAttempt' => $nextAttempt,
        ]);
    }

    /** Subscription cancelled / ended. */
    public static function cancelled(User $owner): void
    {
        self::send($owner->email, 'Your '.config('app.name').' subscription was cancelled', 'emails.billing.cancelled', [
            'owner' => $owner,
        ]);
    }
}
