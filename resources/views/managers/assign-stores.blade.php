@extends('layouts.tabler')

@section('title', 'Assign Store')

@section('content')
<div class="container-xl">
    <h2>Assign Store to {{ $manager->name }}</h2>

    @if($stores->count() > 0)
    <form action="{{ route('managers.assign-stores', $manager->id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="store_id">Select Store</label>
                <select name="store_id" id="store_id" class="form-control" required>
                <option value="">Select a store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ $manager->store_id == $store->id ? 'selected' : '' }}>
                            {{ $store->store_info }} - {{ $store->city }}, {{ $store->state }}
                    </option>
                @endforeach
            </select>
        </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Assign Store</button>
                <a href="{{ route('managers.show', $manager) }}" class="btn btn-secondary">Cancel</a>
            </div>
    </form>
    @else
        <div class="alert alert-warning">
            <h4>No Stores Available</h4>
            <p>There are no stores available to assign. Please create stores first before assigning them to managers.</p>
            <a href="{{ route('stores.create') }}" class="btn btn-primary">Create Store</a>
            <a href="{{ route('managers.show', $manager) }}" class="btn btn-secondary">Back to Manager</a>
        </div>
    @endif
</div>
@endsection
