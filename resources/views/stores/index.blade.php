@extends('layouts.tabler')

@section('title', 'Stores')

@section('content')
<div class="container mt-5">
   <div style="display:flex;justify-content: space-between;">
    <h1 class="mb-4">Stores</h1>
    <a href="{{ route('stores.create') }}" class="btn btn-primary mb-3">Create Store</a>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                          @if(Auth::user()->role == 'admin')
                          <th>Owner</th>
                          @endif
                        <th>Store Info</th>    
                        <th>Address</th>
                        <th>Sales Tax Rate</th>
                        <th>Medicare Tax Rate</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stores as $store)
                    @php
                      $owner = App\Models\User::find($store->created_by);
                    @endphp
                        <tr>
                            <td>{{ $store->id }}</td>
                              @if(Auth::user()->role == 'admin')
                              <td>
                              {{@$owner->name}}
                              </td>
                              @endif
                            <td>{{ $store->store_info }}</td>
                            <td>{{ $store->address }}</td>

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
