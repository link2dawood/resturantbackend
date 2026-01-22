@extends('layouts.tabler')

@section('title', 'Chart of Accounts')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Chart of Accounts</h1>
            <p class="text-muted mb-0">Manage and categorize the financial accounts used across your restaurants.</p>
        </div>
        <x-button-add href="{{ route('coa.create') }}" text="Add Chart of Account" />
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->has('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            {{ $errors->first('error') }}
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.35-4.35"/>
                            </svg>
                        </button>
                        <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $headers = [
            'Code',
            'Account Name',
            'Type',
            'Stores',
            'Status',
            ['label' => 'Actions', 'align' => 'end']
        ];
    @endphp

    <x-table 
        :headers="$headers"
        emptyMessage="No accounts found with the current filters."
        emptyActionHref="{{ route('coa.create') }}"
        emptyActionText="Add First Account">
        @if($coas->count() > 0)
            @foreach($coas as $coa)
                <x-table-row>
                    <x-table-cell>
                        <strong>{{ $coa->account_code }}</strong>
                    </x-table-cell>
                    <x-table-cell>
                        {{ $coa->account_name }}
                        @if($coa->is_system_account)
                            <span class="badge bg-info ms-2">System</span>
                        @endif
                    </x-table-cell>
                    <x-table-cell>
                        <span class="badge bg-secondary">{{ $coa->account_type }}</span>
                    </x-table-cell>
                    <x-table-cell>
                        @if($coa->stores->isEmpty())
                            <span class="text-muted">All Stores</span>
                        @else
                            <span class="text-muted">{{ $coa->stores->pluck('store_info')->implode(', ') }}</span>
                        @endif
                    </x-table-cell>
                    <x-table-cell>
                        @if($coa->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </x-table-cell>
                    <x-table-cell align="end">
                        <div class="d-flex gap-1 justify-content-end">
                            <x-button-view href="{{ route('coa.show', $coa) }}" iconOnly="true" />
                            <x-button-edit href="{{ route('coa.edit', $coa) }}" iconOnly="true" />
                                <x-button-delete 
                                    action="{{ route('coa.destroy', $coa) }}" 
                                    iconOnly="true" 
                                confirmMessage="Are you sure you want to delete this account? This action cannot be undone." />
                        </div>
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>

    @if($coas->hasPages())
        <div class="mt-3">
            <x-pagination :paginator="$coas" />
        </div>
    @endif
</div>
@endsection


