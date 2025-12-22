@extends('layouts.tabler')

@section('title', 'Owners')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Owners</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage restaurant owners and their permissions</p>
        </div>
        @if(Auth::user()->isAdmin())
        <a href="{{ route('owners.create') }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Owner
        </a>
        @endif
    </div>
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
                                            <th class="w-1">Actions</th>
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
                                            <td>
                                                <a href="{{ route('owners.show', $owner->id) }}" class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View Details">
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
