@extends('layouts.tabler')

@section('title', 'Bank Account Details')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">
                {{ $account->bank_name }}
            </h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">
                Account Ending: ****{{ $account->account_number_last_four }}
            </p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.bank.accounts.index') }}" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Accounts
            </a>
            @if(auth()->user()->isAdmin() || auth()->user()->isOwner())
            <button class="btn btn-warning" onclick="openUploadModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Upload Statement
            </button>
            <a href="{{ route('admin.bank.reconciliation.index', $account->id) }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                </svg>
                Reconcile
            </a>
            @endif
        </div>
    </div>

    <!-- Account Summary Cards -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Current Balance</div>
                    </div>
                    <div class="h1 mb-3">${{ number_format($account->current_balance, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Opening Balance</div>
                    </div>
                    <div class="h1 mb-3">${{ number_format($account->opening_balance, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Last Reconciled</div>
                    </div>
                    <div class="h1 mb-3">
                        @if($account->last_reconciled_date)
                            {{ $account->last_reconciled_date->diffForHumans() }}
                        @else
                            <span class="text-muted">Never</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="subheader">Total Transactions</div>
                    </div>
                    <div class="h1 mb-3">{{ $account->transactions->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Details -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Information</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Bank Name:</strong></td>
                            <td>{{ $account->bank_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Account Number:</strong></td>
                            <td>****{{ $account->account_number_last_four }}</td>
                        </tr>
                        <tr>
                            <td><strong>Account Type:</strong></td>
                            <td><span class="badge bg-info">{{ ucwords($account->account_type) }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Store:</strong></td>
                            <td>{{ $account->store ? $account->store->store_info : '<span class="text-muted">Corporate Account</span>' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                @if($account->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Transactions</h3>
                </div>
                <div class="card-body p-0">
                    @if($account->transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($account->transactions->take(5) as $transaction)
                                <tr>
                                    <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                                    <td class="text-truncate" style="max-width: 200px;">{{ $transaction->description }}</td>
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
                                        @else
                                            <span class="badge bg-warning">Unmatched</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-3">No transactions yet</div>
                    @endif
                </div>
                @if($account->transactions->count() > 5)
                <div class="card-footer">
                    <a href="{{ route('admin.bank.reconciliation.index', $account->id) }}" class="btn btn-sm btn-outline-primary w-100">
                        View All Transactions
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isOwner())
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
function openUploadModal() {
    new bootstrap.Modal(document.getElementById('uploadModal')).show();
}

document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const fileInput = document.getElementById('csvFile');
    formData.append('file', fileInput.files[0]);
    formData.append('bank_account_id', {{ $account->id }});
    
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
        } else if (result.errors) {
            alert('Error: ' + JSON.stringify(result.errors));
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





