@extends('layouts.tabler')

@section('title', 'Vendor Order Report')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    $money = fn ($n) => '$'.number_format((float) $n, 2);
    $vendor = $order->vendor;
    $store = $order->store;
    $orderDate = $order->placed_at ?? $order->created_at;
@endphp

@push('styles')
<style>
    .report-sheet {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 2rem;
        max-width: 820px;
        margin: 0 auto;
    }
    .report-sheet h1 { font-size: 1.5rem; margin: 0; }
    .report-meta dt { font-weight: 500; color: #5f6368; font-size: 0.813rem; }
    .report-meta dd { margin-bottom: 0.5rem; }
    .report-table th { border-bottom: 2px solid #202124; font-size: 0.813rem; text-transform: uppercase; letter-spacing: 0.03em; }
    .report-table tfoot th { border-top: 2px solid #202124; border-bottom: none; }

    /* Print: drop the app chrome and everything interactive, keep the sheet. */
    @media print {
        @page { size: letter portrait; margin: 0.6in; }
        /* The Tabler shell paints grey on .page and .main-content as well as
           body, which prints as a grey band above and below the sheet. */
        html, body, .page, .main-content { background: #fff !important; }
        nav, header, footer, .navbar, .d-print-none { display: none !important; }
        .report-sheet {
            border: 0; border-radius: 0; padding: 0; max-width: none;
            box-shadow: none;
        }
        .container-xl { padding: 0 !important; max-width: none !important; }
        .report-table { font-size: 11pt; }
        /* Never split a line item across pages. */
        .report-table tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        a[href]:after { content: ""; }
    }
</style>
@endpush

@section('content')
<div class="container-xl mt-4 mb-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 d-print-none">
        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-link">&larr; Back to order</a>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('admin.orders.report.pdf', $order) }}" target="_blank" rel="noopener">Download PDF</a>
            @if($vendor?->order_method === 'email' && filled($vendor?->contact_email))
                <a class="btn btn-outline-primary" id="emailBtn" href="#">Email {{ $vendor->vendor_name }}</a>
            @elseif($vendor?->order_method === 'online' && filled($vendor?->website))
                <a class="btn btn-outline-primary" href="{{ $vendor->website }}" target="_blank" rel="noopener">Open {{ $vendor->vendor_name }} site</a>
            @endif
            <button class="btn btn-primary" onclick="window.print()">Print</button>
        </div>
    </div>

    @unless(filled($vendor?->order_method))
        <div class="alert alert-warning d-print-none">
            <small>
                No ordering method is set for {{ $vendor->vendor_name ?? 'this vendor' }}.
                <a href="{{ route('admin.vendors.index', ['search' => $vendor->vendor_name ?? '']) }}">Set one</a>
                so whoever places the order knows whether to phone, go online, or walk in.
            </small>
        </div>
    @endunless

    @if($vendor)
    <div class="alert alert-info d-print-none">
        <strong>{{ $vendor->order_method_label }}</strong>
        <div class="small">{{ $vendor->order_instruction }}</div>
        @if(filled($vendor->order_notes))
            <div class="small mt-1">{{ $vendor->order_notes }}</div>
        @endif
    </div>
    @endif

    <div class="report-sheet">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
            <div>
                <h1>{{ $store->store_info ?? 'Store' }}</h1>
                <div class="text-muted" style="font-size: 0.875rem;">
                    @if(filled($store->address)){{ $store->address }}<br>@endif
                    @if(filled($store->city)){{ $store->city }},@endif {{ $store->state }} {{ $store->zip }}
                    @if(filled($store->phone))<br>{{ $store->phone }}@endif
                </div>
            </div>
            <div class="text-end">
                <div style="font-size: 1.25rem; font-weight: 500;">Purchase Order</div>
                <div class="text-muted" style="font-size: 0.875rem;">
                    Order #{{ $order->order_sequence }} &middot; week of {{ $order->week_start_date->format('M j, Y') }}<br>
                    {{ $orderDate->format('M j, Y') }}
                    <span class="badge bg-secondary text-uppercase d-print-none">{{ $order->status }}</span>
                </div>
            </div>
        </div>

        <div class="row report-meta mb-4">
            <div class="col-sm-6">
                <dl class="mb-0">
                    <dt>Vendor</dt>
                    <dd>
                        <strong>{{ $vendor->vendor_name ?? 'Vendor' }}</strong>
                        @if(filled($vendor?->contact_name))<br>{{ $vendor->contact_name }}@endif
                        @if(filled($vendor?->contact_phone))<br>{{ $vendor->contact_phone }}@endif
                        @if(filled($vendor?->contact_email))<br>{{ $vendor->contact_email }}@endif
                        @if(filled($vendor?->address))<br>{{ $vendor->address }}@endif
                    </dd>
                </dl>
            </div>
            <div class="col-sm-6">
                <dl class="mb-0">
                    <dt>Deliver to</dt>
                    <dd>
                        <strong>{{ $store->store_info ?? 'Store' }}</strong>
                        @if(filled($store->contact_name))<br>{{ $store->contact_name }}@endif
                        @if(filled($store->address))<br>{{ $store->address }}@endif
                        @if(filled($store->city))<br>{{ $store->city }}, {{ $store->state }} {{ $store->zip }}@endif
                    </dd>
                </dl>
            </div>
        </div>

        <table class="table report-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-end" style="width: 110px;">Quantity</th>
                    <th style="width: 80px;">Unit</th>
                    <th class="text-end" style="width: 110px;">Unit price</th>
                    <th class="text-end" style="width: 110px;">Line total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $line)
                <tr>
                    <td>
                        {{ $line->inventoryItem->name ?? 'Item' }}
                        @if(filled($line->notes))
                            <div class="text-muted" style="font-size: 0.813rem;">{{ $line->notes }}</div>
                        @endif
                    </td>
                    <td class="text-end">{{ $fmt($line->quantity) }}</td>
                    <td>{{ $line->unit }}</td>
                    <td class="text-end">{{ $line->unit_price !== null ? $money($line->unit_price) : '—' }}</td>
                    <td class="text-end">{{ $line->line_total !== null ? $money($line->line_total) : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No items on this order.</td></tr>
                @endforelse
            </tbody>
            @if($order->total > 0)
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Total</th>
                    <th class="text-end">{{ $money($order->total) }}</th>
                </tr>
            </tfoot>
            @endif
        </table>

        @if(filled($order->notes))
            <div class="mt-3">
                <div style="font-weight: 500; font-size: 0.813rem; color: #5f6368;">NOTES</div>
                <div>{{ $order->notes }}</div>
            </div>
        @endif
    </div>

<div class="toast-container position-fixed bottom-0 end-0 p-3 d-print-none" id="toastContainer" style="z-index: 1080;"></div>
@endsection

@push('scripts')
<script>
const ORDER_TEXT = @json($plainText);
const ORDER_SUBJECT = @json($subject);
const VENDOR_EMAIL = @json($vendor?->contact_email);

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : 'success'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `<div class="d-flex"><div class="toast-body"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
    toast.querySelector('.toast-body').textContent = message;
    container.appendChild(toast);
    const instance = new bootstrap.Toast(toast, { delay: 2500 });
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
    instance.show();
}

// mailto bodies are capped by the browser and the OS mail client, so a long
// order is truncated with a pointer back to the printable page rather than
// arriving cut off mid-item.
const MAILTO_BODY_LIMIT = 1800;

if (VENDOR_EMAIL) {
    const emailBtn = document.getElementById('emailBtn');
    let body = ORDER_TEXT;

    if (body.length > MAILTO_BODY_LIMIT) {
        body = body.slice(0, MAILTO_BODY_LIMIT)
            + '\n\n[Order continues — the full list is attached or available at]\n'
            + window.location.href;
    }

    emailBtn.href = 'mailto:' + encodeURIComponent(VENDOR_EMAIL)
        + '?subject=' + encodeURIComponent(ORDER_SUBJECT)
        + '&body=' + encodeURIComponent(body);
}
</script>
@endpush
