<x-mail::message>
# Your subscription has ended

Hi {{ $owner->name }},

Your {{ config('app.name') }} subscription has been cancelled and access is now paused. Your data is safe — resubscribe anytime to pick up where you left off.

<x-mail::button :url="route('billing.show')">
Resubscribe
</x-mail::button>

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
