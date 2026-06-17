<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions)
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Resolve the workspace owner (billing entity) for the current user, or null
     * if the current user isn't allowed to manage billing.
     */
    private function billingOwner(Request $request)
    {
        $user = $request->user();
        $owner = $user->billingOwner();

        // Only the owner themselves manages billing (not managers).
        return ($owner && $user->is($owner)) ? $owner : null;
    }

    /**
     * Billing page: current plan/status + a PCI-compliant Stripe Elements card
     * form (backed by a SetupIntent) for capturing a card.
     */
    public function show(Request $request)
    {
        $owner = $this->billingOwner($request);

        if (! $owner) {
            return redirect()->route('home')
                ->with('error', 'Only the account owner can manage billing.');
        }

        $subscription = $owner->subscription(config('subscription.type'));
        $subscribed = $owner->subscribed(config('subscription.type'));

        // Hosted Stripe Checkout: the card form lives on Stripe, so we just need
        // keys + a price configured. Missing config shows a friendly notice (no 500).
        $stripeConfigured = filled(config('cashier.secret'))
            && filled(config('cashier.key'))
            && filled(config('subscription.price_id'));

        // Card on file (only for subscribed owners) — guarded so a Stripe hiccup
        // can't 500 the page.
        $paymentMethod = null;
        if ($subscribed) {
            try {
                $paymentMethod = $owner->hasDefaultPaymentMethod() ? $owner->defaultPaymentMethod() : null;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('billing.show', [
            'owner' => $owner,
            'subscription' => $subscription,
            'subscribed' => $subscribed,
            'onFreeTrial' => $owner->onFreeTrial(),
            'trialDaysLeft' => $owner->trialDaysLeft(),
            'planName' => config('subscription.plan_name'),
            'monthlyAmount' => config('subscription.monthly_amount') / 100,
            'paymentMethod' => $paymentMethod,
            'stripeConfigured' => $stripeConfigured,
            'billingEnabled' => (bool) config('subscription.enabled'),
        ]);
    }

    /**
     * Capture the card (PaymentMethod id from Stripe.js) and start the paid
     * subscription, prorated and anchored to the 1st.
     */
    public function subscribe(Request $request)
    {
        $owner = $this->billingOwner($request);

        if (! $owner) {
            return redirect()->route('home')
                ->with('error', 'Only the account owner can manage billing.');
        }

        if (! config('subscription.enabled')) {
            return redirect()->route('billing.show')
                ->with('error', 'Subscriptions aren’t available yet — you’re on a free trial.');
        }

        if ($owner->subscribed(config('subscription.type'))) {
            return redirect()->route('billing.show')
                ->with('success', 'You already have an active subscription.');
        }

        if (! filled(config('cashier.secret')) || ! filled(config('subscription.price_id'))) {
            return redirect()->route('billing.show')
                ->with('error', 'Billing isn’t fully configured yet — please try again later.');
        }

        try {
            // Hand off to Stripe's hosted Checkout. The card form, PCI handling and
            // SCA all live on Stripe. Billing is anchored to the 1st of next month;
            // Stripe prorates the first partial period automatically. The
            // subscription is created back in our DB via the Stripe webhook.
            return $owner->newSubscription(config('subscription.type'), config('subscription.price_id'))
                ->checkout([
                    'success_url' => route('billing.show').'?checkout=success',
                    'cancel_url' => route('billing.show').'?checkout=cancelled',
                    'subscription_data' => [
                        'billing_cycle_anchor' => $this->subscriptions->nextBillingAnchor()->getTimestamp(),
                    ],
                ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('billing.show')
                ->with('error', 'We could not start checkout: '.$e->getMessage());
        }
    }

    /**
     * Redirect to the Stripe Customer Portal (update card, view invoices, cancel).
     */
    public function portal(Request $request)
    {
        $owner = $this->billingOwner($request);

        if (! $owner || ! $owner->hasStripeId()) {
            return redirect()->route('billing.show')
                ->with('error', 'No billing account yet — add a card first.');
        }

        return $owner->redirectToBillingPortal(route('billing.show'));
    }
}
