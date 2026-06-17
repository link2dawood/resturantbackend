<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\IncompletePayment;

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

        // Only talk to Stripe if it's actually configured and we need a card form.
        // Missing/invalid keys must NOT 500 the page — show a friendly notice instead.
        $stripeConfigured = filled(config('cashier.secret')) && filled(config('cashier.key'));
        $intent = null;

        if ($stripeConfigured && ! $subscribed) {
            try {
                $intent = $owner->createSetupIntent();
            } catch (\Throwable $e) {
                report($e);
                $stripeConfigured = false;
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
            'nextBillingAnchor' => $this->subscriptions->nextBillingAnchor(),
            'paymentMethod' => $owner->hasDefaultPaymentMethod() ? $owner->defaultPaymentMethod() : null,
            // SetupIntent client secret — Stripe.js confirms the card against this
            // so raw card data never touches our server.
            'intent' => $intent,
            'stripeConfigured' => $stripeConfigured,
            'stripeKey' => config('cashier.key'),
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

        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
        ]);

        try {
            // The owner must exist as a Stripe customer before a card can be
            // attached. createSetupIntent() (on page load) does NOT create the
            // customer, so do it here, then attach the card and subscribe.
            $owner->createOrGetStripeCustomer();
            $owner->updateDefaultPaymentMethod($validated['payment_method']);
            $this->subscriptions->convert($owner, $validated['payment_method']);
        } catch (IncompletePayment $exception) {
            // Card needs extra authentication (SCA/3DS) — hand off to Cashier's
            // payment confirmation page, then return here.
            return redirect()->route('cashier.payment', [
                $exception->payment->id,
                'redirect' => route('billing.show'),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('billing.show')
                ->with('error', 'We could not start your subscription: '.$e->getMessage());
        }

        return redirect()->route('billing.show')
            ->with('success', 'You are subscribed! Your first (prorated) charge is on its way and billing renews on the 1st.');
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
