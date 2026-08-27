@extends('layouts.tabler')

@section('title', 'Stock-Up Worksheet')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
@endphp

@section('content')
<div class="container-xl mt-4">
    <div class="mb-3">
        <h1 class="mb-0">Stock-Up Worksheet</h1>
        <p class="text-muted mb-0">Enter projected weekly sales and get suggested order quantities from historical usage.</p>
    </div>

    <div class="card mb-4"><div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            @if($stores->isNotEmpty())
                <div class="col-sm-3">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-select">
                        @foreach($stores as $s)<option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div class="col-sm-3">
                <label class="form-label">Week</label>
                <input type="date" name="week_start_date" class="form-control" value="{{ $week->toDateString() }}">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Projected weekly sales ($)</label>
                <input type="number" step="0.01" min="0" name="projected_dollars" class="form-control" value="{{ $projectedDollars ?: '' }}" placeholder="e.g. 20000">
            </div>
            <div class="col-sm-2">
                <label class="form-label">History (weeks)</label>
                <input type="number" min="1" max="12" name="history_weeks" class="form-control" value="{{ $historyWeeks }}">
            </div>
            <div class="col-sm-1">
                <button class="btn btn-primary w-100">Go</button>
            </div>
        </form>
    </div></div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Suggested orders · week of {{ $week->format('M j, Y') }}</h3>
            <span class="text-muted small">Averaging the prior {{ $historyWeeks }} week(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Item</th><th>Unit</th>
                        <th class="text-end">Avg weekly use</th>
                        <th class="text-end">Projected use</th>
                        <th class="text-end">On hand</th>
                        <th class="text-end">Required</th>
                        <th class="text-end" style="width:140px;">Suggested order</th>
                    </tr></thead>
                    <tbody>
                        @forelse($suggestions as $s)
                            <tr class="{{ $s['reorder_flag'] ? 'table-warning' : '' }}">
                                <td>{{ $s['item']->name }}
                                    @if($s['reorder_flag'])<span class="badge bg-yellow-lt ms-1" title="At or below reorder threshold">reorder</span>@endif
                                    @if($s['basis'] === 'average')<span class="badge bg-secondary-lt ms-1" title="No sales-dollar history — using average usage">avg</span>@endif
                                </td>
                                <td class="text-muted">{{ $s['item']->base_unit }}</td>
                                <td class="text-end">{{ $fmt($s['avg_weekly_usage']) }}</td>
                                <td class="text-end">{{ $fmt($s['projected_usage']) }}</td>
                                <td class="text-end">{{ $fmt($s['current_on_hand']) }}</td>
                                <td class="text-end">{{ $fmt($s['required']) }}</td>
                                <td class="text-end">
                                    <input type="number" step="0.0001" min="0" class="form-control form-control-sm text-end"
                                           name="suggested_qty[{{ $s['item']->id }}]" value="{{ $fmt($s['suggested_order']) }}">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No active inventory items for this store.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            Suggested = max(0, required − on hand), where required = projected usage × (1 + safety buffer), floored at the item's min stock level.
            Turning these into vendor orders comes in the ordering module.
        </div>
    </div>
</div>
@endsection
