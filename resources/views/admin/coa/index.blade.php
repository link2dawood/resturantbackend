@extends('layouts.tabler')

@section('title', 'Chart of Accounts')

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
    
    .btn-material:active {
        box-shadow: 0px 5px 5px -3px rgba(0, 0, 0, 0.2), 0px 8px 10px 1px rgba(0, 0, 0, 0.14), 0px 3px 14px 2px rgba(0, 0, 0, 0.12);
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
    
    .btn-material-text {
        background-color: transparent;
        color: #1976d2;
        box-shadow: none;
        padding: 0.5rem 0.75rem;
    }
    
    .btn-material-text:hover {
        background-color: rgba(25, 118, 210, 0.04);
        box-shadow: none;
    }
    
    /* Material UI Cards */
    .card-material {
        background: #ffffff;
        border-radius: 4px;
        box-shadow: 0px 2px 1px -1px rgba(0, 0, 0, 0.2), 0px 1px 1px 0px rgba(0, 0, 0, 0.14), 0px 1px 3px 0px rgba(0, 0, 0, 0.12);
        transition: box-shadow 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }
    
    .card-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
    }
    
    /* Material UI Form Controls */
    .form-control-material,
    .form-select-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1rem;
        line-height: 1.5rem;
        color: #202124;
        background-color: transparent;
        border: none;
        border-bottom: 1px solid rgba(0, 0, 0, 0.42);
        border-radius: 0;
        padding: 0.5rem 0;
        transition: border-color 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .form-control-material:focus,
    .form-select-material:focus {
        border-bottom: 2px solid #1976d2;
        outline: none;
        box-shadow: none;
    }
    
    /* Override for standard form controls in filters */
    .card-material .form-control,
    .card-material .form-select {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 4px;
        padding: 0.5rem 0.75rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-material .form-control:focus,
    .card-material .form-select:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 0.2rem rgba(25, 118, 210, 0.25);
    }
    
    .form-label-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        font-weight: 400;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.03333em;
        margin-bottom: 0.5rem;
        display: block;
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
    
    /* Material UI Badges */
    .badge-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.25rem 0.5rem;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
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
        
        .table-material {
            font-size: 0.875rem;
        }
        
        .table-material thead th,
        .table-material tbody td {
            padding: 0.75rem 0.5rem;
        }
    }
    
    @media (max-width: 576px) {
        .material-headline {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
        
        .table-material thead th,
        .table-material tbody td {
            padding: 0.5rem 0.25rem;
            font-size: 0.8125rem;
        }
        
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="material-headline">Chart of Accounts</h1>
            <p class="material-subtitle">Manage and categorize the financial accounts used across your restaurants.</p>
        </div>
        <a href="{{ route('coa.create') }}" class="btn btn-material btn-material-primary d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i>
            <span>Add Account</span>
        </a>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-4" role="alert" style="border-radius: 4px; box-shadow: 0px 2px 1px -1px rgba(0, 0, 0, 0.2), 0px 1px 1px 0px rgba(0, 0, 0, 0.14), 0px 1px 3px 0px rgba(0, 0, 0, 0.12);">
            <i class="bi bi-check-circle-fill me-2"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-4" role="alert" style="border-radius: 4px; box-shadow: 0px 2px 1px -1px rgba(0, 0, 0, 0.2), 0px 1px 1px 0px rgba(0, 0, 0, 0.14), 0px 1px 3px 0px rgba(0, 0, 0, 0.12);">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <span>{{ $errors->first('error') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters Card -->
    <div class="card-material mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('coa.index') }}" method="GET" class="row g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="account_type" class="form-label-material">Account Type</label>
                    <select name="account_type" id="account_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($accountTypes as $type)
                            <option value="{{ $type }}" @selected(request('account_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="is_active" class="form-label-material">Status</label>
                    <select name="is_active" id="is_active" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" @selected(request('is_active', '1') === '1')>Active</option>
                        <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="store_id" class="form-label-material">Store</label>
                    <select name="store_id" id="store_id" class="form-select">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) request('store_id') === (string) $store->id)>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="search" class="form-label-material">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Account code or name">
                        <button class="btn btn-material btn-material-outlined" type="submit" style="border-radius: 0 4px 4px 0; min-width: auto; padding: 0.5rem 0.75rem;">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="{{ route('coa.index') }}" class="btn btn-material btn-material-outlined" style="border-radius: 0; min-width: auto; padding: 0.5rem 0.75rem;">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card-material">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-material table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Account Name</th>
                            <th scope="col">Type</th>
                            <th scope="col" class="d-none d-md-table-cell">Stores</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coas as $coa)
                            <tr>
                                <td><strong>{{ $coa->account_code }}</strong></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span>{{ $coa->account_name }}</span>
                                        @if($coa->is_system_account)
                                            <span class="badge-material bg-info text-white mt-1" style="width: fit-content;">System</span>
                                        @endif
                                    </div>
                                    <div class="d-md-none mt-1">
                                        <small class="text-muted">
                                            @if($coa->stores->isEmpty())
                                                All Stores
                                            @else
                                                {{ $coa->stores->pluck('store_info')->implode(', ') }}
                                            @endif
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-material bg-secondary text-white">{{ $coa->account_type }}</span>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    @if($coa->stores->isEmpty())
                                        <span class="text-muted">All Stores</span>
                                    @else
                                        <span class="text-muted">{{ $coa->stores->pluck('store_info')->implode(', ') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($coa->is_active)
                                        <span class="badge-material bg-success text-white">Active</span>
                                    @else
                                        <span class="badge-material bg-danger text-white">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('coa.show', $coa) }}" class="btn btn-material btn-material-text" title="View" style="min-width: auto; padding: 0.5rem;">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('coa.edit', $coa) }}" class="btn btn-material btn-material-text" title="Edit" style="min-width: auto; padding: 0.5rem;">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if(!$coa->is_system_account)
                                            <form action="{{ route('coa.destroy', $coa) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-material btn-material-text text-danger" title="Deactivate" style="min-width: auto; padding: 0.5rem;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                                    <p class="mb-3">No accounts found with the current filters.</p>
                                    <a href="{{ route('coa.create') }}" class="btn btn-material btn-material-primary">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        <span>Add First Account</span>
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 p-md-4">
                <x-pagination :paginator="$coas" />
            </div>
        </div>
    </div>
</div>
@endsection
