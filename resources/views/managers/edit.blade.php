@extends('layouts.tabler')

@section('title', 'Edit Manager')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4">Edit Manager</h1>
    <form action="{{ route('managers.update', $manager->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $manager->name }}" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ $manager->email }}" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password">
            <small class="form-text text-muted">Leave blank if you don't want to change the password.</small>
        </div>
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" value="{{ $manager->username }}">
        </div>
        <div class="mb-3">
            <label for="store_id" class="form-label">Assigned Store</label>
            <select class="form-control" id="store_id" name="store_id">
                <option value="">Select a store</option>
                @foreach ($stores as $store)
                    <option value="{{ $store->id }}" {{ $manager->store_id == $store->id ? 'selected' : '' }}>{{ $store->store_info }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Manager</button>
    </form>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#store_id').select2({
            placeholder: 'Select a store',
            allowClear: true
        });
    });
</script>
@endpush
