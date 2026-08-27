@extends('layouts.tabler')

@section('title', 'Square Sales Import')

@section('content')
<div class="container-xl mt-4" style="max-width: 640px;">
    <h1 class="mb-1">Square Sales Import</h1>
    <p class="text-muted">Upload the weekly Square "Items Sold" report. You'll preview and map items before anything is saved.</p>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.square-import.preview') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="store_id" value="{{ $store->id }}">

            @if($stores->isNotEmpty())
                <div class="mb-3">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-select">
                        @foreach($stores as $s)<option value="{{ $s->id }}" @selected($s->id === $store->id)>{{ $s->store_info ?? ('Store #'.$s->id) }}</option>@endforeach
                    </select>
                </div>
            @endif

            <div class="mb-3">
                <label class="form-label">Week</label>
                <input type="date" name="week_start_date" class="form-control" value="{{ $week->toDateString() }}">
                <div class="form-text">Any day in the target week — it snaps to that Monday.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Square CSV <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
            </div>

            <div class="alert alert-info small mb-3">
                Export the <strong>Items Sold</strong> report from Square as CSV. The importer reads the
                item name, variation (size), and quantity columns; the totals row is ignored.
            </div>

            <button type="submit" class="btn btn-primary">Preview import</button>
        </form>
    </div></div>
</div>
@endsection
