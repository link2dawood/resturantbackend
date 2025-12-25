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

    <div class="card">
        <div class="card-header border-0">
            <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">All Transaction Types</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" style="font-size: 0.875rem;">
                    <thead style="background-color: #f8f9fa; border-bottom: 2px solid #e0e0e0;">
                        <tr>
                            <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">#</th>
                            <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Category Transaction Type</th>
                            <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Description Name</th>
                            <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Default COA</th>
                            <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactionTypes as $type)
                        <tr style="border-bottom: 1px solid #f1f3f4;">
                            <td style="padding: 1rem; vertical-align: middle; color: #3c4043;">
                                <span class="badge bg-light text-dark" style="font-size: 0.75rem;">{{ $type->id }}</span>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; color: #202124;">
                                @if($type->p_id === null)
                                    <div style="font-weight: 500; font-size: 0.875rem;">— (Category)</div>
                                @else
                                    <form action="{{ route('transaction-types.update-category', $type->id) }}" method="POST" class="d-inline" style="display: inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <select name="p_id" class="form-control form-control-sm" style="min-width: 150px; display: inline-block;" onchange="this.form.submit()">
                                            <option value="">None</option>
                                            @foreach($parentTransactionTypes as $parent)
                                                <option value="{{ $parent->id }}" {{ $type->p_id == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; color: #5f6368;">
                                <span class="badge bg-info text-white" style="font-size: 0.75rem;">{{ $type->name }}</span>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; color: #5f6368;">
                                @if($type->defaultCoa)
                                    <span class="badge bg-success" style="font-size: 0.75rem;">
                                        {{ $type->defaultCoa->account_code }} - {{ $type->defaultCoa->account_name }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size: 0.813rem;">Not assigned</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: center;">
                                <x-button-group-actions
                                    viewHref="{{ route('transaction-types.show', $type->id) }}"
                                    editHref="{{ route('transaction-types.edit', $type->id) }}"
                                    showDelete="false"
                                />
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
