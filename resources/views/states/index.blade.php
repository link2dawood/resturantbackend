@extends('layouts.tabler')

@section('title', 'All US States')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">US States</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Complete list of all {{ $states->count() }} US states and territories</p>
        </div>
        <a href="{{ route('home') }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                <polyline points="9,22 9,12 15,12 15,22"/>
            </svg>
            Back to Home
        </a>
    </div>

    <div class="row">
        <!-- States Table -->
        <div class="col-lg-8 col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">States List</h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-mobile">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>State Name</th>
                                <th class="d-none d-md-table-cell">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($states as $state)
                            <tr>
                                <td class="text-nowrap">
                                    <span class="badge bg-blue">{{ $state->code }}</span>
                                </td>
                                <td class="w-100">
                                    {{ $state->name }}
                                    <small class="d-block d-md-none text-muted">{{ $state->created_at->format('M d, Y') }}</small>
                                </td>
                                <td class="text-muted d-none d-md-table-cell">{{ $state->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Select Options Demo -->
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Select Options Demo</h3>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Choose a State</label>
                        <select class="form-select" id="stateDemo">
                            @foreach($statesForSelect as $abbr => $name)
                                <option value="{{ $abbr }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Selected State Info</label>
                        <div class="card">
                            <div class="card-body">
                                <div id="selectedInfo">Select a state above to see its details</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Statistics</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center mb-3">
                                <span class="me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                        <circle cx="12" cy="12" r="9"/>
                                        <polyline points="12,7 12,12 15,15"/>
                                    </svg>
                                </span>
                                <div>
                                    <div class="font-weight-medium">{{ $states->count() }}</div>
                                    <div class="text-muted">Total States</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center mb-3">
                                <span class="me-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/>
                                        <path d="M12 7v5l3 3"/>
                                    </svg>
                                </span>
                                <div>
                                    <div class="font-weight-medium">50</div>
                                    <div class="text-muted">US States</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('stateDemo');
    const selectedInfo = document.getElementById('selectedInfo');
    
    const stateData = {
        @foreach($states as $state)
        '{{ $state->code }}': {
            name: '{{ $state->name }}',
            code: '{{ $state->code }}',
            created: '{{ $state->created_at->format("M d, Y H:i") }}'
        },
        @endforeach
    };
    
    stateSelect.addEventListener('change', function() {
        const selectedCode = this.value;
        if (selectedCode && stateData[selectedCode]) {
            const state = stateData[selectedCode];
            selectedInfo.innerHTML = `
                <div class="row g-3">
                    <div class="col-6">
                        <strong>Name:</strong><br>
                        ${state.name}
                    </div>
                    <div class="col-6">
                        <strong>Code:</strong><br>
                        <span class="badge bg-blue">${state.code}</span>
                    </div>
                    <div class="col-12">
                        <strong>Added to DB:</strong><br>
                        <small class="text-muted">${state.created}</small>
                    </div>
                </div>
            `;
        } else {
            selectedInfo.innerHTML = 'Select a state above to see its details';
        }
    });
});
</script>
@endsection