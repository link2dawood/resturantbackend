@extends('layouts.tabler')

@section('title', 'Transaction Types')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Transaction Types</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage transaction categories and types for daily reports</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('transaction-types.auto-assign-categories') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary d-flex align-items-center" style="gap: 0.5rem;" onclick="return confirm('This will automatically assign categories to all transaction types without categories based on their description names. Continue?')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    Auto-Assign Categories
                </button>
            </form>
        <x-button-add href="{{ route('transaction-types.create') }}" text="Add Transaction Type" />
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid #34a853;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#34a853" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22,4 12,14.01 9,11.01"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <x-table 
        :headers="['#', 'Description', 'Category type', 'Default COA', ['label' => 'Actions', 'align' => 'center']]"
        cardTitle="All Transaction Types"
        emptyMessage="No transaction types found"
        emptyDescription="Get started by creating your first transaction type."
        emptyActionHref="{{ route('transaction-types.create') }}"
        emptyActionText="Create Transaction Type">
        @if($transactionTypes->count() > 0)
            @foreach($transactionTypes as $type)
                <x-table-row>
                    <x-table-cell>
                        <span class="badge bg-light text-dark" style="font-size: 0.75rem;">{{ $type->id }}</span>
                    </x-table-cell>
                    <x-table-cell>
                        <span class="badge bg-info text-white" style="font-size: 0.75rem;">{{ $type->name }}</span>
                    </x-table-cell>
                    <x-table-cell>
                        @php
                            $isCategory = $type->p_id === null && $type->children()->count() > 0;
                        @endphp
                        {{-- Show select box for all items (assigned, unassigned, and categories) --}}
                        <form action="{{ route('transaction-types.update-category', $type->id) }}" method="POST" class="d-inline" style="display: inline-block;">
                            @csrf
                            @method('PATCH')
                            <select name="p_id" class="form-control form-control-sm" style="min-width: 150px; display: inline-block;" onchange="this.form.submit()">
                                <option value="">None</option>
                                @foreach($parentTransactionTypes as $parent)
                                    @if($parent->id != $type->id)
                                        <option value="{{ $parent->id }}" {{ $type->p_id == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if($isCategory)
                                <small class="d-block text-muted mt-1" style="font-size: 0.7rem;">(Category - has children)</small>
                            @endif
                        </form>
                    </x-table-cell>
                    <x-table-cell>
                        @if($type->defaultCoa)
                            <span class="badge bg-success" style="font-size: 0.75rem;">
                                {{ $type->defaultCoa->account_code }} - {{ $type->defaultCoa->account_name }}
                            </span>
                        @else
                            <span class="text-muted" style="font-size: 0.813rem;">Not assigned</span>
                        @endif
                    </x-table-cell>
                    <x-table-cell align="center">
                        <x-button-group-actions
                            viewHref="{{ route('transaction-types.show', $type->id) }}"
                            editHref="{{ route('transaction-types.edit', $type->id) }}"
                            showDelete="false"
                        />
                    </x-table-cell>
                </x-table-row>
            @endforeach
        @endif
    </x-table>
</div>
@endsection
