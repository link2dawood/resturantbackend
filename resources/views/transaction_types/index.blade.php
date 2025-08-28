@extends('layouts.tabler')

@section('title', 'Transaction Types')

@section('content')
<div class="container-xl mt-5">
   <div style="display:flex;justify-content: space-between;">
    <h2>Transaction Types</h2>

    <a href="{{ route('transaction-types.create') }}" class="btn btn-primary mb-3">Add Transaction Type</a>
</div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Parent</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactionTypes as $type)
                <tr>
                    <td>{{ $type->id }}</td>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->parent ? $type->parent->name : 'None' }}</td>
                    <td>
                        <a href="{{ route('transaction-types.edit', $type->id) }}" class="btn btn-warning">Edit</a>
                        <form action="{{ route('transaction-types.destroy', $type->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
