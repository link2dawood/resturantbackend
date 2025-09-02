@extends('layouts.tabler')

@section('title', 'Revenue Income Types')

@section('content')
<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Revenue Income Types
                </h2>
                <div class="text-muted mt-1">Manage revenue income types for daily reports</div>
            </div>
            <div class="col-12 col-md-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('revenue-income-types.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add New Revenue Type
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-vcenter table-mobile-md card-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Category</th>
                                    <th>Sort Order</th>
                                    <th>Status</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenueIncomeTypes as $type)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <strong>{{ $type->name }}</strong>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-muted">{{ $type->description ?? 'No description' }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline text-{{ $type->category == 'cash' ? 'success' : ($type->category == 'online' ? 'info' : 'secondary') }}">
                                                {{ ucfirst($type->category) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $type->sort_order }}</span>
                                        </td>
                                        <td>
                                            @if($type->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-list flex-nowrap">
                                                <a href="{{ route('revenue-income-types.show', $type) }}" class="btn btn-white btn-sm">View</a>
                                                <a href="{{ route('revenue-income-types.edit', $type) }}" class="btn btn-white btn-sm">Edit</a>
                                                <form action="{{ route('revenue-income-types.destroy', $type) }}" method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this revenue income type?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-white btn-sm text-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No revenue income types found. 
                                            <a href="{{ route('revenue-income-types.create') }}" class="link-primary">Create your first one!</a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($revenueIncomeTypes->hasPages())
                    <div class="card-footer d-flex align-items-center">
                        {{ $revenueIncomeTypes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection