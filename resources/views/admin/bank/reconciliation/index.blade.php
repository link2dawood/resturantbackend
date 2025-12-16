@extends('layouts.tabler')

@section('title', 'Bank Reconciliation')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">
                Bank Reconciliation
            </h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">
                {{ $account->bank_name }} - Account Ending: ****{{ $account->account_number_last_four }}
            </p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.bank.accounts.show', $account->id) }}" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Account
            </a>
            @if(auth()->user()->isAdmin() || auth()->user()->isOwner())
            <button class="btn btn-primary" onclick="openUploadModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload Statement
            </button>
            @endif
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Unmatched</div>
                    <div class="h1 mb-3 text-warning">{{ $unmatchedCount }}</div>
                    <div class="d-flex align-items-center text-muted">Transactions</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Matched</div>
                    <div class="h1 mb-3 text-success">{{ $matchedCount }}</div>
                    <div class="d-flex align-items-center text-muted">Transactions</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Reviewed</div>
                    <div class="h1 mb-3 text-info">{{ $reviewedCount }}</div>
                    <div class="d-flex align-items-center text-muted">Transactions</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Current Balance</div>
                    <div class="h1 mb-3">${{ number_format($account->current_balance, 2) }}</div>
                    <div class="d-flex align-items-center text-muted">as per records</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.bank.reconciliation.index', $account->id) }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="unmatched" {{ $status == 'unmatched' ? 'selected' : '' }}>Unmatched</option>
                        <option value="matched" {{ $status == 'matched' ? 'selected' : '' }}>Matched</option>
                        <option value="reviewed" {{ $status == 'reviewed' ? 'selected' : '' }}>Reviewed</option>
                        <option value="exception" {{ $status == 'exception' ? 'selected' : '' }}>Exception</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bank Transactions List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bank Transactions</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th class="text-center">Type</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                                    <td>{{ $transaction->description ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($transaction->transaction_type == 'credit')
                                            <span class="badge bg-success">Credit</span>
                                        @else
                                            <span class="badge bg-danger">Debit</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="{{ $transaction->transaction_type == 'credit' ? 'text-success' : 'text-danger' }}">
                                            {{ $transaction->transaction_type == 'credit' ? '+' : '-' }}${{ number_format($transaction->amount, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($transaction->reconciliation_status == 'matched')
                                            <span class="badge bg-success">Matched</span>
                                        @elseif($transaction->reconciliation_status == 'reviewed')
                                            <span class="badge bg-info">Reviewed</span>
                                        @elseif($transaction->reconciliation_status == 'exception')
                                            <span class="badge bg-danger">Exception</span>
                                        @else
                                            <span class="badge bg-warning">Unmatched</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewMatches({{ $transaction->id }})">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No transactions found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Pagination -->
                <x-pagination :paginator="$transactions" />
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isOwner())
<!-- Match Modal -->
<div class="modal fade" id="matchModal" tabindex="-1" aria-labelledby="matchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="matchModalLabel">Match Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="matchModalBody">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Upload Statement Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Bank Statement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="csvFile" name="file" accept=".csv" required>
                        <small class="text-muted">Upload your bank statement in CSV format</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Upload & Process
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
let accountId = {{ $account->id }};

function viewMatches(transactionId) {
    fetch(`/api/bank/reconciliation/${transactionId}/matches`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        const modalBody = document.getElementById('matchModalBody');
        // TODO: Implement match modal content with expense and revenue suggestions
        modalBody.innerHTML = '<p>Matching interface - will show potential matches</p>';
        new bootstrap.Modal(document.getElementById('matchModal')).show();
    });
}

function openUploadModal() {
    new bootstrap.Modal(document.getElementById('uploadModal')).show();
}

// Upload form handler
document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const fileInput = document.getElementById('csvFile');
    formData.append('file', fileInput.files[0]);
    formData.append('bank_account_id', accountId);
    
    const spinner = document.getElementById('uploadBtn').querySelector('.spinner-border');
    spinner.classList.remove('d-none');
    document.getElementById('uploadBtn').disabled = true;
    
    fetch('/api/bank/import/upload', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.message) {
            alert(result.message);
        } else if (result.error) {
            alert('Error: ' + result.error);
        }
        spinner.classList.add('d-none');
        document.getElementById('uploadBtn').disabled = false;
        bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide();
        setTimeout(() => window.location.reload(), 500);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error uploading statement');
        spinner.classList.add('d-none');
        document.getElementById('uploadBtn').disabled = false;
    });
});
</script>
@endpush
