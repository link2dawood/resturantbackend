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
        <x-button-add href="{{ route('owners.create') }}" text="Add Owner" />
        @endif
    </div>
    @php
        $headers = [
            'Owner',
            'Contact',
            'State',
            'Last Login',
            'Permissions',
            'Business Info',
            ['label' => 'Actions', 'align' => 'center']
        ];
    @endphp

    <x-table 
        :headers="$headers"
        emptyMessage="No owners found"
        emptyDescription="There are no owners in the system yet."
        emptyActionHref="{{ Auth::user()->hasPermission('manage_owners') ? route('owners.create') : null }}"
        emptyActionText="{{ Auth::user()->hasPermission('manage_owners') ? 'Add your first owner' : null }}">
        @if($owners->count() > 0)
            @foreach ($owners as $owner)
                <x-table-row>
                    <x-table-cell>
                        <div class="d-flex py-1 align-items-center">
                            <span class="avatar me-2" style="background-image: url({{ $owner->avatar_url }})"></span>
                            <div class="flex-fill">
                                <div class="font-weight-medium">{{ $owner->name }}</div>
                                <div class="text-muted">
                                    <small>Owner ID: #{{ $owner->id }}</small>
                                </div>
                            </div>
                        </div>
                    </x-table-cell>
                    <x-table-cell>
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
                    </x-table-cell>
                    <x-table-cell>
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
                    </x-table-cell>
                    <x-table-cell>
                        <div class="text-muted">
                            {{ $owner->last_online_human }}
                        </div>
                    </x-table-cell>
                    <x-table-cell>
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
                    </x-table-cell>
                    <x-table-cell>
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
                    </x-table-cell>
                    <x-table-cell align="center">
                        <x-button-view href="{{ route('owners.show', $owner->id) }}" iconOnly="true" />
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>
</div>
@endsection
