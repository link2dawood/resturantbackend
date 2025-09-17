@extends('layouts.tabler')

@section('title', 'Revenue Income Types')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Revenue Income Types</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage revenue income types for daily reports</p>
        </div>
        <a href="{{ route('revenue-income-types.create') }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Revenue Type
        </a>
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
                                            <a href="{{ route('revenue-income-types.show', $type) }}" class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View Details">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                            </a>
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