@extends('layouts.tabler')

@section('title', 'Stores')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4">Stores</h1>
    <a href="{{ route('stores.create') }}" class="btn btn-primary mb-3">Create Store</a>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Store Info</th>
                        <th>Contact Name</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Zip</th>
                        <th>Sales Tax Rate</th>
                        <th>Medicare Tax Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stores as $store)
                        <tr>
                            <td>{{ $store->id }}</td>
                            <td>{{ $store->store_info }}</td>
                            <td>{{ $store->contact_name }}</td>
                            <td>{{ $store->phone }}</td>
                            <td>{{ $store->address }}</td>
                            <td>{{ $store->city }}</td>
                            <td>{{ $store->state }}</td>
                            <td>{{ $store->zip }}</td>
                            <td>{{ $store->sales_tax_rate }}</td>
                            <td>{{ $store->medicare_tax_rate }}</td>
                            <td>
                                <a href="{{ route('stores.edit', $store->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('stores.destroy', $store->id) }}" method="POST" style="display:inline;">
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
