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
        <x-button-add href="{{ route('managers.create') }}" text="Add Manager" />
        @endif
    </div>
    @php
        $headers = [
            'Manager',
            'Contact',
            'State',
            'Last Login',
            'Permissions',
            'Assigned Stores',
            ['label' => 'Actions', 'align' => 'center']
        ];
    @endphp

    <x-table 
        :headers="$headers"
        emptyMessage="No managers found"
        emptyDescription="There are no managers in the system yet."
        emptyActionHref="{{ Auth::user()->hasPermission('manage_managers') ? route('managers.create') : null }}"
        emptyActionText="{{ Auth::user()->hasPermission('manage_managers') ? 'Add your first manager' : null }}">
        @if($managers->count() > 0)
            @foreach ($managers as $manager)
                <x-table-row>
                    <x-table-cell>
                        <div class="d-flex py-1 align-items-center">
                            <span class="avatar me-2" style="background-image: url({{ $manager->avatar_url }})"></span>
                            <div class="flex-fill">
                                <div class="font-weight-medium">{{ $manager->name }}</div>
                                <div class="text-muted">
                                    <small>{{ $manager->username ? 'N/A' : ($manager->username ?: 'No username') }}</small>
                                </div>
                            </div>
                        </div>
                    </x-table-cell>
                    <x-table-cell>
                        <div>
                            <div class="text-dark">{{ $manager->email }}</div>
                            @if($manager->personal_phone)
                            <div class="text-muted">
                                <small>{{ $manager->personal_phone }}</small>
                            </div>
                            @endif
                        </div>
                    </x-table-cell>
                    <x-table-cell>
                        @if($manager->state)
                        <span class="badge badge-outline text-blue">{{ $manager->state }}</span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </x-table-cell>
                    <x-table-cell>
                        <div class="text-muted">
                            {{ $manager->last_online_human }}
                        </div>
                    </x-table-cell>
                    <x-table-cell>
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
                    </x-table-cell>
                    <x-table-cell>
                        @if ($manager->store)
                        <span class="badge badge-outline text-green">{{ $manager->store->store_info }}</span>
                        @else
                        <span class="text-muted">No store assigned</span>
                        @endif
                    </x-table-cell>
                    <x-table-cell align="center">
                        <div class="d-flex gap-1 justify-content-center">
                            <x-button-view href="{{ route('managers.show', $manager->id) }}" iconOnly="true" />
                            
                            @if(Auth::user()->hasPermission('manage_managers'))
                                <x-button-edit href="{{ route('managers.edit', $manager->id) }}" iconOnly="true" />
                                
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
                                
                                <x-button-delete 
                                    action="{{ route('managers.destroy', $manager->id) }}" 
                                    iconOnly="true" 
                                    confirmMessage="Are you sure you want to delete this manager? This action cannot be undone." />
                            @endif
                        </div>
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>
</div>
@endsection
