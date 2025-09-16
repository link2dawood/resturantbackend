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
                                            <div class="btn-list flex-nowrap">
                                                <a href="{{ route('revenue-income-types.show', $type) }}" class="btn btn-white btn-sm d-flex align-items-center" style="gap: 0.25rem;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                        <path d="M12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                                                    </svg>
                                                    View
                                                </a>
                                                <a href="{{ route('revenue-income-types.edit', $type) }}" class="btn btn-white btn-sm d-flex align-items-center" style="gap: 0.25rem;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                                                    </svg>
                                                    Edit
                                                </a>
                                                <form action="{{ route('revenue-income-types.destroy', $type) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this revenue income type?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-white btn-sm text-danger d-flex align-items-center" style="gap: 0.25rem;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <polyline points="3,6 5,6 21,6"/>
                                                            <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                                        </svg>
                                                        Delete
                                                    </button>
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