@extends('layouts.tabler')

@section('title', 'Assign Owners - ' . $store->store_info)

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Assign Owners</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">{{ $store->store_info }}</p>
        </div>
        <a href="{{ route('stores.show', $store) }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to Store
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid var(--google-red, #ea4335);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--google-red, #ea4335)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <div>
                <strong>There were some problems with your input:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row">
        <!-- Assignment Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Select Owners</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('stores.assign-owner', $store) }}" method="POST">
                        @csrf

                        @if($owners->count() > 0)
                            <div class="mb-4">
                                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: var(--google-grey-700, #3c4043);">
                                    Select Owner
                                    <span class="text-muted" style="font-weight: 400;">(One store can have only one owner)</span>
                                </label>

                                <div class="row">
                                    @foreach($owners as $owner)
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check" style="padding: 1rem; border: 1px solid var(--google-grey-300, #dadce0); border-radius: 0.75rem; transition: all 0.2s ease;"
                                             onmouseover="this.style.borderColor='var(--google-blue, #4285f4)'; this.style.backgroundColor='var(--google-blue-50, #e8f0fe)'"
                                             onmouseout="if(!this.querySelector('input').checked) { this.style.borderColor='var(--google-grey-300, #dadce0)'; this.style.backgroundColor='transparent' }">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                value="{{ $owner->id }}"
                                                id="owner{{ $owner->id }}"
                                                name="owner_id"
                                                {{ $assignedOwner && $assignedOwner->id === $owner->id ? 'checked' : '' }}
                                                onchange="updateCardStyle(this)"
                                                style="border: 2px solid var(--google-grey-400, #9aa0a6);">
                                            <label class="form-check-label d-flex align-items-center w-100" for="owner{{ $owner->id }}" style="cursor: pointer;">
                                                <div class="avatar avatar-sm me-3" style="background-color: var(--google-blue-100, #d2e3fc); color: var(--google-blue, #4285f4); font-weight: 500;">
                                                    {{ substr($owner->name, 0, 1) }}
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div style="font-weight: 500; font-size: 0.875rem; color: var(--google-grey-900, #202124);">{{ $owner->name }}</div>
                                                    <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368);">{{ $owner->email }}</div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22,4 12,14.01 9,11.01"/>
                                    </svg>
                                    Update Assignments
                                </button>
                                <a href="{{ route('stores.show', $store) }}" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--google-grey-400, #9aa0a6)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                        <circle cx="8.5" cy="7" r="4"/>
                                        <line x1="20" y1="8" x2="20" y2="14"/>
                                        <line x1="23" y1="11" x2="17" y2="11"/>
                                    </svg>
                                </div>
                                <h3 style="font-family: 'Google Sans', sans-serif; font-size: 1.25rem; font-weight: 400; color: var(--google-grey-700, #3c4043); margin-bottom: 0.5rem;">No owners available</h3>
                                <p style="font-family: 'Google Sans', sans-serif; color: var(--google-grey-600, #5f6368); margin-bottom: 1.5rem;">You need to create owners before you can assign them to stores.</p>
                                <a href="{{ route('owners.create') }}" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                    Create Owner
                                </a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Store Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Store Details</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Store Name</label>
                        <div style="font-weight: 500; font-size: 1rem; color: var(--google-grey-900, #202124);">{{ $store->store_info }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Contact</label>
                        <div style="font-weight: 500; font-size: 0.875rem; color: var(--google-grey-900, #202124);">{{ $store->contact_name }}</div>
                        <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368);">{{ $store->phone }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted" style="font-size: 0.813rem; font-weight: 500; letter-spacing: 0.3px;">Address</label>
                        <div style="font-size: 0.875rem; color: var(--google-grey-900, #202124);">
                            {{ $store->address }}<br>
                            {{ $store->city }}, {{ $store->state }} {{ $store->zip }}
                        </div>
                    </div>
                </div>
            </div>

            @if($assignedOwner)
            <div class="card mt-4">
                <div class="card-header border-0 pb-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Currently Assigned</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item border-0 px-0 d-flex align-items-center">
                            <div class="avatar avatar-sm me-3" style="background-color: var(--google-green-100, #c8e6c9); color: var(--google-green, #34a853); font-weight: 500;">
                                {{ substr($assignedOwner->name, 0, 1) }}
                            </div>
                            <div class="flex-grow-1">
                                <div style="font-weight: 500; font-size: 0.875rem; color: var(--google-grey-900, #202124);">{{ $assignedOwner->name }}</div>
                                <div style="font-size: 0.813rem; color: var(--google-grey-600, #5f6368);">{{ $assignedOwner->email }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function updateCardStyle(radio) {
    // Remove styling from all cards
    document.querySelectorAll('.form-check').forEach(function(card) {
        card.style.borderColor = 'var(--google-grey-300, #dadce0)';
        card.style.backgroundColor = 'transparent';
    });
    
    // Apply styling to selected card
    const card = radio.closest('.form-check');
    if (radio.checked) {
        card.style.borderColor = 'var(--google-blue, #4285f4)';
        card.style.backgroundColor = 'var(--google-blue-50, #e8f0fe)';
    }
}

// Initialize card styles for pre-selected items
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="radio"]:checked').forEach(function(radio) {
        updateCardStyle(radio);
    });
});
</script>
@endsection