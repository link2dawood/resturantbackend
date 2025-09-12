@extends('layouts.tabler')

@section('title', 'Owners')

@section('content')
<div class="container-xl mt-4">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Management</div>
                    <h2 class="page-title">Owners</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    @if(Auth::user()->hasPermission('manage_owners'))
                    <a href="{{ route('owners.create') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Add Owner
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body p-0">
                            @if($owners->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table">
                                    <thead>
                                        <tr>
                                            <th>Owner</th>
                                            <th>Contact</th>
                                            <th>State</th>
                                            <th>Last Login</th>
                                            <th>Permissions</th>
                                            <th>Business Info</th>
                                            @if(Auth::user()->hasPermission('manage_owners'))
                                            <th class="w-1">Actions</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($owners as $owner)
                                        <tr>
                                            <td>
                                                <div class="d-flex py-1 align-items-center">
                                                    <span class="avatar me-2" style="background-image: url({{ $owner->avatar_url }})"></span>
                                                    <div class="flex-fill">
                                                        <div class="font-weight-medium">{{ $owner->name }}</div>
                                                        <div class="text-muted">
                                                            <small>Owner ID: #{{ $owner->id }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="text-dark">{{ $owner->email }}</div>
                                                    @if($owner->personal_phone)
                                                    <div class="text-muted">
                                                        <small>{{ $owner->personal_phone }}</small>
                                                    </div>
                                                    @endif
                                                    @if($owner->corporate_email && $owner->corporate_email != $owner->email)
                                                    <div class="text-muted">
                                                        <small>Corporate: {{ $owner->corporate_email }}</small>
                                                    </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($owner->state)
                                                <div>
                                                    <span class="badge badge-outline text-blue">{{ $owner->state }}</span>
                                                    <div class="text-muted">
                                                        <small>{{ \App\Helpers\USStates::getStateName($owner->state) }}</small>
                                                    </div>
                                                </div>
                                                @else
                                                <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="text-muted">
                                                    {{ $owner->last_online_human }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
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
                                            </td>
                                            <td>
                                                <div>
                                                    @if($owner->corporate_ein)
                                                    <div class="text-dark">EIN: {{ $owner->corporate_ein }}</div>
                                                    @endif
                                                    @if($owner->corporate_creation_date)
                                                    <div class="text-muted">
                                                        <small>Est. {{ \Carbon\Carbon::parse($owner->corporate_creation_date)->format('Y') }}</small>
                                                    </div>
                                                    @endif
                                                    @if($owner->corporate_phone)
                                                    <div class="text-muted">
                                                        <small>{{ $owner->corporate_phone }}</small>
                                                    </div>
                                                    @endif
                                                </div>
                                            </td>
                                            @if(Auth::user()->hasPermission('manage_owners'))
                                            <td>
                                                <div class="btn-list">
                                                    <a href="{{ route('owners.edit', $owner->id) }}" class="btn btn-sm btn-outline-primary">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                        </svg>
                                                        Edit
                                                    </a>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <circle cx="12" cy="12" r="1"/>
                                                                <circle cx="12" cy="5" r="1"/>
                                                                <circle cx="12" cy="19" r="1"/>
                                                            </svg>
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <a href="{{ route('owners.assign-stores', $owner->id) }}" class="dropdown-item">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                                                                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                                                                </svg>
                                                                Assign Stores
                                                            </a>
                                                            <div class="dropdown-divider"></div>
                                                            <form action="{{ route('owners.destroy', $owner->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this owner? This action cannot be undone.')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
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
                                            </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="empty">
                                <div class="empty-img">
                                    <img src="https://tabler.io/static/illustrations/undraw_building_blocks_n0nc.svg" height="128" alt="No owners">
                                </div>
                                <p class="empty-title">No owners found</p>
                                <p class="empty-subtitle text-muted">
                                    There are no owners in the system yet.
                                </p>
                                @if(Auth::user()->hasPermission('manage_owners'))
                                <div class="empty-action">
                                    <a href="{{ route('owners.create') }}" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="12" y1="5" x2="12" y2="19"/>
                                            <line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                        Add your first owner
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
