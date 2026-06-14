<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\BillingMailer;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * Phase 4 — Payments.
 *
 * Layers our business logic on top of Cashier's built-in webhook DB sync.
 * Cashier already keeps the subscriptions table in step with Stripe; here we
 * send receipts/dunning emails and mirror subscription state onto the owner's
 * `subscription_status` flag (which drives the trial/access lockout).
 *
 * Handled events: invoice.payment_succeeded, invoice.payment_failed,
 * customer.subscription.updated, customer.subscription.deleted.
 */
class HandleStripeWebhook
{
    public function handle(WebhookHandled $event): void
    {
        $payload = $event->payload;
        $type = $payload['type'] ?? null;
        $object = $payload['data']['object'] ?? [];

        match ($type) {
            'invoice.payment_succeeded' => $this->onPaymentSucceeded($object),
            'invoice.payment_failed' => $this->onPaymentFailed($object),
            'customer.subscription.updated' => $this->onSubscriptionUpdated($object),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($object),
            default => null,
        };
    }

    private function owner(array $object): ?User
    {
        $customerId = $object['customer'] ?? null;

        return $customerId ? Cashier::findBillable($customerId) : null;
    }

    private function onPaymentSucceeded(array $invoice): void
    {
        $owner = $this->owner($invoice);
        if (! $owner) {
            return;
        }

        // Payment cleared → ensure the workspace is unlocked.
        if ($owner->subscription_status !== User::SUBSCRIPTION_ACTIVE) {
            $owner->subscription_status = User::SUBSCRIPTION_ACTIVE;
            $owner->save();
        }

        $amount = number_format(((int) ($invoice['amount_paid'] ?? 0)) / 100, 2);
        BillingMailer::receipt($owner, $amount, $invoice['hosted_invoice_url'] ?? null);
    }

    private function onPaymentFailed(array $invoice): void
    {
        $owner = $this->owner($invoice);
        if (! $owner) {
            return;
        }

        // Dunning: notify the customer. We do NOT lock them out here — Stripe's
        // smart retries keep trying; lockout only happens if Stripe ultimately
        // cancels the subscription (customer.subscription.deleted).
        $nextAttempt = isset($invoice['next_payment_attempt']) && $invoice['next_payment_attempt']
            ? Carbon::createFromTimestamp($invoice['next_payment_attempt'])->format('M j, Y')
            : null;

        BillingMailer::paymentFailed($owner, $nextAttempt);
    }

    private function onSubscriptionUpdated(array $subscription): void
    {
        $owner = $this->owner($subscription);
        if (! $owner) {
            return;
        }

        $status = $subscription['status'] ?? null;

        // Terminal states → lock out; otherwise keep access (incl. past_due,
        // which is still in the dunning/retry window).
        if (in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
            $owner->subscription_status = User::SUBSCRIPTION_EXPIRED;
            $owner->save();
        } elseif (in_array($status, ['active', 'trialing'], true)) {
            $owner->subscription_status = User::SUBSCRIPTION_ACTIVE;
            $owner->save();
        }
    }

    private function onSubscriptionDeleted(array $subscription): void
    {
        $owner = $this->owner($subscription);
        if (! $owner) {
            return;
        }

        $owner->subscription_status = User::SUBSCRIPTION_EXPIRED;
        $owner->save();

        BillingMailer::cancelled($owner);
    }
}
