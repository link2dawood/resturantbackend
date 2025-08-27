@extends('layouts.tabler')

@section('title', 'Owners')

@section('content')

    <div class="container mt-5">
    <div style="display:flex;justify-content: space-between;">
        <h1 class="mb-4">Owners</h1>
        <a href="{{ route('owners.create') }}" class="btn btn-primary mb-3">Create Owner</a>
    </div>
      

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Avatar</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($owners as $owner)
                            <tr>
                                <td>{{ $owner->id }}</td>
                                <td>{{ $owner->name }}</td>
                                <td>{{ $owner->email }}</td>
                                <td>
                                    @if ($owner->avatar)
                                        <img src="{{ asset('storage/' . $owner->avatar) }}" alt="Avatar" class="img-thumbnail" style="width: 50px; height: 50px;">
                                    @else
                                        <img src="{{ asset('images/default-owner.png') }}" alt="Default Avatar" class="img-thumbnail" style="width: 50px; height: 50px;">
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('owners.edit', $owner->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                    <form action="{{ route('owners.destroy', $owner->id) }}" method="POST" style="display:inline;">
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
