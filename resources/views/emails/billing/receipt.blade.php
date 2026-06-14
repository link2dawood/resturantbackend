<x-mail::message>
# Payment received

Hi {{ $owner->name }},

Thanks for your payment to {{ config('app.name') }}.

- **Amount:** ${{ $amount }}
- **Date:** {{ now()->format('M j, Y') }}

@if ($invoiceUrl)
<x-mail::button :url="$invoiceUrl">
View / download invoice
</x-mail::button>
@endif

Your subscription renews on the 1st of next month.

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
