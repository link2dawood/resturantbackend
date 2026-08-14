@extends('layouts.tabler')

@section('title', 'Inventory Dashboard')

@php
    $fmt = fn ($n) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    $pct = $totalItems ? round($submittedItems / $totalItems * 100) : 0;
@endphp

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Inventory Dashboard</h1>
            <p class="text-muted mb-0">{{ $store->store_info ?? 'Store' }} · week of {{ $week->format('M j, Y') }}</p>
        </div>
        @if($stores->isNotEmpty())
            <form method="GET"><select name="store_id" class="form-select" onchange="this.form.submit()">
                @foreach($stores as $s)<option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>@endforeach
            </select></form>
        @endif
    </div>

    <div class="row g-3 mb-4">
        {{-- Count progress --}}
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <div class="text-muted">This week's count</div>
            <div class="fs-1 fw-bold">{{ $submittedItems }}<span class="fs-4 text-muted">/{{ $totalItems }}</span></div>
            <div class="progress mt-2" style="height:8px;"><div class="progress-bar {{ $pct === 100 ? 'bg-success' : '' }}" style="width: {{ $pct }}%"></div></div>
            <a href="{{ route('inventory.entry.index') }}" class="small">Go to inventory entry →</a>
        </div></div></div>

        {{-- Pending orders --}}
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <div class="text-muted">Pending orders</div>
            <div class="fs-1 fw-bold">{{ $pendingOrders->count() }}</div>
            <div class="small">
                @forelse($pendingOrders as $o)
                    <div>{{ $o->vendor->vendor_name ?? '—' }} · Order {{ $o->order_sequence }} <span class="badge bg-secondary-lt">{{ $o->status }}</span></div>
                @empty
                    <span class="text-muted">None outstanding.</span>
                @endforelse
            </div>
            <a href="{{ route('admin.orders.index', ['store_id' => $store->id]) }}" class="small">Orders →</a>
        </div></div></div>

        {{-- Variance alerts --}}
        <div class="col-md-4"><div class="card h-100"><div class="card-body">
            <div class="text-muted">Variance alerts <span class="text-muted small">(week of {{ $priorWeek->format('M j') }})</span></div>
            <div class="fs-1 fw-bold {{ $alerts->where('line.severity','red')->count() ? 'text-danger' : '' }}">{{ $alerts->count() }}</div>
            <div class="small text-muted">{{ $alerts->where('line.severity','red')->count() }} problem · {{ $alerts->where('line.severity','yellow')->count() }} investigate</div>
            <a href="{{ route('admin.variance.index', ['store_id' => $store->id, 'week_start_date' => $priorWeek->toDateString()]) }}" class="small">Variance report →</a>
        </div></div></div>
    </div>

    @if($alerts->isNotEmpty())
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Items to investigate</h3></div>
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Item</th><th class="text-end">Variance</th><th class="text-end">%</th><th>Severity</th><th></th></tr></thead>
                    <tbody>
                        @foreach($alerts as $r)
                            @php $l = $r['line']; @endphp
                            <tr class="{{ $l->severity === 'red' ? 'table-danger' : 'table-warning' }}">
                                <td>{{ $r['item']->name }}</td>
                                <td class="text-end fw-bold">{{ $fmt($l->variance) }} {{ $l->baseUnit }}</td>
                                <td class="text-end">{{ number_format((float) $l->variancePct, 2) }}%</td>
                                <td class="text-capitalize">{{ $l->severity }}</td>
                                <td class="text-end"><a href="{{ route('admin.variance.drill-down', ['inventoryItem' => $r['item']->id, 'week_start_date' => $priorWeek->toDateString()]) }}" class="small">drill-down</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div></div>
        </div>
    @endif
</div>
@endsection
