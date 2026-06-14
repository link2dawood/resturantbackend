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

    {{-- Card capture (Stripe Elements) — only when not subscribed --}}
    @unless ($subscribed)
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">{{ $onFreeTrial ? 'Add a payment method' : 'Subscribe' }}</h3>

            <form id="payment-form">
                <div class="mb-3">
                    <label class="form-label">Card details</label>
                    <div id="card-element" style="padding: .6rem .75rem; border: 1px solid #d6dce6; border-radius: 8px; background: #fff;"></div>
                    <div id="card-errors" class="text-danger mt-2" role="alert" style="font-size:.9rem;"></div>
                </div>
                <button id="card-submit" type="submit" class="btn btn-success">
                    <span id="card-submit-text">Start subscription</span>
                    <span id="card-submit-spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                </button>
                <p class="text-muted mt-2" style="font-size:.82rem;">
                    Payments are processed securely by Stripe. Your card details never touch our servers.
                </p>
            </form>
        </div>
    </div>

    <form id="subscribe-form" method="POST" action="{{ route('billing.subscribe') }}" class="d-none">
        @csrf
        <input type="hidden" name="payment_method" id="payment_method">
    </form>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        (function () {
            const stripe = Stripe(@json($stripeKey));
            const elements = stripe.elements();
            const card = elements.create('card', { hidePostalCode: false });
            card.mount('#card-element');

            const clientSecret = @json($intent->client_secret);
            const ownerName = @json($owner->name);
            const ownerEmail = @json($owner->email);

            const form = document.getElementById('payment-form');
            const errorEl = document.getElementById('card-errors');
            const submitBtn = document.getElementById('card-submit');
            const spinner = document.getElementById('card-submit-spinner');

            card.on('change', (e) => { errorEl.textContent = e.error ? e.error.message : ''; });

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                errorEl.textContent = '';

                const { setupIntent, error } = await stripe.confirmCardSetup(clientSecret, {
                    payment_method: {
                        card: card,
                        billing_details: { name: ownerName, email: ownerEmail },
                    },
                });

                if (error) {
                    errorEl.textContent = error.message;
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                    return;
                }

                document.getElementById('payment_method').value = setupIntent.payment_method;
                document.getElementById('subscribe-form').submit();
            });
        })();
    </script>
    @endunless
</div>
@endsection
