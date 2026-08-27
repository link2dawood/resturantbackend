@extends('layouts.tabler')

@section('title', 'Square Import — Preview')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="mb-0">Preview &amp; map</h1>
            <div class="text-muted">{{ $store->store_info ?? 'Store' }} · week of {{ $week->format('M j, Y') }}</div>
        </div>
        <a href="{{ route('admin.square-import.form', ['store_id' => $store->id]) }}" class="btn btn-link">Start over</a>
    </div>

    <div class="alert {{ $unmatchedCount ? 'alert-warning' : 'alert-success' }}">
        <strong>{{ $matchedCount }}</strong> matched · <strong>{{ $unmatchedCount }}</strong> unmatched.
        @if($unmatchedCount)Map the unmatched rows below (or leave them — they'll be recorded but excluded from variance).@endif
        Nothing is saved until you commit.
    </div>

    <form method="POST" action="{{ route('admin.square-import.commit') }}">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}">
        <input type="hidden" name="week_start_date" value="{{ $week->toDateString() }}">

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>Square item</th><th style="width:90px;">Qty</th>
                            <th style="width:30%;">Menu item</th><th style="width:140px;">Size</th><th style="width:110px;">Status</th>
                        </tr></thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr class="{{ $row['matched'] ? '' : 'table-warning' }}">
                                    <td>
                                        {{ $row['square_raw_name'] }}
                                        @if($row['variation'])<span class="text-muted small d-block">{{ $row['variation'] }}</span>@endif
                                        <input type="hidden" name="square_raw_name[]" value="{{ $row['square_raw_name'] }}">
                                    </td>
                                    <td>
                                        {{ rtrim(rtrim(number_format($row['quantity'], 4, '.', ''), '0'), '.') }}
                                        <input type="hidden" name="quantity[]" value="{{ $row['quantity'] }}">
                                    </td>
                                    <td>
                                        <select name="menu_item_id[]" class="form-select form-select-sm">
                                            <option value="">— unmatched —</option>
                                            @foreach($menuItems as $mi)
                                                <option value="{{ $mi->id }}" @selected($mi->id === $row['menu_item_id'])>{{ $mi->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="size_variant[]" class="form-select form-select-sm text-capitalize">
                                            @foreach($sizes as $size)
                                                <option value="{{ $size }}" @selected($size === $row['size_variant'])>{{ $size }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        @if($row['matched'])<span class="badge bg-green-lt">matched</span>
                                        @else<span class="badge bg-yellow-lt">unmatched</span>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.square-import.form', ['store_id' => $store->id]) }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" onclick="return confirm('Commit this import? It replaces any existing sales for this week.');">
                    Commit import
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
