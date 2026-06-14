<x-mail::message>
# Welcome to {{ config('app.name') }}, {{ $owner->name }}!

Your **{{ (int) config('trial.days', 30) }}-day free trial** is now active.

- Trial started: **{{ optional($owner->trial_started_at)->format('M j, Y') }}**
- Trial ends: **{{ optional($owner->trial_ends_at)->format('M j, Y') }}**

You have full access to your workspace during the trial. When it ends you can request to continue and our team will help you upgrade.

<x-mail::button :url="route('home')">
Go to your dashboard
</x-mail::button>

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
