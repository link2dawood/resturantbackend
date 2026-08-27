@extends('layouts.tabler')

@section('title', 'Order Suggestions')

@php
    $trim = fn ($n) => $n === null ? '' : rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
@endphp

@push('styles')
<style>
    .qty-input { font-size: 1.15rem; font-weight: 600; text-align: right; min-height: 48px; }
    .suggestion-row.no-order { opacity: 0.55; }
    .suggestion-row.overridden { background: #fff8e6; }
    .sticky-save {
        position: sticky; bottom: 0; z-index: 1030;
        background: #fff; border-top: 1px solid #e0e0e0;
        padding: 0.75rem 1rem; margin: 0 -0.75rem;
        box-shadow: 0 -2px 8px rgba(0,0,0,0.06);
    }
    @media (min-width: 768px) { .sticky-save { margin: 0; border-radius: 0 0 12px 12px; } }
</style>
@endpush

@section('content')
<div class="container-xl mt-3 mb-5">

    <div class="mb-3">
        <h1 class="mb-1" style="font-size: 1.5rem;">Order Suggestions</h1>
        <div class="text-muted">
            {{ $store->store_info }} &middot;
            week of <strong>{{ $week->format('M j') }} – {{ $weekEnd->format('M j, Y') }}</strong>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="alert alert-info py-2">
        <small>
            Suggested order is <strong>target stock &minus; what you counted</strong>, in each item's ordering unit,
            rounded up to a whole {{ 'unit' }}. Change any number before generating the order; anything you change
            is recorded as an override.
        </small>
    </div>

    @if($uncounted > 0)
        <div class="alert alert-warning py-2">
            <small>
                <strong>{{ $uncounted }}</strong> {{ Str::plural('item', $uncounted) }} {{ $uncounted === 1 ? 'was' : 'were' }}
                not counted this week, so {{ $uncounted === 1 ? 'it is' : 'they are' }} treated as zero on hand.
                <a href="{{ route('inventory.weekly-count.index', ['store_id' => $store->id, 'week' => $week->toDateString()]) }}">Back to the count</a>
            </small>
        </div>
    @endif

    @if($withoutTarget > 0)
        <div class="alert alert-warning py-2">
            <small>
                <strong>{{ $withoutTarget }}</strong> {{ Str::plural('item', $withoutTarget) }} {{ $withoutTarget === 1 ? 'has' : 'have' }}
                no stock target, so nothing is suggested for {{ $withoutTarget === 1 ? 'it' : 'them' }}.
                <a href="{{ route('admin.inventory-targets.index', $store) }}">Set targets</a>
            </small>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>{{ $needingOrder }} of {{ $rows->count() }} items</strong> need ordering
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="hideZero" checked>
                <label class="form-check-label small" for="hideZero">Hide items that need no order</label>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('inventory.weekly-count.generate-order') }}" id="orderForm">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="week" value="{{ $week->toDateString() }}">

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 170px;">Item</th>
                                <th class="text-end">Current</th>
                                <th class="text-end">Target</th>
                                <th class="text-end">Suggested</th>
                                <th style="min-width: 150px;">Vendor</th>
                                <th style="width: 160px;">Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                            @php $item = $row['item']; @endphp
                            <tr class="suggestion-row {{ $row['needs_order'] ? '' : 'no-order' }}"
                                data-needs-order="{{ $row['needs_order'] ? '1' : '0' }}">
                                <td>
                                    <strong>{{ $item->name }}</strong>
                                    @unless($row['has_target'])
                                        <span class="badge bg-warning text-dark">no target</span>
                                    @endunless
                                    @unless($row['is_counted'])
                                        <span class="badge bg-secondary">not counted</span>
                                    @endunless
                                    <div class="text-muted small">
                                        {{ $trim($item->units_per_purchase) }} {{ $item->base_unit }} per {{ $item->purchase_unit }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    {{ $trim($row['current_stock']) }} <span class="text-muted small">{{ $item->purchase_unit }}</span>
                                    <div class="text-muted small">{{ $trim($row['current_stock_base']) }} {{ $item->base_unit }}</div>
                                </td>
                                <td class="text-end">
                                    @if($row['has_target'])
                                        {{ $trim($row['target_stock']) }} <span class="text-muted small">{{ $item->purchase_unit }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong>{{ $trim($row['suggested_order']) }}</strong>
                                    <span class="text-muted small">{{ $item->purchase_unit }}</span>
                                    @if($row['suggested_order'] > $row['suggested_order_exact'])
                                        <div class="text-muted small" title="Rounded up to a whole {{ $item->purchase_unit }}">
                                            from {{ $trim($row['suggested_order_exact']) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <select name="vendors[{{ $item->id }}]" class="form-select form-select-sm">
                                        <option value="">No vendor</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}"
                                                @selected($row['preferred_vendor']?->id === $vendor->id)>{{ $vendor->vendor_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" inputmode="decimal" step="0.01" min="0"
                                               class="form-control qty-input"
                                               name="quantities[{{ $item->id }}]"
                                               value="{{ $row['suggested_order'] > 0 ? $trim($row['suggested_order']) : '' }}"
                                               data-suggested="{{ $row['suggested_order'] }}"
                                               aria-label="Order quantity for {{ $item->name }}">
                                        <span class="input-group-text">{{ $item->purchase_unit }}</span>
                                    </div>
                                    <div class="small text-warning d-none" data-override-flag>overridden</div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No active inventory items for this store.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($rows->isNotEmpty())
        <div class="sticky-save mt-3">
            <div class="d-flex gap-2 align-items-center">
                <a href="{{ route('inventory.weekly-count.index', ['store_id' => $store->id, 'week' => $week->toDateString()]) }}"
                   class="btn btn-outline-secondary">Back to count</a>
                <button type="submit" class="btn btn-success flex-fill">Generate Order</button>
            </div>
            <div class="text-center text-muted small mt-1">One draft order is created per vendor.</div>
        </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
// Flag any quantity the manager changes away from the suggestion, so an
// override is visible before the order is generated, not only afterwards.
document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('input', function () {
        const suggested = parseFloat(this.dataset.suggested);
        const entered = this.value === '' ? 0 : parseFloat(this.value);
        const row = this.closest('.suggestion-row');
        const flag = row.querySelector('[data-override-flag]');
        const changed = Math.abs(entered - suggested) >= 0.0001;

        row.classList.toggle('overridden', changed);
        flag.classList.toggle('d-none', !changed);
    });
});

const hideZero = document.getElementById('hideZero');

function applyFilter() {
    document.querySelectorAll('.suggestion-row').forEach(row => {
        const needsOrder = row.dataset.needsOrder === '1';
        row.classList.toggle('d-none', hideZero.checked && !needsOrder);
    });
}

hideZero.addEventListener('change', applyFilter);
applyFilter();
</script>
@endpush
