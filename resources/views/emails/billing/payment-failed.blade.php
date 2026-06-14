<x-mail::message>
# Your payment didn't go through

Hi {{ $owner->name }},

We couldn't process your latest {{ config('app.name') }} payment. This usually means your card expired or had insufficient funds.

@if ($nextAttempt)
We'll automatically try again on **{{ $nextAttempt }}**. To avoid any interruption, please update your card now.
@else
Please update your payment method to keep your subscription active.
@endif

<x-mail::button :url="route('billing.show')">
Update payment method
</x-mail::button>

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
