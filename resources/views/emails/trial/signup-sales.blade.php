<x-mail::message>
# New trial signup

A new account just started a free trial.

- **Name:** {{ $owner->name }}
- **Email:** {{ $owner->email }}
- **Trial ends:** {{ optional($owner->trial_ends_at)->format('M j, Y') }}
- **Signed up:** {{ optional($owner->created_at)->format('M j, Y g:i A') }}

Reach out before the trial ends to help them convert.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
