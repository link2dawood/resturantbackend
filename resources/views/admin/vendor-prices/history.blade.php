@extends('layouts.tabler')

@section('title', 'Price History')

@section('content')
<div class="container-xl mt-4" style="max-width: 720px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Price history — {{ $item->name }}</h1>
        <a href="{{ route('admin.vendor-prices.index', ['store_id' => $item->store_id]) }}" class="btn btn-link">← Prices</a>
    </div>

    <div class="card"><div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Effective</th><th>Vendor</th><th class="text-end">Price</th><th>Unit</th></tr></thead>
                <tbody>
                    @forelse($prices as $p)
                        <tr>
                            <td>{{ $p->effective_date?->format('M j, Y') }}</td>
                            <td>{{ $p->vendor->vendor_name ?? '—' }}</td>
                            <td class="text-end">${{ number_format((float) $p->price, 2) }}</td>
                            <td>per {{ $p->price_unit }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No prices recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div></div>
</div>
@endsection
