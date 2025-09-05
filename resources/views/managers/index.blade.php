@extends('layouts.tabler')

@section('title', 'Managers')

@section('content')
<div class="container mt-5">
   <div style="display:flex;justify-content: space-between;">
    <h1 class="mb-4">Managers</h1>
    @if(Auth::user()->hasPermission('manage_managers'))
    <a href="{{ route('managers.create') }}" class="btn btn-primary mb-3">Create Manager</a>
    @endif
    </div>
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
                          @if(Auth::user()->hasPermission('manage_managers'))
                        <th>Actions</th>
                        @endif
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
                                @if ($manager->stores->isNotEmpty())
                                    @foreach ($manager->stores as $store)
                                        <span class="badge bg-primary">{{ $store['store_info'] }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">No stores assigned</span>
                                @endif
                            </td>
                             @if(Auth::user()->hasPermission('manage_managers'))
                            <td>
                                <a href="{{ route('managers.edit', $manager->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('managers.destroy', $manager->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
