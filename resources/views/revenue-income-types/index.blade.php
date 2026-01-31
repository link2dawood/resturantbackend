@extends('layouts.tabler')

@section('title', 'Revenue Income Types')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Revenue Income Types</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage revenue income types for daily reports</p>
        </div>
        <x-button-add href="{{ route('revenue-income-types.create') }}" text="Add Revenue Type" />
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

    @php
        $headers = [
            'Name',
            'Description',
            'COA',
            'Sort Order',
            'Status',
            ['label' => 'Actions', 'align' => 'end']
        ];
    @endphp

    <x-table 
        :headers="$headers"
        emptyMessage="No revenue income types found."
        emptyActionHref="{{ route('revenue-income-types.create') }}"
        emptyActionText="Create your first one!">
        @if($revenueIncomeTypes->count() > 0)
            @foreach($revenueIncomeTypes as $type)
                <x-table-row>
                    <x-table-cell>
                        <strong>{{ $type->name }}</strong>
                    </x-table-cell>
                    <x-table-cell>
                        <div class="text-muted">{{ $type->description ?? 'No description' }}</div>
                    </x-table-cell>
                    <x-table-cell>
                        <span class="badge badge-outline text-{{ @$type->defaultCoa->account_name == 'cash' ? 'success' : (@$type->defaultCoa->account_name== 'online' ? 'info' : 'secondary') }}">
                            {{ @$type->defaultCoa->account_name }}
                        </span>
                    </x-table-cell>
                    <x-table-cell>
                        <span class="text-muted">{{ $type->sort_order }}</span>
                    </x-table-cell>
                    <x-table-cell>
                        @if($type->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </x-table-cell>
                    <x-table-cell align="end">
                        <div class="d-flex gap-1 justify-content-end">
                        <x-button-view href="{{ route('revenue-income-types.show', $type) }}" iconOnly="true" />
                            <x-button-edit href="{{ route('revenue-income-types.edit', $type) }}" iconOnly="true" />
                            <x-button-delete 
                                action="{{ route('revenue-income-types.destroy', $type) }}" 
                                iconOnly="true" 
                                confirmMessage="Are you sure you want to delete this revenue income type? This action cannot be undone." />
                        </div>
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>

    @if($revenueIncomeTypes->hasPages())
        <div class="mt-3">
            <x-pagination :paginator="$revenueIncomeTypes" />
        </div>
    @endif
</div>
@endsection