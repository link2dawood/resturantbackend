@extends('layouts.tabler')

@section('title', 'Owner Details - ' . $owner->name)

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Owner Details</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">{{ $owner->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('owners.assign-stores.form', $owner) }}" class="btn btn-outline-primary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                Assign Stores
            </a>
            <a href="{{ route('owners.edit', $owner) }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                </svg>
                Edit Owner
            </a>
            <a href="{{ route('owners.index') }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Owners
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid var(--google-green, #34a853);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--google-green, #34a853)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22,4 12,14.01 9,11.01"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <!-- Owner Information -->
        <div class="col-md-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Basic Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Full Name</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->name }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Email</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->email }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">State</label>
                            <div>
                                @if($owner->state)
                                <span class="badge badge-outline text-blue">{{ $owner->state }}</span>
                                <div class="text-muted" style="font-size: 0.875rem;">{{ \App\Helpers\USStates::getStateName($owner->state) }}</div>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Last Online</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->last_online_human }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Contact Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Personal Phone</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->personal_phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Personal Email</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->personal_email ?? '—' }}</div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Home Address</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->home_address ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Information -->
            <div class="card mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Business Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Corporate EIN</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->corporate_ein ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Establishment Date</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">
                                {{ $owner->corporate_creation_date ? \Carbon\Carbon::parse($owner->corporate_creation_date)->format('M d, Y') : '—' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Corporate Phone</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->corporate_phone ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Corporate Email</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->corporate_email ?? '—' }}</div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Corporate Address</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $owner->corporate_address ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Owner Avatar & Status -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="avatar avatar-xl mb-3" style="background-image: url({{ $owner->avatar_url }})"></div>
                    <h3 style="font-family: 'Google Sans', sans-serif; font-weight: 500;">{{ $owner->name }}</h3>
                    <p class="text-muted">Owner ID: #{{ $owner->id }}</p>

                    <!-- Permissions -->
                    <div class="d-flex flex-wrap gap-1 justify-content-center mb-3">
                        <span class="badge bg-yellow">Owner</span>
                        @if($owner->hasPermission('manage_stores'))
                        <span class="badge bg-orange">Store Mgmt</span>
                        @endif
                        @if($owner->hasPermission('manage_managers'))
                        <span class="badge bg-green">Manager Mgmt</span>
                        @endif
                        @if($owner->hasPermission('create_reports'))
                        <span class="badge bg-blue">Reports</span>
                        @endif
                        @if($owner->hasPermission('manage_transaction_types'))
                        <span class="badge bg-purple">Transactions</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Assigned Stores -->
            <div class="card mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Assigned Stores</h3>
                </div>
                <div class="card-body">
                    @if($assignedStores->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($assignedStores as $store)
                            <div class="list-group-item border-0 px-0 d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <div style="font-weight: 500; font-size: 0.875rem; color: var(--google-grey-900, #202124);">{{ $store->store_info }}</div>
                                    <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368);">{{ $store->city }}, {{ $store->state }}</div>
                                </div>
                                <a href="{{ route('stores.show', $store) }}" class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--google-grey-400, #9aa0a6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                                </svg>
                            </div>
                            <p style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); margin-bottom: 1rem; font-size: 0.875rem;">No stores assigned</p>
                            <a href="{{ route('owners.assign-stores.form', $owner) }}" class="btn btn-sm btn-outline-primary">
                                Assign Stores
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('owners.assign-stores.form', $owner) }}" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                            </svg>
                            Manage Stores
                        </a>
                        <a href="{{ route('owners.edit', $owner) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                            </svg>
                            Edit Owner
                        </a>
                        <hr class="my-2">
                        <form action="{{ route('owners.destroy', $owner) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this owner? This action cannot be undone.')" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3,6 5,6 21,6"/>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                </svg>
                                Delete Owner
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection