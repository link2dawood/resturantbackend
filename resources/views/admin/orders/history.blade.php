@extends('layouts.tabler')

@section('title', 'Order History')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    $money = fn ($n) => '$'.number_format((float) $n, 2);
    $badge = fn ($status) => ['draft'=>'bg-secondary','placed'=>'bg-blue','received'=>'bg-green','cancelled'=>'bg-red'][$status] ?? 'bg-secondary';
@endphp

@section('content')
<div class="container-xl mt-4 mb-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Order History</h1>
            <p class="text-muted mb-0">
                {{ $store->store_info }} &middot;
                weeks of {{ $from->format('M j') }} to {{ $to->format('M j, Y') }}
            </p>
        </div>
        <a href="{{ route('admin.orders.index', ['store_id' => $store->id]) }}" class="btn btn-outline-secondary">This week's orders</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-start">
                @if($stores->isNotEmpty())
                <div class="col-md-3">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-select">
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label">From week</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $from->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To week</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $to->toDateString() }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Vendors</label>
                    <select name="vendor_ids[]" class="form-select" multiple size="4">
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected(in_array($vendor->id, $selectedVendorIds, true))>{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Ctrl or Cmd to pick several. None means all.</small>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    @foreach($statuses as $status)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="statuses[]" value="{{ $status }}"
                                   id="status-{{ $status }}" @checked(in_array($status, $selectedStatuses, true))>
                            <label class="form-check-label small" for="status-{{ $status }}">{{ ucfirst($status) }}</label>
                        </div>
                    @endforeach
                </div>

                <div class="col-12">
                    <button class="btn btn-primary">Apply filters</button>
                    <a href="{{ route('admin.orders.history', ['store_id' => $store->id]) }}" class="btn btn-link">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Headline numbers --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body py-3">
                <div class="text-muted small">Orders sent</div>
                <div style="font-size: 1.5rem; font-weight: 500;">{{ $summary['order_count'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body py-3">
                <div class="text-muted small">Weeks covered</div>
                <div style="font-size: 1.5rem; font-weight: 500;">{{ $summary['week_count'] }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body py-3">
                <div class="text-muted small">Total spend</div>
                <div style="font-size: 1.5rem; font-weight: 500;">{{ $money($summary['total_spend']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body py-3">
                <div class="text-muted small">Average per week</div>
                <div style="font-size: 1.5rem; font-weight: 500;">{{ $money($summary['average_weekly_spend']) }}</div>
            </div></div>
        </div>
    </div>

    @if($summary['excluded_count'] > 0)
        <div class="alert alert-info py-2">
            <small>
                {{ $summary['excluded_count'] }} of the {{ $summary['listed_count'] }} orders below are still draft or were
                cancelled, so they are listed but left out of the totals and averages.
            </small>
        </div>
    @endif

    {{-- Trends. Owner-facing: the client sees no day-to-day value in averages,
         but they are the input to the learned-target work, so they stay. --}}
    @if($trends->isNotEmpty() && (Auth::user()->isAdmin() || Auth::user()->isOwner()))
    <div class="card mb-4">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title mb-0" style="font-size: 1rem; font-weight: 500;">What you order, per week</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Average per week</th>
                            <th class="text-end">Total ordered</th>
                            <th class="text-center">Weeks ordered</th>
                            <th>Last ordered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trends as $trend)
                        <tr>
                            <td>{{ $trend['item_name'] }}</td>
                            <td class="text-end">
                                <strong>{{ $fmt($trend['average_per_week']) }}</strong>
                                <span class="text-muted small">{{ $trend['unit'] }}/week</span>
                            </td>
                            <td class="text-end">{{ $fmt($trend['total_quantity']) }} <span class="text-muted small">{{ $trend['unit'] }}</span></td>
                            <td class="text-center">{{ $trend['weeks'] }}</td>
                            <td class="text-muted small">{{ \Illuminate\Support\Carbon::parse($trend['last_ordered'])->format('M j, Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Grouped by vendor --}}
    @forelse($byVendor as $vendorName => $vendorOrders)
    <div class="card mb-3">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title mb-0" style="font-size: 1rem; font-weight: 500;">
                {{ $vendorOrders->count() }} {{ Str::plural('order', $vendorOrders->count()) }} from {{ $vendorName }}
                <span class="text-muted" style="font-weight: 400;">
                    &middot; weeks of {{ $from->format('M j') }} to {{ $to->format('M j') }}
                </span>
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Week</th>
                            <th>Order</th>
                            <th class="text-center">Items</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vendorOrders as $order)
                        <tr data-order-id="{{ $order->id }}">
                            <td>
                                <button class="btn btn-sm btn-link p-0" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#lines-{{ $order->id }}"
                                        aria-expanded="false" aria-controls="lines-{{ $order->id }}"
                                        aria-label="Show line items for order {{ $order->id }}">&#9662;</button>
                            </td>
                            <td>{{ $order->week_start_date->format('M j, Y') }}</td>
                            <td>Order {{ $order->order_sequence }}</td>
                            <td class="text-center">{{ $order->items->count() }}</td>
                            <td class="text-end">{{ $order->total > 0 ? $money($order->total) : '—' }}</td>
                            <td><span class="badge {{ $badge($order->status) }}">{{ $order->status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
                                <form action="{{ route('admin.orders.reorder', $order) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="week" value="{{ $currentWeek->toDateString() }}">
                                    <button class="btn btn-sm btn-outline-success"
                                            title="Copy these lines into a new draft for the week of {{ $currentWeek->format('M j') }}">Reorder</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse" id="lines-{{ $order->id }}">
                            <td colspan="7" class="bg-light">
                                @if($order->items->isEmpty())
                                    <div class="text-muted small py-2">No lines on this order.</div>
                                @else
                                <table class="table table-sm mb-0" style="background: transparent;">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th class="text-end">Quantity</th>
                                            <th>Unit</th>
                                            <th class="text-end">Unit price</th>
                                            <th class="text-end">Line total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $line)
                                        <tr>
                                            <td>
                                                {{ $line->inventoryItem->name ?? 'Item' }}
                                                @if(filled($line->notes))<div class="text-muted small">{{ $line->notes }}</div>@endif
                                            </td>
                                            <td class="text-end">{{ $fmt($line->quantity) }}</td>
                                            <td>{{ $line->unit }}</td>
                                            <td class="text-end">{{ $line->unit_price !== null ? $money($line->unit_price) : '—' }}</td>
                                            <td class="text-end">{{ $line->line_total !== null ? $money($line->line_total) : '—' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            No orders in this window. Widen the date range, or clear the vendor and status filters.
        </div>
    </div>
    @endforelse
</div>
@endsection
