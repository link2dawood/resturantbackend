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

    {{-- Task 13 widget grid. Two per row on tablet, one per row on a phone. --}}
    <div class="row g-3 mb-4">

        {{-- 1. This week's inventory status --}}
        <div class="col-12 col-md-6 col-xl-3">
            <x-widget-card
                title="This week's count"
                :href="route('inventory.weekly-count.index', ['store_id' => $store->id])"
                link-text="Count"
                :value="$countStatus['started'] ? $countStatus['counted'].' of '.$countStatus['total'] : 'Not started'"
                :subtitle="$countStatus['started']
                    ? ($countStatus['submitted'] ? 'submitted and locked' : 'items counted')
                    : 'click to begin this week'"
                :tone="$countStatus['submitted'] ? 'success' : ($countStatus['started'] ? 'default' : 'warning')">
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar {{ $countStatus['percent'] === 100 ? 'bg-success' : '' }}"
                         role="progressbar" style="width: {{ $countStatus['percent'] }}%"
                         aria-valuenow="{{ $countStatus['counted'] }}" aria-valuemin="0"
                         aria-valuemax="{{ $countStatus['total'] }}"></div>
                </div>
            </x-widget-card>
        </div>

        {{-- 2. Pending orders --}}
        <div class="col-12 col-md-6 col-xl-3">
            <x-widget-card
                title="Pending orders"
                :href="route('admin.orders.index', ['store_id' => $store->id])"
                link-text="Orders"
                :value="$pendingOrders->count()"
                :subtitle="$pendingOrders->count() === 0
                    ? 'nothing outstanding'
                    : $pendingOrders->where('status', 'draft')->count().' draft, '.$pendingOrders->where('status', 'placed')->count().' awaiting delivery'"
                :tone="$pendingOrders->count() > 0 ? 'warning' : 'success'">
                @foreach($pendingOrders->take(3) as $pending)
                    <div class="small">
                        <a href="{{ route('admin.orders.show', $pending) }}" class="text-decoration-none">
                            {{ $pending->vendor->vendor_name ?? 'No vendor' }}
                        </a>
                        <span class="badge bg-secondary-lt">{{ $pending->status }}</span>
                    </div>
                @endforeach
                @if($pendingOrders->count() > 3)
                    <div class="small text-muted">and {{ $pendingOrders->count() - 3 }} more</div>
                @endif
            </x-widget-card>
        </div>

        {{-- 3. Low stock alerts --}}
        <div class="col-12 col-md-6 col-xl-3">
            <x-widget-card
                title="Low stock"
                :href="(Auth::user()->isAdmin() || Auth::user()->isOwner())
                    ? route('inventory.weekly-count.suggestions', ['store_id' => $store->id])
                    : route('inventory.weekly-count.index', ['store_id' => $store->id])"
                :link-text="(Auth::user()->isAdmin() || Auth::user()->isOwner()) ? 'Suggestions' : 'Count'"
                :value="$lowStock->count()"
                :subtitle="$lowStock->count() === 0
                    ? 'everything counted is above its reorder point'
                    : $lowStock->where('is_out', true)->count().' out of stock'"
                :tone="$lowStock->where('is_out', true)->count() > 0 ? 'danger' : ($lowStock->count() > 0 ? 'warning' : 'success')">
                @foreach($lowStock->take(3) as $low)
                    <div class="small">
                        <strong>{{ $low['item']->name }}</strong>
                        <span class="text-muted">
                            {{ $fmt($low['on_hand']) }} / {{ $fmt($low['threshold']) }} {{ $low['unit'] }}
                        </span>
                    </div>
                @endforeach
                @if($lowStock->count() > 3)
                    <div class="small text-muted">and {{ $lowStock->count() - 3 }} more</div>
                @endif
            </x-widget-card>
        </div>

        {{-- 4. Variance alerts (Phase 5.9, kept) --}}
        <div class="col-12 col-md-6 col-xl-3">
            <x-widget-card
                title="Variance alerts"
                :href="route('admin.variance.index', ['store_id' => $store->id, 'week_start_date' => $priorWeek->toDateString()])"
                link-text="Report"
                :value="$alerts->count()"
                :subtitle="'week of '.$priorWeek->format('M j').' · '.$alerts->where('line.severity','red')->count().' problem, '.$alerts->where('line.severity','yellow')->count().' investigate'"
                :tone="$alerts->where('line.severity','red')->count() > 0 ? 'danger' : ($alerts->count() > 0 ? 'warning' : 'success')" />
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="card mb-4">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title mb-0" style="font-size: 1rem; font-weight: 500;">Recent activity</h3>
        </div>
        <div class="card-body">
            @forelse($recentActivity as $event)
                <div class="d-flex justify-content-between align-items-start py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                    <div>
                        <a href="{{ $event['url'] }}" class="text-decoration-none">{{ $event['description'] }}</a>
                        @if($event['actor'])
                            <div class="text-muted small">by {{ $event['actor'] }}</div>
                        @endif
                    </div>
                    <div class="text-muted small text-nowrap ms-3">{{ $event['at']->diffForHumans() }}</div>
                </div>
            @empty
                <div class="text-muted">Nothing has happened yet this week.</div>
            @endforelse
        </div>
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
