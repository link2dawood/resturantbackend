@extends('layouts.tabler')

@section('title', 'Vendor Prices')

@php $money = fn ($n) => '$'.number_format((float) $n, 2); @endphp

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Vendor Prices</h1>
            <p class="text-muted mb-0">Enter per-vendor prices; the cheapest vendor per item is flagged (compared per base unit).</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('admin.vendor-prices.compare', ['store_id' => $store->id, 'inventory_category_id' => request('inventory_category_id')]) }}"
               class="btn btn-outline-secondary">Compare prices</a>
            @if($stores->isNotEmpty())
                <form method="GET"><select name="store_id" class="form-select" onchange="this.form.submit()">
                    @foreach($stores as $s)<option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>@endforeach
                </select></form>
            @endif
            <form method="POST" action="{{ route('admin.vendor-prices.apply-cheapest') }}" onsubmit="return confirm('Set each item\'s preferred vendor to its cheapest?');">
                @csrf<input type="hidden" name="store_id" value="{{ $store->id }}">
                <button class="btn btn-outline-success">Apply cheapest → preferred</button>
            </form>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <div class="col-md-3">
            <label class="form-label">Category</label>
            <select name="inventory_category_id" class="form-select" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('inventory_category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.vendor-prices.bulk') }}">
        @csrf<input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="inventory_category_id" value="{{ request('inventory_category_id') }}">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th style="min-width:180px;">Item</th>
                            @foreach($vendors as $v)<th class="text-center">{{ $v->vendor_name }}</th>@endforeach
                        </tr></thead>
                        <tbody>
                            @forelse($items as $item)
                                @php $unpriced = ($current->get($item->id)?->count() ?? 0) === 0; @endphp
                                <tr class="{{ $unpriced ? 'table-warning' : '' }}">
                                    <td>
                                        {{ $item->name }}
                                        @if($unpriced)<span class="badge bg-warning text-dark">no price</span>@endif
                                        <span class="text-muted small d-block">per {{ $item->purchase_unit }}
                                            · <a href="{{ route('admin.vendor-prices.history', $item) }}">history</a></span>
                                    </td>
                                    @foreach($vendors as $v)
                                        @php
                                            $price = $current->get($item->id)?->get($v->id);
                                            $isCheapest = ($cheapest[$item->id] ?? null) === $v->id;
                                            $perBase = $price ? $service->perBase($price, $item) : null;
                                        @endphp
                                        <td class="{{ $isCheapest ? 'table-success' : '' }}" style="width:130px;">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" min="0" class="form-control text-end"
                                                       name="prices[{{ $item->id }}][{{ $v->id }}]"
                                                       value="{{ $price ? number_format((float)$price->price, 2, '.', '') : '' }}">
                                            </div>
                                            @if($perBase !== null)
                                                <div class="small text-muted text-end mt-1">
                                                    {{ $money($perBase) }}/{{ $item->base_unit }} @if($isCheapest)<span class="badge bg-green-lt">cheapest</span>@endif
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ $vendors->count() + 1 }}" class="text-center text-muted py-4">No active items for this store.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($items->isNotEmpty() && $vendors->isNotEmpty())
                <div class="card-footer d-flex justify-content-end"><button type="submit" class="btn btn-primary">Save prices</button></div>
            @endif
        </div>
    </form>
</div>
@endsection
