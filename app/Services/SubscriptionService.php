<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;

/**
 * Phase 4 — Payments.
 *
 * Encapsulates trial→paid conversion. Billing is anchored to the 1st of every
 * month; the first charge is prorated from the conversion date to the next 1st
 * (Stripe creates that proration on the initial invoice and charges it
 * immediately against the customer's payment method).
 */
class SubscriptionService
{
    /**
     * The next billing-cycle anchor: the 1st of next month (midnight UTC).
     * Stripe prorates the partial period between "now" and this anchor.
     */
    public function nextBillingAnchor(?Carbon $from = null): Carbon
    {
        return ($from ?? now())->copy()->addMonthNoOverflow()->startOfMonth();
    }

    /**
     * Convert an owner to a paid subscription.
     *
     * @param  string|null  $paymentMethod  Stripe PaymentMethod id. If null, the
     *                                       customer's existing default PM is used.
     */
    public function convert(User $owner, ?string $paymentMethod = null): Subscription
    {
        // Idempotent: don't double-subscribe.
        if ($owner->subscribed(config('subscription.type'))) {
            return $owner->subscription(config('subscription.type'));
        }

        $anchor = $this->nextBillingAnchor();

        $subscription = $owner
            ->newSubscription(config('subscription.type'), config('subscription.price_id'))
            ->create($paymentMethod, [], [
                // Anchor every renewal to the 1st; charge a prorated amount now
                // for the conversion-date → 1st window.
                'billing_cycle_anchor' => $anchor->getTimestamp(),
                'proration_behavior' => 'create_prorations',
            ]);

        $owner->subscription_status = User::SUBSCRIPTION_ACTIVE;
        $owner->save();

        return $subscription;
    }

    /**
     * Can this owner be auto-converted at trial end? (Stripe customer + a card
     * on file, and not already subscribed.)
     */
    public function canAutoConvert(User $owner): bool
    {
        return $owner->hasStripeId()
            && $owner->hasDefaultPaymentMethod()
            && ! $owner->subscribed(config('subscription.type'));
    }
}
