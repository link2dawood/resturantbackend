@extends('layouts.tabler')

@section('title', 'Managers')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Managers</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage store managers and their access permissions</p>
        </div>
        @if(Auth::user()->hasPermission('manage_managers'))
        <a href="{{ route('managers.create') }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Manager
        </a>
        @endif
    </div>
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body p-0">
                            @if($managers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Manager</th>
                                            <th>Contact</th>
                                            <th>State</th>
                                            <th>Last Login</th>
                                            <th>Permissions</th>
                                            <th>Assigned Stores</th>
                                            <th class="w-1">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($managers as $manager)
                                        <tr>
                                            <td>
                                                <div class="d-flex py-1 align-items-center">
                                                    <span class="avatar me-2" style="background-image: url({{ $manager->avatar_url }})"></span>
                                                    <div class="flex-fill">
                                                        <div class="font-weight-medium">{{ $manager->name }}</div>
                                                        <div class="text-muted">
                                                            <small>{{ $manager->username ? 'N/A' : ($manager->username ?: 'No username') }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="text-dark">{{ $manager->email }}</div>
                                                    @if($manager->personal_phone)
                                                    <div class="text-muted">
                                                        <small>{{ $manager->personal_phone }}</small>
                                                    </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($manager->state)
                                                <span class="badge badge-outline text-blue">{{ $manager->state }}</span>
                                                @else
                                                <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="text-muted">
                                                    {{ $manager->last_online_human }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <span class="badge bg-green">Manager</span>
                                                    @if($manager->hasPermission('create_reports'))
                                                    <span class="badge bg-blue">Reports</span>
                                                    @endif
                                                    @if($manager->hasPermission('manage_transaction_types'))
                                                    <span class="badge bg-purple">Transactions</span>
                                                    @endif
                                                    @if($manager->hasPermission('manage_stores'))
                                                    <span class="badge bg-orange">Store Mgmt</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if ($manager->stores->isNotEmpty())
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach ($manager->stores as $store)
                                                    <span class="badge badge-outline text-green">{{ $store->name }}</span>
                                                    @endforeach
                                                </div>
                                                @else
                                                <span class="text-muted">No stores assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('managers.show', $manager->id) }}" class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View Details">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                        <circle cx="12" cy="12" r="3"/>
                                                    </svg>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="empty">
                                <div class="empty-img">
                                    <img src="https://tabler.io/static/illustrations/undraw_printing_invoices_5r4r.svg" height="128" alt="No managers">
                                </div>
                                <p class="empty-title">No managers found</p>
                                <p class="empty-subtitle text-muted">
                                    There are no managers in the system yet.
                                </p>
                                @if(Auth::user()->hasPermission('manage_managers'))
                                <div class="empty-action">
                                    <a href="{{ route('managers.create') }}" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                        Add your first manager
                                    </a>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
