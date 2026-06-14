<x-mail::message>
# Your free trial has ended

Hi {{ $owner->name }},

Your {{ config('app.name') }} free trial ended on **{{ optional($owner->trial_ends_at)->format('M j, Y') }}**. Access to your workspace is paused until you continue.

Click below and our team will get you set up to keep going — your data is safe and waiting.

<x-mail::button :url="route('trial.expired')">
Request to continue
</x-mail::button>

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
