@extends('layouts.tabler')

@section('title', 'Manager Details')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('managers.index') }}">Managers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $manager->name }}</li>
                </ol>
            </nav>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Manager Details</h1>
        </div>
        <div class="d-flex gap-2">
            @if(Auth::user()->hasPermission('manage_managers'))
            <a href="{{ route('managers.edit', $manager) }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit Manager
            </a>
            @endif
            <a href="{{ route('managers.index') }}" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Managers
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Manager Information Card -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Manager Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <img src="{{ $manager->avatar_url }}" alt="{{ $manager->name }}" class="avatar avatar-xl mb-3">
                            <h4 class="mb-1">{{ $manager->name }}</h4>
                            <span class="badge bg-green">{{ $manager->role->label() }}</span>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <strong class="text-muted">Email</strong>
                                    <div>{{ $manager->email }}</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <strong class="text-muted">Username</strong>
                                    <div>{{ $manager->username ?: 'N/A' }}</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <strong class="text-muted">Personal Phone</strong>
                                    <div>{{ $manager->personal_phone ?: 'N/A' }}</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <strong class="text-muted">State</strong>
                                    <div>{{ $manager->state ?: 'N/A' }}</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <strong class="text-muted">Created</strong>
                                    <div>{{ $manager->created_at->format('M j, Y') }}</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <strong class="text-muted">Last Online</strong>
                                    <div>{{ $manager->last_online_human }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Contact Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Personal Information</h5>
                            <div class="mb-2">
                                <strong class="text-muted">Email:</strong> {{ $manager->personal_email ?: $manager->email }}
                            </div>
                            <div class="mb-2">
                                <strong class="text-muted">Phone:</strong> {{ $manager->personal_phone ?: 'N/A' }}
                            </div>
                            <div class="mb-2">
                                <strong class="text-muted">Address:</strong> {{ $manager->home_address ?: 'N/A' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Corporate Information</h5>
                            <div class="mb-2">
                                <strong class="text-muted">Email:</strong> {{ $manager->corporate_email ?: 'N/A' }}
                            </div>
                            <div class="mb-2">
                                <strong class="text-muted">Phone:</strong> {{ $manager->corporate_phone ?: 'N/A' }}
                            </div>
                            <div class="mb-2">
                                <strong class="text-muted">Address:</strong> {{ $manager->corporate_address ?: 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Assigned Store -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Assigned Store</h3>
                </div>
                <div class="card-body">
                    @if($manager->store)
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <span class="avatar" style="background-color: var(--google-blue, #4285f4); color: white;">
                                    {{ strtoupper(substr($manager->store->store_info, 0, 2)) }}
                                </span>
                            </div>
                            <div class="flex-fill">
                                <div class="font-weight-medium">{{ $manager->store->store_info }}</div>
                                <div class="text-muted">
                                    <small>{{ $manager->store->city }}, {{ $manager->store->state }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="small text-muted">
                            <div><strong>Contact:</strong> {{ $manager->store->contact_name }}</div>
                            <div><strong>Phone:</strong> {{ $manager->store->phone }}</div>
                            <div><strong>Address:</strong> {{ $manager->store->address }}</div>
                        </div>
                        @if(Auth::user()->hasPermission('manage_managers'))
                        <div class="mt-3">
                            <a href="{{ route('managers.assign-stores.form', $manager) }}" class="btn btn-sm btn-outline-primary">
                                Reassign Store
                            </a>
                        </div>
                        @endif
                    @else
                        <div class="text-center text-muted py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="mb-3 opacity-50">
                                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                                <polyline points="9,22 9,12 15,12 15,22"/>
                            </svg>
                            <div class="mb-3">No store assigned</div>
                            @if(Auth::user()->hasPermission('manage_managers'))
                            <a href="{{ route('managers.assign-stores.form', $manager) }}" class="btn btn-sm btn-primary">
                                Assign Store
                            </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Permissions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Permissions</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-green">Manager Access</span>
                        @if($manager->hasPermission('create_reports'))
                            <span class="badge bg-blue">Create Reports</span>
                        @endif
                        @if($manager->hasPermission('view_daily_reports'))
                            <span class="badge bg-purple">View Reports</span>
                        @endif
                        @if($manager->hasPermission('view_assigned_stores'))
                            <span class="badge bg-orange">Store Access</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection