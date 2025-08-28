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
            <label for="assigned_stores" class="form-label">Assigned Stores</label>
            <select class="form-control" id="assigned_stores" name="assigned_stores[]" multiple>
                @foreach ($stores as $store)
                    <option value="{{ $store->id }}" {{ in_array($store->id, json_decode($manager->assigned_stores ?? '[]')) ? 'selected' : '' }}>{{ $store->store_info }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Manager</button>
    </form>
</div>
@endsection
