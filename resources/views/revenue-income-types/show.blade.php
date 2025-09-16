@extends('layouts.tabler')

@section('title', 'Revenue Income Type Details')

@section('content')
<div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('revenue-income-types.index') }}">Revenue Income Types</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $revenueIncomeType->name }}</li>
                    </ol>
                </nav>
                <h2 class="page-title">{{ $revenueIncomeType->name }}</h2>
            </div>
            <div class="col-12 col-md-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('revenue-income-types.edit', $revenueIncomeType) }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
                            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
                            <path d="M16 5l3 3"/>
                        </svg>
                        Edit
                    </a>
                </div>
            </div>
        </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Revenue Income Type Details</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <div class="form-control-plaintext">{{ $revenueIncomeType->name }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <div class="form-control-plaintext">
                                    <span class="badge badge-outline text-{{ $revenueIncomeType->category == 'cash' ? 'success' : ($revenueIncomeType->category == 'online' ? 'info' : 'secondary') }}">
                                        {{ ucfirst($revenueIncomeType->category) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <div class="form-control-plaintext">{{ $revenueIncomeType->description ?: 'No description provided' }}</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <div class="form-control-plaintext">{{ $revenueIncomeType->sort_order }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-control-plaintext">
                                    @if($revenueIncomeType->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($revenueIncomeType->metadata && count($revenueIncomeType->metadata) > 0)
                        <div class="mb-3">
                            <label class="form-label">Metadata</label>
                            <div class="form-control-plaintext">
                                <code>{{ json_encode($revenueIncomeType->metadata, JSON_PRETTY_PRINT) }}</code>
                            </div>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Created</label>
                                <div class="form-control-plaintext">{{ $revenueIncomeType->created_at->format('M j, Y g:i A') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Last Updated</label>
                                <div class="form-control-plaintext">{{ $revenueIncomeType->updated_at->format('M j, Y g:i A') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Actions</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('revenue-income-types.edit', $revenueIncomeType) }}" class="list-group-item list-group-item-action">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
                                        <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
                                        <path d="M16 5l3 3"/>
                                    </svg>
                                </div>
                                <div class="col">
                                    <div>Edit Revenue Type</div>
                                    <div class="text-muted">Modify details</div>
                                </div>
                            </div>
                        </a>
                        <form action="{{ route('revenue-income-types.destroy', $revenueIncomeType) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to delete this revenue income type?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="list-group-item list-group-item-action text-danger">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <line x1="4" y1="7" x2="20" y2="7"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
                                            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
                                        </svg>
                                    </div>
                                    <div class="col">
                                        <div>Delete Revenue Type</div>
                                        <div class="text-muted">Permanently remove</div>
                                    </div>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection