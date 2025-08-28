@extends('layouts.tabler')

@section('title', 'Managers')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4">Managers</h1>
    <a href="{{ route('managers.create') }}" class="btn btn-primary mb-3">Create Manager</a>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Assigned Stores</th>
                        <th>Last Online</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($managers as $manager)
                        <tr>
                            <td>{{ $manager->id }}</td>
                            <td>{{ $manager->name }}</td>
                            <td>{{ $manager->email }}</td>
                            <td>{{ $manager->username }}</td>
                            <td>
                                @foreach ($manager->stores as $store)
                                    <span class="badge bg-primary">{{ $store->store_info }}</span>
                                @endforeach
                            </td>
                            <td>{{ $manager->last_online }}</td>
                            <td>
                                <a href="{{ route('managers.edit', $manager->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('managers.destroy', $manager->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
