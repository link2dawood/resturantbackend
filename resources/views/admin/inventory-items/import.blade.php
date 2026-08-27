@extends('layouts.tabler')

@section('title', 'Bulk Import Inventory Items')

@section('content')
<div class="container-xl mt-4">
    <div class="mb-4">
        <a href="{{ route('admin.inventory-items.index', ['store_id' => $store->id]) }}" class="text-muted text-decoration-none small">&larr; Back to inventory items</a>
        <h1 class="mb-0 mt-2" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400;">Bulk Import</h1>
        <p class="text-muted mb-0">Step 1 of 2 &middot; upload the order guide for {{ $store->store_info }}</p>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.inventory-items.import.preview') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="store_id" value="{{ $store->id }}">

                        @if($stores->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label">Store</label>
                            <select class="form-select" name="store_id">
                                @foreach($stores as $s)
                                    <option value="{{ $s->id }}" {{ $s->id === $store->id ? 'selected' : '' }}>{{ $s->store_info }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Items are imported into this store's list.</small>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label for="file" class="form-label">CSV file <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="file" name="file" accept=".csv,text/csv" required>
                            <small class="text-muted">Up to 5 MB. Nothing is saved until you confirm on the next screen.</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Preview import</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title mb-0" style="font-size: 1rem; font-weight: 500;">Expected columns</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-3">
                        <tbody>
                            <tr><td><code>Name</code></td><td class="text-muted small">Required. Ribeye Steak</td></tr>
                            <tr><td><code>Category</code></td><td class="text-muted small">Meats, Breads, Cheese...</td></tr>
                            <tr><td><code>Unit</code></td><td class="text-muted small">box, case, bag</td></tr>
                            <tr><td><code>Portions per Unit</code></td><td class="text-muted small">Required. 53</td></tr>
                            <tr><td><code>Portion Size</code></td><td class="text-muted small">Optional. 3</td></tr>
                            <tr><td><code>Portion Unit</code></td><td class="text-muted small">oz, lb, each</td></tr>
                        </tbody>
                    </table>
                    <p class="text-muted small mb-2">Header names are matched loosely, so "Portions per Box" or "Item Name" also work.</p>
                    <p class="text-muted small mb-0">A category that does not exist yet is flagged on the preview, where you can choose to create it.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
