@extends('layouts.tabler')

@section('title', 'Orders')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    // The owner decides what to buy; the manager places it and checks it in.
    $canOrder = Auth::user()->isAdmin() || Auth::user()->isOwner();
@endphp

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="mb-0">Orders @unless($allWeeks) &middot; week of {{ $week->format('M j, Y') }} @else &middot; all weeks @endunless</h1>
        @if($canOrder)
            <div class="d-flex gap-2">
                <a href="{{ route('admin.orders.history', ['store_id' => $store->id]) }}" class="btn btn-outline-secondary">Order history</a>
                <a href="{{ route('admin.orders.build', ['store_id' => $store->id, 'week_start_date' => $week->toDateString()]) }}" class="btn btn-primary">Build order</a>
            </div>
        @endif
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            @if($stores->isNotEmpty())
                <div class="col-sm-3"><label class="form-label">Store</label>
                    <select name="store_id" class="form-select">@foreach($stores as $s)<option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>@endforeach</select></div>
            @endif
            <div class="col-sm-3"><label class="form-label">Week</label>
                <input type="date" name="week_start_date" class="form-control" value="{{ $allWeeks ? '' : $week->toDateString() }}">
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="all_weeks" value="1" id="allWeeks" @checked($allWeeks)>
                    <label class="form-check-label small text-muted" for="allWeeks">All weeks</label>
                </div>
            </div>
            <div class="col-sm-3"><label class="form-label">Vendor</label>
                <select name="vendor_id" class="form-select"><option value="">All vendors</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected($v->id === $selectedVendorId)>{{ $v->vendor_name }}</option>@endforeach</select></div>
            <div class="col-sm-2"><label class="form-label">Status</label>
                <select name="status" class="form-select"><option value="">All</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="col-sm-1"><button class="btn btn-outline-primary w-100">Filter</button></div>
        </form>
    </div></div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        @if($allWeeks)<th>Week</th>@endif
                        <th>Order</th><th>Vendor</th><th class="text-center">Items</th><th class="text-end">Total</th><th>Status</th><th class="text-end">Actions</th>
                    </tr></thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr data-order-id="{{ $order->id }}">
                                @if($allWeeks)<td>{{ $order->week_start_date?->format('M j, Y') }}</td>@endif
                                <td>
                                    Order {{ $order->order_sequence }}
                                    @if($order->has_overrides)<span class="badge bg-yellow-lt" title="At least one line was changed from the suggestion">edited</span>@endif
                                </td>
                                <td>
                                    {{ $order->vendor->vendor_name ?? '—' }}
                                    @if($order->vendor?->order_method)
                                        <div class="text-muted small">{{ $order->vendor->order_method_label }}</div>
                                    @endif
                                </td>
                                <td class="text-center">{{ $order->items->count() }}</td>
                                <td class="text-end">{{ $order->total > 0 ? '$'.number_format($order->total, 2) : '—' }}</td>
                                <td>
                                    @php $badge = ['draft'=>'bg-secondary','placed'=>'bg-blue','received'=>'bg-green','cancelled'=>'bg-red'][$order->status] ?? 'bg-secondary'; @endphp
                                    <span class="badge {{ $badge }}">{{ $order->status }}</span>
                                    @if($order->status === 'received' && $order->has_discrepancies)
                                        <span class="badge bg-red" title="What arrived did not match the order">mismatch</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('admin.orders.report', $order) }}" class="btn btn-sm btn-outline-secondary" title="Printable vendor report">Report</a>
                                    @if($order->status === 'draft' && $canOrder)
                                        <form action="{{ route('admin.orders.placed', $order) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-info">Mark placed</button></form>
                                    @elseif($order->status === 'placed')
                                        <a href="{{ route('admin.orders.receive', $order) }}" class="btn btn-sm btn-outline-success">Check in</a>
                                    @endif
                                    @if($canOrder && (int) $order->order_sequence === 1 && $order->status !== 'cancelled')
                                        <form action="{{ route('admin.orders.duplicate', $order) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary" title="Copy these lines into a second order for the same week">Duplicate for Order 2</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $allWeeks ? 7 : 6 }}" class="text-center text-muted py-4">
                                No orders for this week.
                                @if($canOrder)
                                    <a href="{{ route('admin.orders.build', ['store_id' => $store->id, 'week_start_date' => $week->toDateString()]) }}">Build one</a>.
                                @else
                                    The owner raises the orders; they will appear here for you to place and check in.
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($history->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header"><h3 class="card-title mb-0">History · {{ $history->first()->vendor->vendor_name ?? 'Vendor' }}</h3></div>
            <div class="card-body p-0"><div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Week</th><th>Order</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach($history as $h)
                            <tr>
                                <td>{{ $h->week_start_date?->format('M j, Y') }}</td>
                                <td>Order {{ $h->order_sequence }}</td>
                                <td>{{ $h->status }}</td>
                                <td class="text-end"><a href="{{ route('admin.orders.show', $h) }}" class="small">view</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div></div>
        </div>
    @endif
</div>
@endsection
