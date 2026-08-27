@extends('layouts.tabler')

@section('title', 'Variance Drill-down')

@php $fmt = fn ($n) => $n === null ? '—' : rtrim(rtrim(number_format((float) $n, 4, '.', ''), '0'), '.'); @endphp

@section('content')
<div class="container-xl mt-4" style="max-width: 820px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">{{ $item->name }} <span class="text-muted fs-4">· week of {{ $week->format('M j, Y') }}</span></h1>
        <a href="{{ route('admin.variance.index', ['store_id' => $item->store_id, 'week_start_date' => $week->toDateString()]) }}" class="btn btn-link">← Report</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach([['Starting', $line->startingStock], ['Ordered', $line->orderedQty], ['Available', $line->totalAvailable], ['Theo. usage', $line->theoreticalUsage], ['Theo. ending', $line->theoreticalEnding], ['Actual ending', $line->isIncomplete ? null : $line->actualEnding], ['Variance', $line->isIncomplete ? null : $line->variance]] as [$label, $val])
            <div class="col-6 col-md-3"><div class="card"><div class="card-body py-2">
                <div class="text-muted small">{{ $label }}</div>
                <div class="fs-4">{{ $fmt($val) }} <span class="text-muted fs-6">{{ $line->baseUnit }}</span></div>
            </div></div></div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Contributing menu items (theoretical usage)</h3></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>Menu item</th><th>Size</th><th class="text-end">Sold</th>
                        <th class="text-end">Portion / unit</th><th class="text-end">Usage</th>
                    </tr></thead>
                    <tbody>
                        @forelse($contributors as $c)
                            <tr>
                                <td>{{ $c['menu_item'] }}</td>
                                <td class="text-capitalize">{{ $c['size'] }}</td>
                                <td class="text-end">{{ $fmt($c['qty_sold']) }}</td>
                                <td class="text-end">{{ $fmt($c['portion']) }} {{ $line->baseUnit }}</td>
                                <td class="text-end fw-bold">{{ $fmt($c['usage']) }} {{ $line->baseUnit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No matched sales used this item this week.</td></tr>
                        @endforelse
                    </tbody>
                    @if(count($contributors))
                        <tfoot><tr class="fw-bold"><td colspan="4" class="text-end">Total theoretical usage</td><td class="text-end">{{ $fmt($line->theoreticalUsage) }} {{ $line->baseUnit }}</td></tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @if($line->warnings)
        <div class="alert alert-warning mt-3"><strong>Notes:</strong>
            <ul class="mb-0">@foreach($line->warnings as $w)<li>{{ $w }}</li>@endforeach</ul>
        </div>
    @endif
</div>
@endsection
