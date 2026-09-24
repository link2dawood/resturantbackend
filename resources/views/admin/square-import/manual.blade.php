@extends('layouts.tabler')

@section('title', 'Enter sales by hand')

@section('content')
<div class="container-xl py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h3 mb-0">Enter sales by hand</h1>
            <div class="text-muted">{{ $store->store_info }} &middot; week of {{ \Carbon\Carbon::parse($week)->format('M j, Y') }}</div>
        </div>
        <a href="{{ route('admin.square-import.form', ['store_id' => $store->id]) }}" class="btn btn-outline-primary">
            Upload the Square file instead
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-info">
        The Square upload is the normal way to do this. Use this screen when the export is not to hand.
        Whatever you save here <strong>replaces</strong> the whole week, so enter every item that sold, not just the ones you missed.
    </div>

    @if($importedFromCsv)
        <div class="alert alert-warning">
            This week already has figures from a Square upload. Saving here will replace them.
        </div>
    @endif

    <form method="GET" action="{{ route('admin.square-import.manual') }}" class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label">Store</label>
            <select name="store_id" class="form-select" onchange="this.form.submit()">
                @foreach($stores as $option)
                    <option value="{{ $option->id }}" @selected($option->id === $store->id)>{{ $option->store_info }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label">Week beginning</label>
            <input type="date" name="week_start_date" value="{{ $week }}" class="form-control" onchange="this.form.submit()">
        </div>
    </form>

    <form method="POST" action="{{ route('admin.square-import.manual.store') }}">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="week_start_date" value="{{ $week }}">

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Menu item</th>
                            @foreach($sizes as $size)
                                <th class="text-end text-capitalize">{{ $size }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($menuItems as $menuItem)
                            <tr>
                                <td>{{ $menuItem->name }}</td>
                                @foreach($sizes as $size)
                                    @php
                                        $key = $menuItem->id.'-'.$size;
                                        $row = $existing[$key] ?? null;
                                        $value = $row ? rtrim(rtrim(number_format((float) $row->quantity_sold, 2, '.', ''), '0'), '.') : '';
                                    @endphp
                                    <td class="text-end" style="width: 140px;">
                                        <input type="number" inputmode="numeric" step="1" min="0"
                                               class="form-control text-end"
                                               name="sold[{{ $key }}]"
                                               value="{{ $value }}"
                                               placeholder="0"
                                               aria-label="{{ $menuItem->name }} sold, {{ $size }}">
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($sizes) + 1 }}" class="text-center text-muted py-4">
                                    No menu items for this store yet. Add them under Menu &amp; Recipes first,
                                    otherwise there is nothing for the sales to deduct from.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($menuItems->isNotEmpty())
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save the week's sales</button>
                <a href="{{ route('admin.variance.index', ['store_id' => $store->id, 'week_start_date' => $week]) }}" class="btn btn-link">
                    See the variance this feeds
                </a>
            </div>
        @endif
    </form>
</div>
@endsection
