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
            'Name',
            'Email',
            'Assigned Stores',
            'Last Online',
            'Member Since',
            ['label' => '', 'align' => 'center']
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
                        <div style="font-weight: 500; font-size: 0.875rem;">{{ $owner->name }}</div>
                    </x-table-cell>
                    <x-table-cell>
                        <div style="font-size: 0.875rem;">{{ $owner->email }}</div>
                    </x-table-cell>
                    <x-table-cell>
                        @if($owner->ownedStores->count() > 0)
                            <div style="font-size: 0.875rem;">
                                @foreach($owner->ownedStores as $store)
                                    <div>{{ $store->store_info }}</div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted" style="font-size: 0.875rem;">No stores assigned</span>
                        @endif
                    </x-table-cell>
                    <x-table-cell>
                        <div style="font-size: 0.875rem; color: var(--google-grey-600, #5f6368);">
                            {{ $owner->last_online_human }}
                        </div>
                    </x-table-cell>
                    <x-table-cell>
                        <div style="font-size: 0.875rem; color: var(--google-grey-600, #5f6368);">
                            {{ $owner->created_at->format('M d, Y') }}
                        </div>
                    </x-table-cell>
                    <x-table-cell align="center">
                        <a href="{{ route('owners.show', $owner->id) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </a>
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>
</div>
@endsection
