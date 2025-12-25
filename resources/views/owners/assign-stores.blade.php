@extends('layouts.tabler')

@section('title', 'Assign Stores')

@section('content')
<div class="container-xl">
    <h2>Assign Stores to {{ $owner->name }}</h2>

    @if($stores->count() > 0)
    <form action="{{ route('owners.assign-stores', $owner) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="store_ids">Select Stores</label>
                <select name="store_ids[]" id="store_ids" class="form-control" multiple size="10">
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ in_array($store->id, $assignedStores) ? 'selected' : '' }}>
                            {{ $store->store_info }} - {{ $store->city }}, {{ $store->state }}
                    </option>
                @endforeach
            </select>
                <small class="form-text text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple stores.</small>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Assign Stores</button>
            <a href="{{ route('owners.show', $owner) }}" class="btn btn-secondary mt-3">Cancel</a>
    </form>
    @else
        <div class="alert alert-warning">
            <h4>No Stores Available</h4>
            <p>There are no stores available to assign. Please create stores first before assigning them to owners.</p>
            <a href="{{ route('stores.create') }}" class="btn btn-primary">Create Store</a>
            <a href="{{ route('owners.show', $owner) }}" class="btn btn-secondary">Back to Owner</a>
        </div>
    @endif
</div>
@endsection
