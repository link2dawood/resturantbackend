@extends('layouts.tabler')

@section('title', 'Store Details - ' . $store->store_info)

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Store Details</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">{{ $store->store_info }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('stores.assign-owner.form', $store) }}" class="btn btn-outline-primary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="23" y1="11" x2="17" y2="11"/>
                </svg>
                Assign Owners
            </a>
            <a href="{{ route('stores.edit', $store) }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                </svg>
                Edit Store
            </a>
            <a href="{{ route('stores.index') }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Stores
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
        <!-- Store Information -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Store Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Store Name</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $store->store_info }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Contact Name</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $store->contact_name }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Phone</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $store->phone }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Store ID</label>
                            <div><span class="badge bg-light text-dark" style="font-size: 0.875rem;">#{{ $store->id }}</span></div>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Address</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">
                                {{ $store->address }}<br>
                                {{ $store->city }}, {{ $store->state }} {{ $store->zip }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Sales Tax Rate</label>
                            <div><span class="badge bg-info text-white" style="font-size: 0.875rem;">{{ $store->sales_tax_rate }}%</span></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Medicare Tax Rate</label>
                            <div><span class="badge bg-warning text-dark" style="font-size: 0.875rem;">{{ $store->medicare_tax_rate ?? 'N/A' }}%</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Owners -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Assigned Owners</h3>
                </div>
                <div class="card-body">
                    @if($store->owners->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($store->owners as $owner)
                            <div class="list-group-item border-0 px-0 d-flex align-items-center">
                                <div class="avatar avatar-sm me-3" style="background-color: var(--google-blue-100, #d2e3fc); color: var(--google-blue, #4285f4); font-weight: 500;">
                                    {{ substr($owner->name, 0, 1) }}
                                </div>
                                <div class="flex-grow-1">
                                    <div style="font-weight: 500; font-size: 0.875rem; color: var(--google-grey-900, #202124);">{{ $owner->name }}</div>
                                    <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368);">{{ $owner->email }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--google-grey-400, #9aa0a6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                    <circle cx="8.5" cy="7" r="4"/>
                                    <line x1="20" y1="8" x2="20" y2="14"/>
                                    <line x1="23" y1="11" x2="17" y2="11"/>
                                </svg>
                            </div>
                            <p style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); margin-bottom: 1rem; font-size: 0.875rem;">No owners assigned</p>
                            <a href="{{ route('stores.assign-owner.form', $store) }}" class="btn btn-sm btn-outline-primary">
                                Assign Owners
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('stores.assign-owner.form', $store) }}" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="8.5" cy="7" r="4"/>
                                <line x1="20" y1="8" x2="20" y2="14"/>
                                <line x1="23" y1="11" x2="17" y2="11"/>
                            </svg>
                            Manage Owners
                        </a>
                        <a href="{{ route('stores.edit', $store) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                            </svg>
                            Edit Store
                        </a>
                        <hr class="my-2">
                        <form action="{{ route('stores.destroy', $store) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this store? This action cannot be undone.')" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3,6 5,6 21,6"/>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                </svg>
                                Delete Store
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection