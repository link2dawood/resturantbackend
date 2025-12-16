@extends('layouts.tabler')

@section('title', 'Expense Ledger')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Expense Ledger</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Track all cash, credit card, and bank expenses</p>
        </div>
        <div class="d-flex gap-2">
            @can('review', 'view')
            <a href="{{ route('admin.expenses.review') }}" class="btn btn-warning d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Review Queue
            </a>
            @endcan
            @can('imports', 'upload')
            <button class="btn btn-success d-flex align-items-center" style="gap: 0.5rem;" onclick="showSyncModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h7"/>
                    <path d="M15 10l5-5 5 5"/>
                    <path d="M20 4v6h-6"/>
                </svg>
                Sync Cash Expenses
            </button>
            @endcan
            @can('expenses', 'create')
            <button class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;" data-bs-toggle="modal" data-bs-target="#expenseModal" onclick="openCreateModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add Expense
            </button>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.expenses.index') }}" method="GET" class="row g-3">
                @canViewAllStores
                <div class="col-md-2">
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
                @endcanViewAllStores
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Transaction Type</label>
                    <select class="form-select" name="transaction_type">
                        <option value="">All Types</option>
                        <option value="cash" {{ request('transaction_type') == 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="credit_card" {{ request('transaction_type') == 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                        <option value="bank_transfer" {{ request('transaction_type') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="check" {{ request('transaction_type') == 'check' ? 'selected' : '' }}>Check</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="needs_review">
                        <option value="">All</option>
                        <option value="0" {{ request('needs_review') === '0' ? 'selected' : '' }}>Normal</option>
                        <option value="1" {{ request('needs_review') === '1' ? 'selected' : '' }}>Needs Review</option>
                    </select>
                </div>
                <div class="col-md-2">
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

    <!-- Expenses Table -->
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">All Expenses</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 0.875rem;">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                        <tr>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Date</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Store</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Vendor</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Category (COA)</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem; text-align: right;">Amount</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Type</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Status</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                        <tr>
                            <td style="padding: 1rem; vertical-align: middle;">{{ $expense->transaction_date->format('M d, Y') }}</td>
                            <td style="padding: 1rem; vertical-align: middle;">{{ $expense->store->store_info ?? 'N/A' }}</td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($expense->vendor)
                                    {{ $expense->vendor->vendor_name }}
                                @elseif($expense->vendor_name_raw)
                                    {{ $expense->vendor_name_raw }}
                                @else
                                    <span class="text-muted">Unknown</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($expense->coa)
                                    <span class="text-muted">{{ $expense->coa->account_code }}</span> - {{ $expense->coa->account_name }}
                                @else
                                    <span class="text-muted">Not assigned</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: right;">
                                <strong class="text-danger">${{ number_format($expense->amount, 2) }}</strong>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $expense->transaction_type)) }}</span>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($expense->needs_review)
                                    <span class="badge bg-warning text-dark">Review</span>
                                @else
                                    <span class="badge bg-success">OK</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: center;">
                                @if(auth()->user()->isAdmin() || auth()->user()->isOwner())
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-primary" onclick="editExpense({{ $expense->id }})" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No expenses found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Totals Row -->
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Total Expenses:</strong> <span class="text-danger">${{ number_format($total, 2) }}</span>
                    </div>
                    <div class="col-md-6">
                        <x-pagination :paginator="$expenses" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync Modal -->
@if(auth()->user()->isAdmin())
<div class="modal fade" id="syncModal" tabindex="-1" aria-labelledby="syncModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="syncModalLabel">Sync Cash Expenses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="syncForm">
                <div class="modal-body">
                    <p class="text-muted mb-3">Import cash expenses from daily reports into the expense ledger.</p>
                    
                    <div class="mb-3">
                        <label for="syncStartDate" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="syncStartDate" name="start_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="syncEndDate" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="syncEndDate" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="syncBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Sync Expenses
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Create/Edit Expense Modal -->
@if(auth()->user()->isAdmin() || auth()->user()->isOwner() || auth()->user()->isManager())
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expenseModalLabel">Add Manual Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="expenseForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expenseDate" class="form-label">Transaction Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="expenseDate" name="transaction_date" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expenseStore" class="form-label">Store <span class="text-danger">*</span></label>
                            <select class="form-select" id="expenseStore" name="store_id" required>
                                <option value="">Select Store</option>
                                <!-- Options loaded via JavaScript -->
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expenseVendor" class="form-label">Vendor</label>
                            <select class="form-select" id="expenseVendor" name="vendor_id">
                                <option value="">Select Vendor</option>
                                <!-- Options loaded via JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expensePaymentMethod" class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" id="expensePaymentMethod" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="check">Check</option>
                                <option value="eft">EFT</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="expenseCoa" class="form-label">Category (COA) <span class="text-danger">*</span></label>
                        <select class="form-select" id="expenseCoa" name="coa_id" required>
                            <option value="">Select Category</option>
                            <!-- Options loaded via JavaScript -->
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expenseAmount" class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="expenseAmount" name="amount" step="0.01" min="0.01" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expenseReference" class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="expenseReference" name="reference_number">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="expenseDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="expenseDescription" name="description" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="expenseReceiptUrl" class="form-label">Receipt URL</label>
                        <input type="url" class="form-control" id="expenseReceiptUrl" name="receipt_url" placeholder="https://...">
                    </div>

                    <div class="mb-3">
                        <label for="expenseNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="expenseNotes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Edit Expense Modal -->
@if(auth()->user()->isAdmin() || auth()->user()->isOwner())
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="editExpenseId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editVendor" class="form-label">Vendor</label>
                            <select class="form-select" id="editVendor" name="vendor_id">
                                <option value="">Select Vendor</option>
                                <!-- Options loaded via JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editCoa" class="form-label">Category (COA)</label>
                            <select class="form-select" id="editCoa" name="coa_id">
                                <option value="">Select Category</option>
                                <!-- Options loaded via JavaScript -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="editNotes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editSaveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save Changes
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
// Auth flags from backend
const userPermissions = {
    isAdmin: @json(auth()->user()->isAdmin()),
    isOwner: @json(auth()->user()->isOwner()),
    isManager: @json(auth()->user()->isManager())
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadStores();
    loadVendors();
    loadCOAs();
    
    // Expense form submission
    document.getElementById('expenseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveExpense();
    });

    // Edit form submission
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateExpense();
    });

    // Sync form submission
    document.getElementById('syncForm').addEventListener('submit', function(e) {
        e.preventDefault();
        syncExpenses();
    });

    // Auto-fill COA when vendor is selected (Create modal)
    const expenseVendorSelect = document.getElementById('expenseVendor');
    if (expenseVendorSelect) {
        expenseVendorSelect.addEventListener('change', function() {
            const vendorId = this.value;
            if (vendorId) {
                autoFillCoaFromVendor(vendorId, 'expenseCoa');
            } else {
                // Clear COA if vendor is deselected
                document.getElementById('expenseCoa').value = '';
            }
        });
    }

    // Auto-fill COA when vendor is selected (Edit modal)
    const editVendorSelect = document.getElementById('editVendor');
    if (editVendorSelect) {
        editVendorSelect.addEventListener('change', function() {
            const vendorId = this.value;
            if (vendorId) {
                autoFillCoaFromVendor(vendorId, 'editCoa');
            } else {
                // Clear COA if vendor is deselected
                document.getElementById('editCoa').value = '';
            }
        });
    }

    // Reload vendors when store changes (Create modal)
    const expenseStoreSelect = document.getElementById('expenseStore');
    if (expenseStoreSelect) {
        expenseStoreSelect.addEventListener('change', function() {
            const storeId = this.value;
            loadVendors(storeId);
            // Clear vendor and COA when store changes
            document.getElementById('expenseVendor').value = '';
            document.getElementById('expenseCoa').value = '';
        });
    }
});

// Show sync modal
function showSyncModal() {
    new bootstrap.Modal(document.getElementById('syncModal')).show();
}

// Sync expenses
function syncExpenses() {
    const form = document.getElementById('syncForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    setButtonLoading('syncBtn', true);
    
    fetch(`/api/expenses/sync-cash-expenses?${params.toString()}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(result => {
        if (result.error) {
            showToast(result.error, 'error');
        } else {
            showToast(`Synced ${result.imported} expenses, skipped ${result.skipped}`, 'success');
            setTimeout(() => window.location.reload(), 500);
        }
        setButtonLoading('syncBtn', false);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error syncing expenses', 'error');
        setButtonLoading('syncBtn', false);
    });
}

// Open create modal
function openCreateModal() {
    document.getElementById('expenseModalLabel').textContent = 'Add Manual Expense';
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseDate').value = new Date().toISOString().split('T')[0];
    clearValidationErrors();
}

// Save expense
function saveExpense() {
    const form = document.getElementById('expenseForm');
    const formData = new FormData(form);
    
    const data = {
        transaction_date: formData.get('transaction_date'),
        store_id: formData.get('store_id'),
        vendor_id: formData.get('vendor_id') || null,
        coa_id: formData.get('coa_id'),
        amount: formData.get('amount'),
        payment_method: formData.get('payment_method'),
        description: formData.get('description'),
        reference_number: formData.get('reference_number'),
        receipt_url: formData.get('receipt_url'),
        notes: formData.get('notes'),
    };
    
    setButtonLoading('saveBtn', true);
    
    fetch('/api/expenses', {
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
            displayValidationErrors(result.errors);
        } else {
            showToast(result.message, 'success');
            setTimeout(() => window.location.reload(), 500);
        }
        setButtonLoading('saveBtn', false);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error saving expense', 'error');
        setButtonLoading('saveBtn', false);
    });
}

// Edit expense
function editExpense(id) {
    fetch(`/api/expenses/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(expense => {
        document.getElementById('editExpenseId').value = expense.id;
        document.getElementById('editVendor').value = expense.vendor_id || '';
        document.getElementById('editCoa').value = expense.coa_id || '';
        document.getElementById('editDescription').value = expense.description || '';
        document.getElementById('editNotes').value = expense.notes || '';
        
        new bootstrap.Modal(document.getElementById('editModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error loading expense details', 'error');
    });
}

// Update expense
function updateExpense() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const id = document.getElementById('editExpenseId').value;
    
    const data = {};
    if (formData.get('vendor_id')) data.vendor_id = formData.get('vendor_id');
    if (formData.get('coa_id')) data.coa_id = formData.get('coa_id');
    if (formData.get('description')) data.description = formData.get('description');
    if (formData.get('notes')) data.notes = formData.get('notes');
    
    setButtonLoading('editSaveBtn', true);
    
    fetch(`/api/expenses/${id}`, {
        method: 'PUT',
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
            displayValidationErrors(result.errors);
        } else {
            showToast(result.message, 'success');
            setTimeout(() => window.location.reload(), 500);
        }
        setButtonLoading('editSaveBtn', false);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating expense', 'error');
        setButtonLoading('editSaveBtn', false);
    });
}

// Load stores
function loadStores() {
    fetch('/api/stores', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(stores => {
        const selects = ['expenseStore'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">Select Store</option>';
            stores.forEach(store => {
                const option = document.createElement('option');
                option.value = store.id;
                option.textContent = store.name;
                select.appendChild(option);
            });
        });
    })
    .catch(error => console.error('Error loading stores:', error));
}

// Load vendors (filtered by store if store is selected)
function loadVendors(storeId = null) {
    let url = '/api/vendors?per_page=1000';
    if (storeId) {
        url += `&store_id=${storeId}`;
    }
    
    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        const selects = ['expenseVendor', 'editVendor'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.innerHTML = '<option value="">Select Vendor</option>';
            data.data.forEach(vendor => {
                const option = document.createElement('option');
                option.value = vendor.id;
                option.textContent = vendor.vendor_name;
                select.appendChild(option);
            });
        });
    })
    .catch(error => console.error('Error loading vendors:', error));
}

// Load COAs
function loadCOAs() {
    fetch('/api/coa?per_page=1000', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        const selects = ['expenseCoa', 'editCoa'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.innerHTML = '<option value="">Select Category</option>';
            data.data.forEach(coa => {
                const option = document.createElement('option');
                option.value = coa.id;
                option.textContent = `${coa.account_code} - ${coa.account_name}`;
                select.appendChild(option);
            });
        });
    })
    .catch(error => console.error('Error loading COAs:', error));
}

// Auto-fill COA category when vendor is selected
function autoFillCoaFromVendor(vendorId, coaSelectId) {
    if (!vendorId) return;
    
    fetch(`/api/vendors/${vendorId}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(vendor => {
        const coaSelect = document.getElementById(coaSelectId);
        if (!coaSelect) return;
        
        // Handle both direct response and wrapped response
        const vendorData = vendor.data || vendor;
        const defaultCoaId = vendorData.default_coa_id;
        
        // If vendor has a default COA, auto-fill it
        if (defaultCoaId) {
            // Ensure COAs are loaded first
            if (coaSelect.options.length <= 1) {
                loadCOAs().then(() => {
                    setCoaValue(coaSelect, defaultCoaId);
                });
            } else {
                setCoaValue(coaSelect, defaultCoaId);
            }
        }
    })
    .catch(error => {
        console.error('Error fetching vendor details:', error);
    });
}

// Helper function to set COA value with visual feedback
function setCoaValue(coaSelect, coaId) {
    coaSelect.value = coaId;
    
    // Show a brief visual indicator that COA was auto-filled
    coaSelect.style.backgroundColor = '#e7f3ff';
    coaSelect.style.transition = 'background-color 0.3s';
    
    setTimeout(() => {
        coaSelect.style.backgroundColor = '';
    }, 1500);
    
    // Show toast notification
    showToast('Category auto-filled from vendor default', 'success');
}

// Utility functions
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatTransactionType(type) {
    return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);
    setTimeout(() => { toast.remove(); }, 3000);
}

function setButtonLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    const spinner = btn?.querySelector('.spinner-border');
    if (loading) {
        btn.disabled = true;
        spinner?.classList.remove('d-none');
    } else {
        btn.disabled = false;
        spinner?.classList.add('d-none');
    }
}

function displayValidationErrors(errors) {
    clearValidationErrors();
    Object.keys(errors).forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = errors[field][0];
            }
        }
    });
}

function clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}
</script>
@endpush

