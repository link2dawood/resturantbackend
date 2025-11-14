@extends('layouts.tabler')

@section('title', 'Chart of Accounts')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Chart of Accounts</h1>
            <p class="text-muted mb-0">Manage and categorize the financial accounts used across your restaurants.</p>
        </div>
        <a href="{{ route('coa.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add Account
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('coa.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="account_type" class="form-label">Account Type</label>
                    <select name="account_type" id="account_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach($accountTypes as $type)
                            <option value="{{ $type }}" @selected(request('account_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="is_active" class="form-label">Status</label>
                    <select name="is_active" id="is_active" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" @selected(request('is_active', '1') === '1')>Active</option>
                        <option value="0" @selected(request('is_active') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="store_id" class="form-label">Store</label>
                    <select name="store_id" id="store_id" class="form-select">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected((string) request('store_id') === (string) $store->id)>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Account code or name">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                        <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Code</th>
                            <th scope="col">Account Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Stores</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coas as $coa)
                            <tr>
                                <td><strong>{{ $coa->account_code }}</strong></td>
                                <td>
                                    {{ $coa->account_name }}
                                    @if($coa->is_system_account)
                                        <span class="badge bg-info ms-2">System</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-secondary">{{ $coa->account_type }}</span></td>
                                <td>
                                    @if($coa->stores->isEmpty())
                                        <span class="text-muted">All Stores</span>
                                    @else
                                        <span class="text-muted">{{ $coa->stores->pluck('store_info')->implode(', ') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($coa->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('coa.show', $coa) }}" class="btn btn-outline-secondary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('coa.edit', $coa) }}" class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if(!$coa->is_system_account)
                                            <form action="{{ route('coa.destroy', $coa) }}" method="POST" class="d-inline" onsubmit="return confirm('Deactivate this account?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Deactivate">
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
                                    <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                    No accounts found with the current filters.
                                    <div class="mt-3">
                                        <a href="{{ route('coa.create') }}" class="btn btn-primary">
                                            <i class="bi bi-plus-circle me-2"></i>Add First Account
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($coas->hasPages())
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ $coas->firstItem() }} to {{ $coas->lastItem() }} of {{ $coas->total() }} accounts
                    </small>
                    {{ $coas->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


