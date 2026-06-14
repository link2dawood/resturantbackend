<x-mail::message>
# We got your request

Hi {{ $owner->name }},

Thanks for letting us know you'd like to continue with {{ config('app.name') }}. Our team has been notified and will reach out shortly to help you upgrade and restore access.

Your data is safe in the meantime.

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
