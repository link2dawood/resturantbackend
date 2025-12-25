@extends('layouts.tabler')

@section('title', 'Stores')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Stores</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage your restaurant locations</p>
        </div>
        <x-button-add href="{{ route('stores.create') }}" text="Create Store" />
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

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid var(--google-red, #ea4335);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--google-red, #ea4335)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    @php
        $headers = ['#'];
        if(Auth::user()->hasPermission('manage_owners')) {
            $headers[] = 'Owner';
        }
        $headers = array_merge($headers, [
            'Store Information',
            'Type',
            'Address',
            'Sales Tax',
            'Medicare Tax',
            ['label' => 'Actions', 'align' => 'center']
        ]);
    @endphp

    <x-table 
        :headers="$headers"
        cardTitle="All Stores"
        emptyMessage="No stores found"
        emptyDescription="Get started by creating your first restaurant location."
        emptyActionHref="{{ route('stores.create') }}"
        emptyActionText="Create Your First Store">
        @if($stores->count() > 0)
            @foreach ($stores as $store)
                @php
                    $owner = App\Models\User::find($store->created_by);
                @endphp
                <x-table-row>
                    <x-table-cell>
                        <span class="badge bg-light text-dark" style="font-size: 0.75rem;">{{ $store->id }}</span>
                    </x-table-cell>
                    @if(Auth::user()->hasPermission('manage_owners'))
                    <x-table-cell>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-xs me-2" style="background-color: var(--google-blue-100, #d2e3fc); color: var(--google-blue, #4285f4);">
                                {{ substr($owner->name ?? 'U', 0, 1) }}
                            </div>
                            <span style="font-weight: 500;">{{ $owner->name ?? 'Unknown' }}</span>
                        </div>
                    </x-table-cell>
                    @endif
                    <x-table-cell>
                        <div style="font-weight: 500; font-size: 0.875rem;">{{ $store->store_info }}</div>
                    </x-table-cell>
                    <x-table-cell>
                        @if($store->store_type === 'corporate')
                            <span class="badge bg-primary" style="font-size: 0.75rem;">Corporate Store</span>
                            <small class="d-block text-muted mt-1" style="font-size: 0.7rem;">Franchisor</small>
                        @else
                            <span class="badge bg-success" style="font-size: 0.75rem;">Franchisee Location</span>
                            <small class="d-block text-muted mt-1" style="font-size: 0.7rem;">Owner</small>
                        @endif
                    </x-table-cell>
                    <x-table-cell>
                        <div style="font-size: 0.875rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $store->address }}">{{ $store->address }}</div>
                    </x-table-cell>
                    <x-table-cell>
                        <span class="badge bg-info text-white" style="font-size: 0.75rem;">{{ $store->sales_tax_rate }}%</span>
                    </x-table-cell>
                    <x-table-cell>
                        <span class="badge bg-warning text-dark" style="font-size: 0.75rem;">{{ $store->medicare_tax_rate }}%</span>
                    </x-table-cell>
                    <x-table-cell align="center">
                        <x-button-view href="{{ route('stores.show', $store->id) }}" iconOnly="true" />
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>
</div>
@endsection
