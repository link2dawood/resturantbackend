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
                                                @if ($manager->store)
                                                <span class="badge badge-outline text-green">{{ $manager->store->store_info }}</span>
                                                @else
                                                <span class="text-muted">No store assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('managers.show', $manager->id) }}" class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View Details">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                            <circle cx="12" cy="12" r="3"/>
                                                        </svg>
                                                    </a>

                                                    @if(Auth::user()->hasPermission('manage_managers'))
                                                        <a href="{{ route('managers.edit', $manager->id) }}" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Edit Manager">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                            </svg>
                                                        </a>

                                                        @if(Auth::user()->isAdmin())
                                                            <form method="POST" action="{{ route('impersonate.start', $manager) }}" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-outline-info d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Login as {{ $manager->name }}">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                                                        <polyline points="10,17 15,12 10,7"/>
                                                                        <line x1="15" y1="12" x2="3" y2="12"/>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form method="POST" action="{{ route('managers.destroy', $manager->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this manager? This action cannot be undone.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Delete Manager">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="3,6 5,6 21,6"/>
                                                                    <path d="M19,6V20a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6M8,6V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2V6"/>
                                                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
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
