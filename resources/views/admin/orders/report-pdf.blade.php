{{-- DomPDF sheet. Deliberately plain: DomPDF has no flexbox or grid, so this
     uses tables for layout and inline-ish CSS only. --}}
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    $money = fn ($n) => '$'.number_format((float) $n, 2);
    $vendor = $order->vendor;
    $store = $order->store;
    $orderDate = $order->placed_at ?? $order->created_at;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order {{ $order->order_sequence }} — {{ $vendor->vendor_name ?? 'Vendor' }}</title>
    <style>
        @page { margin: 0.6in; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #202124; }
        h1 { font-size: 15pt; margin: 0 0 2pt 0; }
        .muted { color: #5f6368; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; }
        table.layout td { vertical-align: top; padding: 0; }
        table.items { margin-top: 14pt; }
        table.items th {
            border-bottom: 1.5pt solid #202124; text-align: left;
            font-size: 8pt; text-transform: uppercase; padding: 4pt 3pt;
        }
        table.items td { border-bottom: 0.5pt solid #dadce0; padding: 5pt 3pt; }
        table.items tfoot td { border-top: 1.5pt solid #202124; border-bottom: none; font-weight: bold; padding-top: 6pt; }
        .right { text-align: right; }
        .label { font-size: 8pt; text-transform: uppercase; color: #5f6368; margin-bottom: 2pt; }
        .box { margin-top: 12pt; }
        .note { font-size: 8pt; color: #5f6368; }
    </style>
</head>
<body>

<table class="layout">
    <tr>
        <td style="width: 55%;">
            <h1>{{ $store->store_info ?? 'Store' }}</h1>
            <div class="muted">
                @if(filled($store->address)){{ $store->address }}<br>@endif
                @if(filled($store->city)){{ $store->city }},@endif {{ $store->state }} {{ $store->zip }}
                @if(filled($store->phone))<br>{{ $store->phone }}@endif
            </div>
        </td>
        <td class="right">
            <div style="font-size: 12pt;">Purchase Order</div>
            <div class="muted">
                Order #{{ $order->order_sequence }}<br>
                Week of {{ $order->week_start_date->format('M j, Y') }}<br>
                {{ $orderDate->format('M j, Y') }}
            </div>
        </td>
    </tr>
</table>

<table class="layout box">
    <tr>
        <td style="width: 50%; padding-right: 12pt;">
            <div class="label">Vendor</div>
            <strong>{{ $vendor->vendor_name ?? 'Vendor' }}</strong>
            @if(filled($vendor?->order_method_label))
                <span class="muted">({{ $vendor->order_method_label }})</span>
            @endif
            <br>
            <span class="muted">
                @if(filled($vendor?->contact_name)){{ $vendor->contact_name }}<br>@endif
                @if(filled($vendor?->contact_phone)){{ $vendor->contact_phone }}<br>@endif
                @if(filled($vendor?->contact_email)){{ $vendor->contact_email }}<br>@endif
                @if(filled($vendor?->address)){{ $vendor->address }}@endif
                @if(filled($vendor?->order_notes))<br>{{ $vendor->order_notes }}@endif
            </span>
        </td>
        <td style="width: 50%;">
            <div class="label">Deliver to</div>
            <strong>{{ $store->store_info ?? 'Store' }}</strong><br>
            <span class="muted">
                @if(filled($store->contact_name)){{ $store->contact_name }}<br>@endif
                @if(filled($store->address)){{ $store->address }}<br>@endif
                @if(filled($store->city)){{ $store->city }}, {{ $store->state }} {{ $store->zip }}@endif
            </span>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Item</th>
            <th class="right" style="width: 70pt;">Quantity</th>
            <th style="width: 50pt;">Unit</th>
            <th class="right" style="width: 65pt;">Unit price</th>
            <th class="right" style="width: 70pt;">Line total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($order->items as $line)
        <tr>
            <td>
                {{ $line->inventoryItem->name ?? 'Item' }}
                @if(filled($line->notes))<br><span class="note">{{ $line->notes }}</span>@endif
            </td>
            <td class="right">{{ $fmt($line->quantity) }}</td>
            <td>{{ $line->unit }}</td>
            <td class="right">{{ $line->unit_price !== null ? $money($line->unit_price) : '—' }}</td>
            <td class="right">{{ $line->line_total !== null ? $money($line->line_total) : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="muted">No items on this order.</td></tr>
        @endforelse
    </tbody>
    @if($order->total > 0)
    <tfoot>
        <tr>
            <td colspan="4" class="right">Total</td>
            <td class="right">{{ $money($order->total) }}</td>
        </tr>
    </tfoot>
    @endif
</table>

@if(filled($order->notes))
<div class="box">
    <div class="label">Notes</div>
    {{ $order->notes }}
</div>
@endif

</body>
</html>
