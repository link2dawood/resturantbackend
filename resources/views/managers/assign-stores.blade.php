@extends('layouts.tabler')

@section('title', 'Assign Store')

@section('content')
<div class="container-xl">
    <h2>Assign Store to {{ $manager->name }}</h2>

    <form action="{{ route('managers.assign-stores', $manager->id) }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="store_id">Select Store</label>
            <select name="store_id" id="store_id" class="form-control">
                <option value="">Select a store</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}" {{ $manager->store_id == $store->id ? 'selected' : '' }}>
                        {{ $store->store_info }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Assign Store</button>
    </form>
</div>
@endsection
