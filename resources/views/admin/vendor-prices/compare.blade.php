@extends('layouts.tabler')

@section('title', 'Price Comparison')

@php
    $money = fn ($n, $dp = 2) => '$'.number_format((float) $n, $dp);
    $sortLink = function (string $column, string $label) use ($sort, $direction) {
        $next = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $arrow = $sort === $column ? ($direction === 'asc' ? ' ↑' : ' ↓') : '';
        $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next]);
        return '<a href="'.e($url).'" class="text-decoration-none text-reset">'.e($label).$arrow.'</a>';
    };
@endphp

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Price Comparison</h1>
            <p class="text-muted mb-0">
                {{ $store->store_info }} &middot; every vendor's current price side by side. Quotes are normalized
                per base unit, so a price per case and a price per pound are judged on the same footing.
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('admin.vendor-prices.index', request()->query()) }}" class="btn btn-outline-secondary">Enter prices</a>
            <a href="{{ route('admin.vendor-prices.compare.export', request()->query()) }}" class="btn btn-outline-primary">Export CSV</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @if($unpricedCount > 0)
    <div class="alert alert-warning">
        <strong>{{ $unpricedCount }}</strong> {{ Str::plural('item', $unpricedCount) }}
        {{ $unpricedCount === 1 ? 'has' : 'have' }} no price from any vendor yet, highlighted below.
        {{ $unpricedCount === 1 ? 'It cannot' : 'They cannot' }} be compared until a price is entered.
    </div>
    @endif

    <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="direction" value="{{ $direction }}">
        @if($stores->isNotEmpty())
        <div class="col-md-3">
            <label class="form-label">Store</label>
            <select name="store_id" class="form-select" onchange="this.form.submit()">
                @foreach($stores as $s)
                    <option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>
                @endforeach
            </select>
        </div>
        @endif
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

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 200px;">{!! $sortLink('name', 'Item') !!}</th>
                            <th>{!! $sortLink('category', 'Category') !!}</th>
                            @foreach($vendors as $vendor)
                                <th class="text-center" style="min-width: 130px;">{{ $vendor->vendor_name }}</th>
                            @endforeach
                            <th class="text-center">{!! $sortLink('cheapest', 'Cheapest') !!}</th>
                            <th class="text-center">{!! $sortLink('priced', 'Quotes') !!}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        @php $item = $row['item']; @endphp
                        <tr class="{{ $row['has_no_price'] ? 'table-warning' : '' }}">
                            <td>
                                {{ $item->name }}
                                @if($row['has_no_price'])<span class="badge bg-warning text-dark">no price</span>@endif
                                <span class="text-muted small d-block">
                                    per {{ $item->purchase_unit }}
                                    &middot; <a href="{{ route('admin.vendor-prices.history', $item) }}">history</a>
                                </span>
                            </td>
                            <td class="text-muted">{{ $item->inventoryCategory?->name ?? $item->category ?? '—' }}</td>

                            @foreach($vendors as $vendor)
                            @php $cell = $row['cells'][$vendor->id]; @endphp
                            <td class="text-end {{ $cell['is_cheapest'] ? 'table-success' : '' }}">
                                @if($cell['price'] !== null)
                                    <div>
                                        <strong>{{ $money($cell['price']) }}</strong>
                                        @if($cell['is_cheapest'])<span class="badge bg-green-lt">cheapest</span>@endif
                                    </div>
                                    @if($cell['per_base'] !== null)
                                        <div class="small text-muted">{{ $money($cell['per_base'], 4) }}/{{ $item->base_unit }}</div>
                                    @endif
                                    @if($cell['change_pct'] !== null)
                                        @if($cell['change_pct'] > 0)
                                            <div class="small text-danger">▲ {{ number_format($cell['change_pct'], 1) }}%</div>
                                        @elseif($cell['change_pct'] < 0)
                                            <div class="small text-success">▼ {{ number_format(abs($cell['change_pct']), 1) }}%</div>
                                        @else
                                            <div class="small text-muted">no change</div>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @endforeach

                            <td class="text-center">
                                @php $cheapestVendor = $vendors->firstWhere('id', $row['cheapest_vendor_id']); @endphp
                                @if($cheapestVendor)
                                    <div><strong>{{ $cheapestVendor->vendor_name }}</strong></div>
                                    @if($row['cheapest_per_base'] !== null)
                                        <div class="small text-muted">{{ $money($row['cheapest_per_base'], 4) }}/{{ $item->base_unit }}</div>
                                    @endif
                                    @if(! $row['is_preferred_cheapest'])
                                        <span class="badge bg-yellow-lt" title="The preferred vendor for this item is not the cheapest">not preferred</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center text-muted">{{ $row['priced_count'] }} / {{ $vendors->count() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ $vendors->count() + 4 }}" class="text-center text-muted py-4">No active items for this store.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(! empty($rows))
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">
                A price is only compared when its unit converts to the item's base unit,
                so a quote in a unit the item does not use is left out of the cheapest calculation.
            </small>
            <form method="POST" action="{{ route('admin.vendor-prices.apply-cheapest') }}"
                  onsubmit="return confirm('Set each item\'s preferred vendor to its cheapest?');">
                @csrf
                <input type="hidden" name="store_id" value="{{ $store->id }}">
                <button class="btn btn-outline-success">Apply cheapest → preferred</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
