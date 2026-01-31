@extends('layouts.tabler')

@section('title', 'Transaction Type Details - ' . $transactionType->name)

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Transaction Type Details</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">{{ $transactionType->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('transaction-types.edit', $transactionType) }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                </svg>
                Edit Type
            </a>
            <a href="{{ route('transaction-types.index') }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Types
            </a>
        </div>
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

    <div class="row">
        <!-- Transaction Type Information -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Transaction Type Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Description Name</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $transactionType->name }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Type ID</label>
                            <div><span class="badge bg-light text-dark" style="font-size: 0.875rem;">#{{ $transactionType->id }}</span></div>
                        </div>
                        <!-- <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Category Transaction Type</label>
                            <div>
                                @if($transactionType->parent)
                                <span class="badge bg-info text-white" style="font-size: 0.875rem;">{{ $transactionType->parent->name }}</span>
                                @else
                                <span class="badge bg-secondary text-white" style="font-size: 0.875rem;">Root Category</span>
                                @endif
                            </div>
                        </div> -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Created Date</label>
                            <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">
                                {{ $transactionType->created_at ? $transactionType->created_at->format('M d, Y') : '—' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Default COA Category</label>
                            <div>
                                @if($transactionType->defaultCoa)
                                    <span class="badge bg-success" style="font-size: 0.875rem;">
                                        {{ $transactionType->defaultCoa->account_code }} - {{ $transactionType->defaultCoa->account_name }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary" style="font-size: 0.875rem;">Not assigned</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Child Categories -->
            @if($transactionType->children->count() > 0)
            <div class="card mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Child Categories</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($transactionType->children as $child)
                        <div class="list-group-item border-0 px-0 d-flex align-items-center justify-content-between">
                            <div>
                                <div style="font-weight: 500; font-size: 0.875rem; color: var(--google-grey-900, #202124);">{{ $child->name }}</div>
                                <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368);">ID: #{{ $child->id }}</div>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="{{ route('transaction-types.show', $child) }}" class="btn btn-sm btn-outline-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <a href="{{ route('transaction-types.edit', $child) }}" class="btn btn-sm btn-outline-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Assigned Stores section removed until pivot table is created -->
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Quick Info -->
            <div class="card mb-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Quick Info</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <div class="d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: var(--google-blue-100, #d2e3fc); border-radius: 50%;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--google-blue, #4285f4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="3"/>
                                    <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div style="font-weight: 500; font-size: 0.875rem; color: var(--google-grey-900, #202124);">{{ $transactionType->name }}</div>
                            <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368);">Transaction Type</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368); margin-bottom: 0.25rem;">Category Type</div>
                        <div>
                            @if($transactionType->parent)
                            <span class="badge bg-info">Sub-category</span>
                            @else
                            <span class="badge bg-primary">Root Category</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368); margin-bottom: 0.25rem;">Child Categories</div>
                        <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $transactionType->children->count() }}</div>
                    </div>

                    <!-- Assigned stores count removed until pivot table is created -->
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('transaction-types.edit', $transactionType) }}" class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                            </svg>
                            Edit Type
                        </a>
                        <a href="{{ route('transaction-types.create') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Create New Type
                        </a>
                        <hr class="my-2">
                        <form action="{{ route('transaction-types.destroy', $transactionType) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this transaction type? This action cannot be undone.')" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm w-100 d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3,6 5,6 21,6"/>
                                    <path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    <line x1="10" y1="11" x2="10" y2="17"/>
                                    <line x1="14" y1="11" x2="14" y2="17"/>
                                </svg>
                                Delete Type
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection