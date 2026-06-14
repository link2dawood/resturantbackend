<x-mail::message>
# Request to continue

A client whose trial expired wants to keep using {{ config('app.name') }}.

- **Name:** {{ $owner->name }}
- **Email:** {{ $owner->email }}
- **Phone:** {{ $owner->personal_phone ?? $owner->corporate_phone ?? '—' }}
- **Requested at:** {{ optional($owner->trial_extension_requested_at)->format('M j, Y g:i A') }}

Please reach out to set up their subscription.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
