<x-mail::message>
# Your trial ends soon

Hi {{ $owner->name }},

Your {{ config('app.name') }} free trial ends on **{{ optional($owner->trial_ends_at)->format('M j, Y') }}** — that's **{{ $owner->trialDaysLeft() }} day(s)** away.

To keep your workspace active after the trial, request to continue and our team will help you upgrade.

<x-mail::button :url="route('trial.expired')">
Continue my subscription
</x-mail::button>

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
