@extends('layouts.tabler')

@section('title', 'Billing & Subscription')

@section('content')
<div class="container-xl py-4" style="max-width: 820px;">
    <h2 class="mb-3">Billing &amp; Subscription</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (request('checkout') === 'success')
        <div class="alert alert-success">Payment received! Your subscription is being activated — this can take a few seconds to appear.</div>
    @elseif (request('checkout') === 'cancelled')
        <div class="alert alert-warning">Checkout was cancelled. You can subscribe whenever you're ready.</div>
    @endif

    {{-- Current status --}}
    <div class="card mb-4">
        <div class="card-body">
            <h3 class="card-title">Current plan</h3>
            @if ($subscribed)
                @php($onGrace = $subscription && $subscription->onGracePeriod())
                @php($pastDue = $subscription && $subscription->hasIncompletePayment())
                <p class="mb-1">
                    <span class="badge bg-{{ $pastDue ? 'warning' : 'success' }}-lt">
                        {{ $pastDue ? 'Payment issue' : ($onGrace ? 'Cancelling' : 'Active') }}
                    </span>
                    <strong>{{ $planName }}</strong> — ${{ number_format($monthlyAmount, 2) }}/month
                </p>
                @if ($onGrace)
                    <p class="text-muted">Your subscription ends on {{ optional($subscription->ends_at)->format('M j, Y') }}.</p>
                @else
                    <p class="text-muted">Renews on the 1st of each month.</p>
                @endif
                @if ($paymentMethod)
                    <p class="text-muted mb-0">Card on file: {{ ucfirst($paymentMethod->card->brand) }} ending {{ $paymentMethod->card->last4 }}</p>
                @endif

                <a href="{{ route('billing.portal') }}" class="btn btn-primary mt-3">
                    Manage subscription &amp; invoices
                </a>
            @elseif ($onFreeTrial)
                <p><span class="badge bg-blue-lt">Free trial</span> {{ $trialDaysLeft }} day(s) remaining.</p>
                <p class="text-muted">Add a card below to continue after your trial. Your first charge is prorated to the 1st, then it's ${{ number_format($monthlyAmount, 2) }}/month on the 1st.</p>
            @else
                <p><span class="badge bg-red-lt">No active plan</span></p>
                <p class="text-muted">Subscribe below to {{ $planName }} — ${{ number_format($monthlyAmount, 2) }}/month, billed on the 1st (first charge prorated from today).</p>
            @endif
        </div>
    </div>

    {{-- Subscribe via Stripe's hosted Checkout — only when not subscribed --}}
    @unless ($subscribed)
    @if (! $stripeConfigured)
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-0">
                Online payments aren't available just yet — we're finishing payment setup.
                Please check back shortly, or contact us to arrange your subscription.
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">{{ $onFreeTrial ? 'Add a payment method' : 'Subscribe' }}</h3>
            <p class="text-muted">
                You'll be taken to Stripe's secure checkout page to enter your card and confirm —
                then you're brought right back here.
            </p>
            <form method="POST" action="{{ route('billing.subscribe') }}">
                @csrf
                <button type="submit" class="btn btn-success btn-lg">
                    Continue to secure checkout &rarr;
                </button>
            </form>
            <p class="text-muted mt-3" style="font-size:.82rem;">
                Payments are processed securely on Stripe. Your card details never touch our servers.
            </p>
        </div>
    </div>
    @endif
    @endunless
</div>
@endsection
