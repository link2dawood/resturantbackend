@extends('layouts.tabler')

@section('title', 'View Chart of Account')

@section('styles')
<style>
    /* Material UI Typography */
    .material-headline {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 2rem;
        font-weight: 400;
        line-height: 2.5rem;
        letter-spacing: 0;
        color: #202124;
        margin: 0;
    }
    
    .material-subtitle {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.25rem;
        color: #5f6368;
        margin: 0.5rem 0 0 0;
    }
    
    /* Material UI Buttons */
    .btn-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 0.0892857143em;
        text-transform: uppercase;
        padding: 0.625rem 1.5rem;
        border-radius: 4px;
        border: none;
        box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 0px 2px 2px 0px rgba(0, 0, 0, 0.14), 0px 1px 5px 0px rgba(0, 0, 0, 0.12);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 64px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .btn-material-primary {
        background-color: #1976d2;
        color: #fff;
    }
    
    .btn-material-primary:hover {
        background-color: #1565c0;
        color: #fff;
    }
    
    .btn-material-outlined {
        background-color: transparent;
        border: 1px solid rgba(0, 0, 0, 0.12);
        color: #1976d2;
        box-shadow: none;
    }
    
    .btn-material-outlined:hover {
        background-color: rgba(25, 118, 210, 0.04);
        border-color: #1976d2;
        box-shadow: none;
    }
    
    /* Material UI Cards */
    .card-material {
        background: #ffffff;
        border-radius: 4px;
        box-shadow: 0px 2px 1px -1px rgba(0, 0, 0, 0.2), 0px 1px 1px 0px rgba(0, 0, 0, 0.14), 0px 1px 3px 0px rgba(0, 0, 0, 0.12);
        transition: box-shadow 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        height: 100%;
    }
    
    .card-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
    }
    
    .card-header-material {
        background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
        color: #fff;
        padding: 1rem 1.5rem;
        border-bottom: none;
    }
    
    .card-header-material.info {
        background: linear-gradient(135deg, #0288d1 0%, #01579b 100%);
    }
    
    .card-header-material.secondary {
        background: linear-gradient(135deg, #616161 0%, #424242 100%);
    }
    
    .card-header-material h5 {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1rem;
        font-weight: 500;
        margin: 0;
        letter-spacing: 0.00938em;
    }
    
    /* Material UI Badges */
    .badge-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.25rem 0.75rem;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
    }
    
    /* Material UI Table */
    .table-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    .table-material thead th {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.03333em;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        padding: 1rem;
    }
    
    .table-material tbody td {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        color: rgba(0, 0, 0, 0.87);
    }
    
    .table-material tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.04);
    }
    
    /* Detail List */
    .detail-list {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    .detail-list dt {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.03333em;
        margin-bottom: 0.5rem;
    }
    
    .detail-list dd {
        font-size: 0.875rem;
        color: rgba(0, 0, 0, 0.87);
        margin-bottom: 1.5rem;
    }
    
    .detail-list dd:last-child {
        margin-bottom: 0;
    }
    
    /* List Group Material */
    .list-group-material {
        border: none;
    }
    
    .list-group-material .list-group-item {
        border: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        padding: 1rem;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        color: rgba(0, 0, 0, 0.87);
    }
    
    .list-group-material .list-group-item:last-child {
        border-bottom: none;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .material-headline {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        
        .btn-material {
            font-size: 0.8125rem;
            padding: 0.5rem 1rem;
            min-width: 56px;
            height: 32px;
        }
        
        .card-header-material {
            padding: 0.75rem 1rem;
        }
        
        .detail-list dd {
            margin-bottom: 1rem;
        }
    }
    
    @media (max-width: 576px) {
        .material-headline {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
        
        .table-material thead th,
        .table-material tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.8125rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <!-- Header Section -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <div>
                    <h1 class="material-headline">{{ $chartOfAccount->account_name }}</h1>
                    <p class="material-subtitle">Account Code: {{ $chartOfAccount->account_code }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(!$chartOfAccount->is_system_account)
                        <a href="{{ route('coa.edit', $chartOfAccount) }}" class="btn btn-material btn-material-primary d-flex align-items-center gap-2">
                            <i class="bi bi-pencil"></i>
                            <span>Edit</span>
                        </a>
                    @endif
                    <a href="{{ route('coa.index') }}" class="btn btn-material btn-material-outlined d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>

            <!-- Details Cards -->
            <div class="row g-3 g-md-4 mb-4">
                <div class="col-12 col-lg-6">
                    <div class="card-material">
                        <div class="card-header-material">
                            <h5><i class="bi bi-info-circle me-2"></i>Account Details</h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <dl class="detail-list mb-0">
                                <dt>Account Code</dt>
                                <dd>
                                    <strong style="font-size: 1rem; color: #1976d2;">{{ $chartOfAccount->account_code }}</strong>
                                </dd>

                                <dt>Account Type</dt>
                                <dd>
                                    <span class="badge-material bg-secondary text-white">{{ $chartOfAccount->account_type }}</span>
                                </dd>

                                <dt>Parent Account</dt>
                                <dd>
                                    @if($chartOfAccount->parent)
                                        <a href="{{ route('coa.show', $chartOfAccount->parent) }}" class="text-decoration-none" style="color: #1976d2;">
                                            <i class="bi bi-link-45deg me-1"></i>
                                            {{ $chartOfAccount->parent->account_code }} - {{ $chartOfAccount->parent->account_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">None (top level)</span>
                                    @endif
                                </dd>

                                <dt>Status</dt>
                                <dd>
                                    @if($chartOfAccount->is_active)
                                        <span class="badge-material bg-success text-white">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge-material bg-danger text-white">
                                            <i class="bi bi-x-circle me-1"></i>Inactive
                                        </span>
                                    @endif
                                    @if($chartOfAccount->is_system_account)
                                        <span class="badge-material bg-info text-white ms-2">
                                            <i class="bi bi-shield-check me-1"></i>System
                                        </span>
                                    @endif
                                </dd>

                                <dt>Created By</dt>
                                <dd>
                                    <i class="bi bi-person me-1"></i>
                                    {{ $chartOfAccount->creator?->name ?? 'System' }}
                                </dd>

                                <dt>Created At</dt>
                                <dd>
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $chartOfAccount->created_at->format('M d, Y h:i A') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <div class="card-material">
                        <div class="card-header-material info">
                            <h5><i class="bi bi-shop me-2"></i>Store Availability</h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            @if($chartOfAccount->stores->isEmpty())
                                <div class="d-flex align-items-center text-muted">
                                    <i class="bi bi-globe-americas me-2" style="font-size: 1.5rem;"></i>
                                    <span>Available to all stores.</span>
                                </div>
                            @else
                                <ul class="list-group list-group-material">
                                    @foreach($chartOfAccount->stores as $store)
                                        <li class="list-group-item d-flex align-items-center">
                                            <i class="bi bi-shop me-2 text-primary"></i>
                                            <span>{{ $store->store_info }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sub-Accounts Card -->
            @if($chartOfAccount->children->isNotEmpty())
                <div class="card-material">
                    <div class="card-header-material secondary">
                        <h5>
                            <i class="bi bi-diagram-3 me-2"></i>
                            Sub-Accounts ({{ $chartOfAccount->children->count() }})
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-material table-hover mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Name</th>
                                        <th class="d-none d-md-table-cell">Type</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($chartOfAccount->children as $child)
                                        <tr>
                                            <td><strong>{{ $child->account_code }}</strong></td>
                                            <td>{{ $child->account_name }}</td>
                                            <td class="d-none d-md-table-cell">
                                                <span class="badge-material bg-secondary text-white">{{ $child->account_type }}</span>
                                            </td>
                                            <td>
                                                @if($child->is_active)
                                                    <span class="badge-material bg-success text-white">Active</span>
                                                @else
                                                    <span class="badge-material bg-danger text-white">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('coa.show', $child) }}" class="btn btn-material btn-material-outlined" title="View" style="min-width: auto; padding: 0.5rem;">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
