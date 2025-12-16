@extends('layouts.tabler')

@section('title', 'Bank Accounts')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Bank Accounts</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage your bank accounts and reconciliation</p>
        </div>
        @if(auth()->user()->isAdmin() || auth()->user()->isOwner())
        <button class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;" onclick="openCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Bank Account
        </button>
        @endif
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.bank.accounts.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Store</label>
                    <select class="form-select" name="store_id">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Account Type</label>
                    <select class="form-select" name="account_type">
                        <option value="">All Types</option>
                        <option value="checking" {{ request('account_type') == 'checking' ? 'selected' : '' }}>Checking</option>
                        <option value="savings" {{ request('account_type') == 'savings' ? 'selected' : '' }}>Savings</option>
                        <option value="credit_card" {{ request('account_type') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bank Accounts Table -->
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">All Bank Accounts</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 0.875rem;">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                        <tr>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Bank Name</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Account Number</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Type</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Store</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem; text-align: right;">Current Balance</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Last Reconciled</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td style="padding: 1rem; vertical-align: middle;"><strong>{{ $account->bank_name }}</strong></td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                <span class="badge bg-light text-dark">****{{ $account->account_number_last_four }}</span>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;"><span class="badge bg-info">{{ ucwords($account->account_type) }}</span></td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                {{ $account->store ? $account->store->store_info : '<span class="text-muted">Corporate</span>' }}
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: right;">
                                <strong class="text-success">${{ number_format($account->current_balance, 2) }}</strong>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($account->last_reconciled_date)
                                    {{ $account->last_reconciled_date->format('M d, Y') }}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: center;">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.bank.accounts.show', $account->id) }}" class="btn btn-outline-primary" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    @if(auth()->user()->isAdmin() || auth()->user()->isOwner())
                                    <a href="{{ route('admin.bank.reconciliation.index', $account->id) }}" class="btn btn-outline-warning" title="Reconcile">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                            <polyline points="10 9 9 9 8 9"/>
                                        </svg>
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="editAccount({{ $account->id }})" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No bank accounts found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <x-pagination :paginator="$accounts" />
        </div>
    </div>
</div>

@if(auth()->user()->isAdmin() || auth()->user()->isOwner())
<!-- Create/Edit Modal -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalLabel">Add Bank Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="accountForm" onsubmit="return false;">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bankName" class="form-label">Bank Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bankName" name="bank_name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="accountNumber" class="form-label">Last 4 Digits <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="accountNumber" name="account_number_last_four" maxlength="4" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="accountType" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="accountType" name="account_type" required>
                                <option value="">Select Type</option>
                                <option value="checking">Checking</option>
                                <option value="savings">Savings</option>
                                <option value="credit_card">Credit Card</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="storeId" class="form-label">Store</label>
                        <select class="form-select" id="storeId" name="store_id">
                            <option value="">Corporate Account</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->store_info }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="openingBalance" class="form-label">Opening Balance <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="openingBalance" name="opening_balance" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save
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
let accountId = null;

// Open create modal
function openCreateModal() {
    accountId = null;
    document.getElementById('accountModalLabel').textContent = 'Add Bank Account';
    document.getElementById('accountForm').reset();
    new bootstrap.Modal(document.getElementById('accountModal')).show();
}

// Submit form
document.getElementById('accountForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    saveAccount();
});

function saveAccount() {
    const form = document.getElementById('accountForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    const spinner = document.getElementById('saveBtn').querySelector('.spinner-border');
    spinner.classList.remove('d-none');
    document.getElementById('saveBtn').disabled = true;
    
    fetch('/api/bank-accounts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin',
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.errors) {
            alert('Error: ' + JSON.stringify(result.errors));
        } else {
            setTimeout(() => window.location.reload(), 500);
        }
        spinner.classList.add('d-none');
        document.getElementById('saveBtn').disabled = false;
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving bank account');
        spinner.classList.add('d-none');
        document.getElementById('saveBtn').disabled = false;
    });
}

function editAccount(id) {
    fetch(`/api/bank-accounts/${id}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(account => {
        accountId = account.id;
        document.getElementById('accountModalLabel').textContent = 'Edit Bank Account';
        document.getElementById('bankName').value = account.bank_name;
        document.getElementById('accountNumber').value = account.account_number_last_four;
        document.getElementById('accountType').value = account.account_type;
        document.getElementById('storeId').value = account.store_id || '';
        document.getElementById('openingBalance').value = account.opening_balance;
        new bootstrap.Modal(document.getElementById('accountModal')).show();
    });
}
</script>
@endpush





