@extends('layouts.tabler')

@section('title', 'Import Recipes')

@section('content')
<div class="container-xl mt-4" style="max-width: 640px;">
    <h1 class="mb-3">Import Recipes (CSV)</h1>

    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.menu-items.import') }}" enctype="multipart/form-data">
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
                <label class="form-label">CSV file <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
            </div>

            <div class="alert alert-info small mb-3">
                <strong>Format</strong> — one row per ingredient, with a header line:
                <pre class="mb-1 mt-1">menu_item,size_variant,ingredient,quantity,unit</pre>
                <div>Example:</div>
                <pre class="mb-0">Standard Steak Sandwich,regular,Ribeye Steak,4.5,oz
Standard Steak Sandwich,regular,Hoagie Roll,1,each
Standard Steak Sandwich,mini,Ribeye Steak,3,oz</pre>
                <div class="mt-1">Rows for the same menu item + size become one recipe (a new version). The
                    ingredient name must match an inventory item in the store, and the unit must be the
                    item's base or purchase unit.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Import</button>
                <a href="{{ route('admin.menu-items.index', ['store_id' => $store->id]) }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
