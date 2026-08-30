@extends('layouts.tabler')

@section('title', 'Check In Delivery')

@php
    $fmt = fn ($n) => $n === null ? '' : rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    $money = fn ($n) => '$'.number_format((float) $n, 2);
@endphp

@push('styles')
<style>
    .recv-input { font-size: 1.2rem; font-weight: 600; text-align: right; min-height: 50px; }
    .recv-row.is-over  { background: #fff4f4; }
    .recv-row.is-short { background: #fffaf0; }
    .recv-row.is-exact { background: #f4fbf6; }
    .sticky-save {
        position: sticky; bottom: 0; z-index: 1030; background: #fff;
        border-top: 1px solid #e0e0e0; padding: 0.75rem 1rem; margin: 0 -0.75rem;
        box-shadow: 0 -2px 8px rgba(0,0,0,0.06);
    }
    @media (min-width: 768px) { .sticky-save { margin: 0; } }
</style>
@endpush

@section('content')
<div class="container-xl mt-3 mb-5" style="max-width: 900px;">

    <div class="mb-3">
        <a href="{{ route('admin.orders.show', $order) }}" class="text-muted text-decoration-none small">&larr; Back to order</a>
        <h1 class="mb-1 mt-2" style="font-size: 1.5rem;">Check In Delivery</h1>
        <div class="text-muted">
            {{ $order->vendor->vendor_name ?? 'Vendor' }} &middot;
            Order {{ $order->order_sequence }} &middot;
            week of {{ $order->week_start_date->format('M j, Y') }}
        </div>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="alert alert-info py-2">
        <small>
            Enter what actually arrived, not what was ordered. Anything that does not match is
            flagged so it can be raised with the vendor. Leave a line blank if you have not checked it yet.
        </small>
    </div>

    <form method="POST" action="{{ route('admin.orders.received', $order) }}">
        @csrf @method('PATCH')

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end" style="width: 110px;">Ordered</th>
                                <th style="width: 170px;">Arrived</th>
                                <th style="width: 130px;">Difference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $line)
                            <tr class="recv-row" data-line="{{ $line->id }}">
                                <td>
                                    <strong>{{ $line->inventoryItem->name ?? 'Item' }}</strong>
                                    @if(filled($line->notes))
                                        <div class="text-muted small">{{ $line->notes }}</div>
                                    @endif
                                    <input type="text" class="form-control form-control-sm mt-1"
                                           name="received_notes[{{ $line->id }}]" maxlength="255"
                                           value="{{ $line->received_notes }}"
                                           placeholder="Note, e.g. 2 boxes damaged">
                                </td>
                                <td class="text-end">
                                    <strong>{{ $fmt($line->quantity) }}</strong>
                                    <div class="text-muted small">{{ $line->unit }}</div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" inputmode="decimal" step="0.01" min="0"
                                               class="form-control recv-input"
                                               name="received[{{ $line->id }}]"
                                               data-ordered="{{ (float) $line->quantity }}"
                                               value="{{ $line->quantity_received !== null ? $fmt($line->quantity_received) : $fmt($line->quantity) }}"
                                               aria-label="How many {{ $line->unit }} of {{ $line->inventoryItem->name ?? 'this item' }} arrived">
                                        <span class="input-group-text">{{ $line->unit }}</span>
                                    </div>
                                </td>
                                <td data-delta-for="{{ $line->id }}" class="small"></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No lines on this order.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($order->items->isNotEmpty())
        <div class="sticky-save mt-3">
            <div id="receiveSummary" class="small text-muted mb-2"></div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="button" class="btn btn-outline-primary flex-fill" onclick="matchOrder()">Everything arrived as ordered</button>
                <button type="submit" class="btn btn-success flex-fill">Confirm delivery</button>
            </div>
        </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
// Flag differences as they are typed, so a mismatch is visible before the
// delivery driver has left rather than a week later.
function refreshDeltas() {
    let over = 0, short = 0, unchecked = 0;

    document.querySelectorAll('.recv-row').forEach(row => {
        const input = row.querySelector('.recv-input');
        if (!input) return;

        const ordered = parseFloat(input.dataset.ordered);
        const cell = row.querySelector('[data-delta-for]');
        row.classList.remove('is-over', 'is-short', 'is-exact');

        if (input.value === '') {
            cell.textContent = 'not checked';
            cell.className = 'small text-muted';
            unchecked++;
            return;
        }

        const arrived = parseFloat(input.value);
        const delta = Math.round((arrived - ordered) * 10000) / 10000;

        if (Math.abs(delta) < 0.0001) {
            cell.textContent = 'matches';
            cell.className = 'small text-success';
            row.classList.add('is-exact');
        } else if (delta > 0) {
            cell.textContent = `${delta} more than ordered`;
            cell.className = 'small text-danger fw-bold';
            row.classList.add('is-over');
            over++;
        } else {
            cell.textContent = `${Math.abs(delta)} short`;
            cell.className = 'small text-warning fw-bold';
            row.classList.add('is-short');
            short++;
        }
    });

    const parts = [];
    if (over) parts.push(`${over} line(s) over-delivered`);
    if (short) parts.push(`${short} line(s) short`);
    if (unchecked) parts.push(`${unchecked} not checked`);

    const summary = document.getElementById('receiveSummary');
    summary.textContent = parts.length ? parts.join(' · ') : 'Everything matches the order.';
    summary.className = 'small mb-2 ' + (over ? 'text-danger' : (short ? 'text-warning' : 'text-success'));
}

function matchOrder() {
    document.querySelectorAll('.recv-input').forEach(input => {
        input.value = parseFloat(input.dataset.ordered);
    });
    refreshDeltas();
}

document.querySelectorAll('.recv-input').forEach(input => {
    input.addEventListener('input', refreshDeltas);
});

refreshDeltas();
</script>
@endpush
