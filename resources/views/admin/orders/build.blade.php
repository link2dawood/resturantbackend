@extends('layouts.tabler')

@section('title', 'Build Order')

@php $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.'); @endphp

@section('content')
<div class="container-xl mt-4">
    <h1 class="mb-1">Build weekly order</h1>
    <p class="text-muted">Set a quantity and vendor per item, then generate one order per vendor.</p>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            @if($stores->isNotEmpty())
                <div class="col-sm-3"><label class="form-label">Store</label>
                    <select name="store_id" class="form-select">@foreach($stores as $s)<option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>@endforeach</select>
                </div>
            @endif
            <div class="col-sm-3"><label class="form-label">Week</label><input type="date" name="week_start_date" class="form-control" value="{{ $week->toDateString() }}"></div>
            <div class="col-sm-2"><label class="form-label">Order</label>
                <select name="order_sequence" class="form-select"><option value="1" @selected($sequence===1)>Order 1</option><option value="2" @selected($sequence===2)>Order 2</option></select>
            </div>
            <div class="col-sm-3"><label class="form-label">Projected sales ($) — prefill</label><input type="number" step="0.01" min="0" name="projected_dollars" class="form-control" value="{{ $projectedDollars ?: '' }}"></div>
            <div class="col-sm-1"><button class="btn btn-outline-primary w-100">Load</button></div>
        </form>
    </div></div>

    <form method="POST" action="{{ route('admin.orders.generate') }}">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="week_start_date" value="{{ $week->toDateString() }}">
        <input type="hidden" name="order_sequence" value="{{ $sequence }}">

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Items · Order {{ $sequence }} · week of {{ $week->format('M j, Y') }}</h3></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>Item</th><th>Vendor</th>
                            <th class="text-end">Suggested</th>
                            <th style="width:120px;">Order qty</th><th style="width:120px;">Unit</th>
                        </tr></thead>
                        <tbody>
                            @forelse($items as $item)
                                @php $sg = $suggested->get($item->id); @endphp
                                <tr>
                                    <td>{{ $item->name }}<span class="text-muted small d-block">{{ $item->category }}</span>
                                        <input type="hidden" name="item_id[]" value="{{ $item->id }}"></td>
                                    <td>
                                        <select name="vendor_id[]" class="form-select form-select-sm">
                                            <option value="">— none —</option>
                                            @foreach($vendors as $v)<option value="{{ $v->id }}" @selected($v->id === $item->preferred_vendor_id)>{{ $v->vendor_name }}</option>@endforeach
                                        </select>
                                    </td>
                                    <td class="text-end text-muted">{{ $sg ? $fmt($sg['suggested_order']) : '—' }}</td>
                                    <td><input type="number" step="0.0001" min="0" name="quantity[]" class="form-control form-control-sm text-end" value="{{ $sg ? $fmt($sg['suggested_order']) : '' }}"></td>
                                    <td>
                                        <select name="unit[]" class="form-select form-select-sm">
                                            <option value="{{ $item->purchase_unit }}">{{ $item->purchase_unit }}</option>
                                            @if($item->base_unit !== $item->purchase_unit)<option value="{{ $item->base_unit }}">{{ $item->base_unit }}</option>@endif
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No active items for this store.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Generate orders</button>
            </div>
        </div>
    </form>
</div>
@endsection
