@extends('layouts.tabler')

@section('title', 'Variance Report')

@php
    $fmt = fn ($n) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    $rowClass = ['green' => 'table-success', 'yellow' => 'table-warning', 'red' => 'table-danger'];
@endphp

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Variance Report</h1>
            <p class="text-muted mb-0">{{ $store->store_info ?? 'Store' }} · week of {{ $week->format('M j, Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.variance.export.csv', ['store_id' => $store->id, 'week_start_date' => $week->toDateString()]) }}" class="btn btn-outline-secondary">Export CSV</a>
            <a href="{{ route('admin.variance.export.pdf', ['store_id' => $store->id, 'week_start_date' => $week->toDateString()]) }}" class="btn btn-outline-danger">Export PDF</a>
        </div>
    </div>

    <div class="d-flex gap-2 mb-3">
        <span class="badge bg-green">{{ $tally['green'] }} acceptable</span>
        <span class="badge bg-yellow">{{ $tally['yellow'] }} investigate</span>
        <span class="badge bg-red">{{ $tally['red'] }} problem</span>
        <span class="badge bg-secondary">{{ $tally['incomplete'] }} incomplete</span>
    </div>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            @if($stores->isNotEmpty())
                <div class="col-sm-3"><label class="form-label">Store</label>
                    <select name="store_id" class="form-select">@foreach($stores as $s)<option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>@endforeach</select></div>
            @endif
            <div class="col-sm-3"><label class="form-label">Week</label><input type="date" name="week_start_date" class="form-control" value="{{ $week->toDateString() }}"></div>
            <div class="col-sm-4"><label class="form-label">Item</label>
                <select name="item_id" class="form-select"><option value="">All items</option>@foreach($items as $i)<option value="{{ $i->id }}" @selected($i->id === $itemId)>{{ $i->name }}</option>@endforeach</select></div>
            <div class="col-sm-2"><button class="btn btn-primary w-100">Apply</button></div>
        </form>
    </div></div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>Item</th><th>Unit</th>
                    <th class="text-end">Start</th><th class="text-end">Ordered</th><th class="text-end">Available</th>
                    <th class="text-end">Theo. usage</th><th class="text-end">Theo. ending</th><th class="text-end">Actual</th>
                    <th class="text-end">Variance</th><th class="text-end">%</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse($rows as $r)
                        @php $l = $r['line']; $cls = $l->isIncomplete ? '' : ($rowClass[$l->severity] ?? ''); @endphp
                        <tr class="{{ $cls }}">
                            <td>{{ $r['item']->name }}</td>
                            <td class="text-muted">{{ $l->baseUnit }}</td>
                            <td class="text-end">{{ $fmt($l->startingStock) }}</td>
                            <td class="text-end">{{ $fmt($l->orderedQty) }}</td>
                            <td class="text-end">{{ $fmt($l->totalAvailable) }}</td>
                            <td class="text-end">{{ $fmt($l->theoreticalUsage) }}</td>
                            <td class="text-end">{{ $fmt($l->theoreticalEnding) }}</td>
                            <td class="text-end">{{ $l->isIncomplete ? '—' : $fmt($l->actualEnding) }}</td>
                            <td class="text-end fw-bold">{{ $l->isIncomplete ? '—' : $fmt($l->variance) }}</td>
                            <td class="text-end">{{ $l->isIncomplete ? '—' : number_format((float) $l->variancePct, 2).'%' }}</td>
                            <td class="text-end"><a href="{{ route('admin.variance.drill-down', ['inventoryItem' => $r['item']->id, 'week_start_date' => $week->toDateString()]) }}" class="small">drill-down</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">No active items for this store.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
    <p class="text-muted small mt-2">Variance = Theoretical Ending − Actual Ending (positive = short/loss). Incomplete rows are missing a starting or ending count.</p>
</div>
@endsection
