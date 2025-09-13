@extends('layouts.tabler')

@section('title', 'Assign Stores')

@section('content')
<div class="container-xl">
    <h2>Assign Stores to {{ $owner->name }}</h2>

    <form action="{{ route('owners.assign-stores', $owner) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="store_ids">Select Stores</label>
            <select name="store_ids[]" id="store_ids" class="form-control" multiple>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ in_array($store->id, $assignedStores) ? 'selected' : '' }}>
                        {{ $store->store_info }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Assign Stores</button>
    </form>
</div>
@endsection
