@extends('layouts.tabler')

@section('title', 'Order')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.');
    $money = fn ($n) => '$'.number_format((float) $n, 2);
    // The owner decides what to buy and can still edit a draft; the manager
    // places the order and checks the delivery in.
    $canOrder = Auth::user()->isAdmin() || Auth::user()->isOwner();
    $editable = $order->isEditable() && $canOrder;
@endphp

@section('content')
<div class="container-xl mt-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <a href="{{ route('admin.orders.index', ['store_id' => $order->store_id, 'week_start_date' => $order->week_start_date->toDateString()]) }}" class="btn btn-link">&larr; Orders</a>
        <div class="d-flex gap-2">
            @if($canOrder && $order->canTransitionTo('placed'))
                <form action="{{ route('admin.orders.placed', $order) }}" method="POST"
                      onsubmit="return confirm('Mark this order placed? The lines will be locked.');">
                    @csrf @method('PATCH')<button class="btn btn-outline-info">Mark placed</button>
                </form>
            @endif
            @if($order->canTransitionTo('received'))
                <a href="{{ route('admin.orders.receive', $order) }}" class="btn btn-outline-success">Check in delivery</a>
            @endif
            @if($canOrder && $order->canTransitionTo('cancelled'))
                <form action="{{ route('admin.orders.cancel', $order) }}" method="POST"
                      onsubmit="return confirm('Cancel this order?');">
                    @csrf @method('PATCH')<button class="btn btn-outline-danger">Cancel</button>
                </form>
            @endif
            @if($canOrder && (int) $order->order_sequence === 1 && $order->status !== 'cancelled')
                <form action="{{ route('admin.orders.duplicate', $order) }}" method="POST">
                    @csrf<button class="btn btn-outline-secondary">Duplicate for Order 2</button>
                </form>
            @endif
            <a href="{{ route('admin.orders.report', $order) }}" class="btn btn-primary">View Vendor Report</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success d-print-none">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger d-print-none">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger d-print-none">{{ $errors->first() }}</div>@endif

    @if($order->status === 'received' && $order->has_discrepancies)
        <div class="alert alert-danger d-print-none">
            <strong>This delivery did not match the order.</strong>
            <ul class="mb-0 mt-2 ps-3">
                @foreach($order->discrepancies as $line)
                    <li>
                        {{ $line->inventoryItem->name ?? 'Item' }}:
                        ordered {{ $fmt($line->quantity) }}, arrived {{ $fmt($line->quantity_received) }}
                        ({{ $line->received_delta > 0 ? 'over by ' : 'short by ' }}{{ $fmt(abs($line->received_delta)) }} {{ $line->unit }})
                        @if(filled($line->received_notes)) &middot; {{ $line->received_notes }} @endif
                    </li>
                @endforeach
            </ul>
            @if(abs($order->discrepancy_value) >= 0.01)
                <div class="mt-2">
                    Worth {{ $order->discrepancy_value > 0 ? '+' : '' }}{{ $money($order->discrepancy_value) }}
                    against what was ordered.
                </div>
            @endif
        </div>
    @endif

    @unless($editable)
        <div class="alert alert-secondary d-print-none">
            <small>
                @if(! $canOrder)
                    The owner sets the quantities on this order. Place it with the vendor, then use
                    <strong>Check in delivery</strong> to record what actually arrives.
                @else
                    This order is <strong>{{ $order->status }}</strong>, so its lines are locked.
                    @if($order->status === 'placed') It can still be marked received or cancelled. @endif
                @endif
            </small>
        </div>
    @endunless

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
                    @php $badge = ['draft'=>'bg-secondary','placed'=>'bg-blue','received'=>'bg-green','cancelled'=>'bg-red'][$order->status] ?? 'bg-secondary'; @endphp
                    <div><span class="badge {{ $badge }} text-uppercase">{{ $order->status }}</span></div>
                </div>
            </div>

            {{-- A read-only viewer gets no form at all, so nothing here can be posted. --}}
            @if($editable)
                <form method="POST" action="{{ route('admin.orders.items.update', $order) }}">
                @csrf @method('PUT')
            @endif

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end" style="width: 130px;">Quantity</th>
                                <th style="width: 70px;">Unit</th>
                                <th class="text-end" style="width: 120px;">Unit price</th>
                                <th class="text-end" style="width: 100px;">Line total</th>
                                @if($order->status === 'received')<th class="text-end" style="width: 110px;">Arrived</th>@endif
                                @if($editable)<th class="d-print-none" style="width: 170px;">Move to vendor</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $line)
                            <tr>
                                <td>
                                    {{ $line->inventoryItem->name ?? '—' }}
                                    @if($line->is_manual_override)
                                        <span class="badge bg-yellow-lt d-print-none"
                                              title="Suggested {{ $fmt($line->suggested_quantity) }}">overridden</span>
                                    @endif
                                    @if($editable)
                                        <input type="text" class="form-control form-control-sm mt-1 d-print-none"
                                               name="line_notes[{{ $line->id }}]" maxlength="255"
                                               value="{{ $line->notes }}" placeholder="Line note">
                                    @elseif($line->notes)
                                        <div class="small text-muted">{{ $line->notes }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($editable)
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end"
                                               name="quantity[{{ $line->id }}]" value="{{ $fmt($line->quantity) }}"
                                               aria-label="Quantity for {{ $line->inventoryItem->name ?? 'item' }}">
                                    @else
                                        {{ $fmt($line->quantity) }}
                                    @endif
                                </td>
                                <td>{{ $line->unit }}</td>
                                <td class="text-end">
                                    @if($editable)
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control text-end"
                                                   name="unit_price[{{ $line->id }}]"
                                                   value="{{ $line->unit_price !== null ? number_format((float) $line->unit_price, 2, '.', '') : '' }}">
                                        </div>
                                    @else
                                        {{ $line->unit_price !== null ? $money($line->unit_price) : '—' }}
                                    @endif
                                </td>
                                <td class="text-end">{{ $line->line_total !== null ? $money($line->line_total) : '—' }}</td>
                                @if($order->status === 'received')
                                <td class="text-end">
                                    @if($line->is_checked)
                                        {{ $fmt($line->quantity_received) }}
                                        @if($line->has_discrepancy)
                                            <div class="small {{ $line->received_delta > 0 ? 'text-danger fw-bold' : 'text-warning fw-bold' }}">
                                                {{ $line->received_delta > 0 ? '+' : '' }}{{ $fmt($line->received_delta) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">not checked</span>
                                    @endif
                                    @if(filled($line->received_notes))
                                        <div class="text-muted small">{{ $line->received_notes }}</div>
                                    @endif
                                </td>
                                @endif
                                @if($editable)
                                <td class="d-print-none">
                                    <select name="move_to_vendor[{{ $line->id }}]" class="form-select form-select-sm">
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}" @selected($vendor->id === $order->vendor_id)>{{ $vendor->vendor_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr><td colspan="{{ $editable ? 6 : 5 }}" class="text-center text-muted py-3">No lines on this order.</td></tr>
                            @endforelse
                        </tbody>
                        @if($order->total > 0)
                        <tfoot>
                            <tr>
                                <th colspan="{{ $editable ? 4 : 3 }}" class="text-end">Order total</th>
                                <th class="text-end">{{ $money($order->total) }}</th>
                                @if($editable)<th class="d-print-none"></th>@endif
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <div class="mb-3">
                    <label class="form-label">Order notes</label>
                    @if($editable)
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Anything the vendor needs to know">{{ $order->notes }}</textarea>
                    @else
                        <div class="text-muted">{{ $order->notes ?: '—' }}</div>
                    @endif
                </div>

                @if($editable)
                <div class="d-flex justify-content-between align-items-center d-print-none">
                    <small class="text-muted">Set a quantity to 0 to take a line off the order.</small>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
                </form>
                @endif

            @if($order->placed_at || $order->received_at)
                <div class="text-muted small mt-3">
                    @if($order->placed_at)Placed {{ $order->placed_at->format('M j, Y g:ia') }}. @endif
                    @if($order->received_at)Received {{ $order->received_at->format('M j, Y g:ia') }}.@endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
