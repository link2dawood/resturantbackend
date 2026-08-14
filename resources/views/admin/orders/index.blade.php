@extends('layouts.tabler')

@section('title', 'Orders')

@php $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.'); @endphp

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="mb-0">Orders · week of {{ $week->format('M j, Y') }}</h1>
        <a href="{{ route('admin.orders.build', ['store_id' => $store->id, 'week_start_date' => $week->toDateString()]) }}" class="btn btn-primary">Build order</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            @if($stores->isNotEmpty())
                <div class="col-sm-3"><label class="form-label">Store</label>
                    <select name="store_id" class="form-select">@foreach($stores as $s)<option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>@endforeach</select></div>
            @endif
            <div class="col-sm-3"><label class="form-label">Week</label><input type="date" name="week_start_date" class="form-control" value="{{ $week->toDateString() }}"></div>
            <div class="col-sm-3"><label class="form-label">Vendor</label>
                <select name="vendor_id" class="form-select"><option value="">All vendors</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected($v->id === $selectedVendorId)>{{ $v->vendor_name }}</option>@endforeach</select></div>
            <div class="col-sm-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
        </form>
    </div></div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Order</th><th>Vendor</th><th class="text-center">Items</th><th>Status</th><th class="text-end">Actions</th>
                    </tr></thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>Order {{ $order->order_sequence }}</td>
                                <td>{{ $order->vendor->vendor_name ?? '—' }}</td>
                                <td class="text-center">{{ $order->items->count() }}</td>
                                <td>
                                    @php $badge = ['draft'=>'bg-secondary','placed'=>'bg-blue','received'=>'bg-green'][$order->status] ?? 'bg-secondary'; @endphp
                                    <span class="badge {{ $badge }}">{{ $order->status }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    @if($order->status === 'draft')
                                        <form action="{{ route('admin.orders.placed', $order) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-info">Mark placed</button></form>
                                    @elseif($order->status === 'placed')
                                        <form action="{{ route('admin.orders.received', $order) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Mark received</button></form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No orders for this week. <a href="{{ route('admin.orders.build', ['store_id' => $store->id, 'week_start_date' => $week->toDateString()]) }}">Build one</a>.</td></tr>
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
