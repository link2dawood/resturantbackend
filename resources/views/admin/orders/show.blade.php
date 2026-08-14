@extends('layouts.tabler')

@section('title', 'Order')

@php $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.'); @endphp

@section('content')
<div class="container-xl mt-4" style="max-width: 760px;">
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <a href="{{ route('admin.orders.index', ['store_id' => $order->store_id, 'week_start_date' => $order->week_start_date->toDateString()]) }}" class="btn btn-link">← Orders</a>
        <div class="d-flex gap-2">
            @if($order->status === 'draft')
                <form action="{{ route('admin.orders.placed', $order) }}" method="POST">@csrf @method('PATCH')<button class="btn btn-outline-info">Mark placed</button></form>
            @elseif($order->status === 'placed')
                <form action="{{ route('admin.orders.received', $order) }}" method="POST">@csrf @method('PATCH')<button class="btn btn-outline-success">Mark received</button></form>
            @endif
            <button class="btn btn-primary" onclick="window.print()">Print / copy</button>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success d-print-none">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="mb-0">{{ $order->vendor->vendor_name ?? 'Vendor' }}</h1>
                    <div class="text-muted">{{ $order->store->store_info ?? 'Store' }}</div>
                </div>
                <div class="text-end">
                    <div><strong>Order {{ $order->order_sequence }}</strong></div>
                    <div class="text-muted">Week of {{ $order->week_start_date->format('M j, Y') }}</div>
                    <div><span class="badge bg-secondary text-uppercase">{{ $order->status }}</span></div>
                </div>
            </div>

            <table class="table table-sm">
                <thead><tr><th>Item</th><th class="text-end">Quantity</th><th>Unit</th></tr></thead>
                <tbody>
                    @foreach($order->items as $line)
                        <tr>
                            <td>{{ $line->inventoryItem->name ?? '—' }}</td>
                            <td class="text-end">{{ $fmt($line->quantity) }}</td>
                            <td>{{ $line->unit }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($order->placed_at || $order->received_at)
                <div class="text-muted small mt-2">
                    @if($order->placed_at)Placed {{ $order->placed_at->format('M j, Y g:ia') }}. @endif
                    @if($order->received_at)Received {{ $order->received_at->format('M j, Y g:ia') }}.@endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
