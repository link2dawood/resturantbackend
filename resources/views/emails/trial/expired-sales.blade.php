<x-mail::message>
# Trial expired

A trial just lapsed and the workspace is now locked out.

- **Name:** {{ $owner->name }}
- **Email:** {{ $owner->email }}
- **Trial ended:** {{ optional($owner->trial_ends_at)->format('M j, Y') }}

Follow up to win them back.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
