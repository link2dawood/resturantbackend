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
        <a href="{{ route('transaction-types.create') }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Transaction Type
        </a>
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
                            <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Category</th>
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
                                <div style="font-weight: 500; font-size: 0.875rem;">{{ $type->name }}</div>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; color: #5f6368;">
                                <span class="badge bg-info text-white" style="font-size: 0.75rem;">{{ $type->parent ? $type->parent->name : 'None' }}</span>
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
                                <a href="{{ route('transaction-types.show', $type->id) }}" class="btn btn-sm btn-outline-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View Details">
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
        </div>
    </div>
</div>
@endsection
