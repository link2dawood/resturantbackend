@extends('layouts.tabler')

@section('title', $menuItem->exists ? 'Edit Menu Item' : 'New Menu Item')

@section('content')
<div class="container-xl mt-4" style="max-width: 640px;">
    <h1 class="mb-3">{{ $menuItem->exists ? 'Edit Menu Item' : 'New Menu Item' }}</h1>

    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ $menuItem->exists ? route('admin.menu-items.update', $menuItem) : route('admin.menu-items.store') }}">
            @csrf
            @if($menuItem->exists)@method('PUT')@else<input type="hidden" name="store_id" value="{{ $store->id }}">@endif

            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" maxlength="150" required
                       value="{{ old('name', $menuItem->name) }}" placeholder="e.g. Standard Steak Sandwich">
            </div>
            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" maxlength="50"
                       value="{{ old('category', $menuItem->category) }}" placeholder="e.g. Sandwich">
            </div>
            <div class="mb-3">
                <label class="form-label">Square item name</label>
                <input type="text" name="square_name" class="form-control" maxlength="191"
                       value="{{ old('square_name', $menuItem->square_name) }}" placeholder="Name as it appears in the Square Items Sold report">
                <div class="form-text">Used to match Square sales imports to this item.</div>
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                       @checked(old('is_active', $menuItem->exists ? $menuItem->is_active : true))>
                <label class="form-check-label" for="isActive">Active</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ $menuItem->exists ? 'Save' : 'Create' }}</button>
                <a href="{{ route('admin.menu-items.index', ['store_id' => $store->id]) }}" class="btn btn-link">Cancel</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
