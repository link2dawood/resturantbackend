@extends('layouts.tabler')

@section('title', 'Review Queue')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Review Queue</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Review and categorize unmatched transactions</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Expenses
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-warning">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="12" y1="8" x2="12" y2="12"/>
                                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Pending</div>
                            <div class="h4 mb-0">{{ $stats['total_pending'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-danger">
                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">No Vendor</div>
                            <div class="h4 mb-0">{{ $stats['by_reason']['Vendor not found'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-info">
                                    <rect x="3" y="3" width="7" height="7"/>
                                    <rect x="14" y="3" width="7" height="7"/>
                                    <rect x="14" y="14" width="7" height="7"/>
                                    <rect x="3" y="14" width="7" height="7"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">No Category</div>
                            <div class="h4 mb-0">{{ $stats['by_reason']['COA not assigned'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-secondary bg-opacity-10 rounded p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-secondary">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="9" y1="9" x2="15" y2="15"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Duplicates</div>
                            <div class="h4 mb-0">{{ $stats['by_reason']['Possible duplicate'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grouped Transaction Lists -->
    @if($groupedTransactions->count() > 0)
    <div class="accordion" id="reviewAccordion">
        @php
            $badgeColors = [
                'Vendor not found' => 'danger',
                'COA not assigned' => 'info',
                'Possible duplicate' => 'secondary',
                'Needs verification' => 'warning'
            ];
        @endphp
        
        @foreach($groupedTransactions as $reason => $transactions)
        @php
            $badgeColor = $badgeColors[$reason] ?? 'secondary';
            $accordionId = 'accordion_' . str_replace(' ', '_', $reason);
        @endphp
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button {{ !$loop->first ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $accordionId }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                    <span class="badge bg-{{ $badgeColor }} me-3">{{ $transactions->count() }}</span>
                    {{ $reason }}
                </button>
            </h2>
            <div id="{{ $accordionId }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#reviewAccordion">
                <div class="accordion-body p-0">
                    <div class="list-group">
                        @foreach($transactions as $expense)
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    @can('review', 'categorize')
                                    <input type="checkbox" class="form-check-input" data-expense-id="{{ $expense->id }}" data-group="{{ $reason }}">
                                    @endcan
                                </div>
                                <div class="col-md-2">
                                    <strong>{{ $expense->transaction_date->format('M d, Y') }}</strong><br>
                                    <small class="text-muted">{{ $expense->store->store_info ?? 'Unknown' }}</small>
                                </div>
                                <div class="col-md-4">
                                    <div class="fw-bold">{{ $expense->description ?? $expense->vendor_name_raw ?? 'No description' }}</div>
                                    @if($expense->reference_number)
                                        <small class="text-muted">Ref: {{ $expense->reference_number }}</small>
                                    @endif
                                </div>
                                <div class="col-md-2 text-end">
                                    <strong class="text-danger">${{ number_format($expense->amount, 2) }}</strong><br>
                                    <small class="text-muted">{{ ucwords(str_replace('_', ' ', $expense->transaction_type)) }}</small>
                                </div>
                                <div class="col-md-2 text-center">
                                    <span class="badge bg-{{ $badgeColor }}">{{ $reason }}</span>
                                </div>
                                <div class="col-md-2 text-end">
                                    @can('review', 'categorize')
                                    <button class="btn btn-sm btn-outline-primary" onclick="openReviewModal({{ $expense->id }})">
                                        Review
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <!-- Empty State -->
    <div class="card text-center">
        <div class="card-body py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-success mb-3">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <h3 class="text-success">All Caught Up! 🎉</h3>
            <p class="text-muted">No transactions need review at this time.</p>
        </div>
    </div>
    @endif
</div>

<!-- Individual Transaction Review Modal -->
@can('review', 'categorize')
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Review Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reviewForm">
                <div class="modal-body">
                    <input type="hidden" id="reviewExpenseId" name="id">
                    
                    <!-- Transaction Info -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Date:</strong> <span id="reviewDate"></span><br>
                                    <strong>Amount:</strong> <span id="reviewAmount" class="text-danger"></span><br>
                                    <strong>Store:</strong> <span id="reviewStore"></span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Type:</strong> <span id="reviewType"></span><br>
                                    <strong>Reference:</strong> <span id="reviewReference"></span>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-2"><strong>Description:</strong></div>
                            <div class="text-muted" id="reviewDescription"></div>
                        </div>
                    </div>

                    <!-- Categorization Fields -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reviewVendor" class="form-label">Vendor <span class="text-danger">*</span></label>
                            <select class="form-select" id="reviewVendor" name="vendor_id" required>
                                <option value="">Select Vendor</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reviewCoa" class="form-label">Category (COA) <span class="text-danger">*</span></label>
                            <select class="form-select" id="reviewCoa" name="coa_id" required>
                                <option value="">Select Category</option>
                                @foreach($coas as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->account_code }} - {{ $coa->account_name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="createMappingRule" name="create_mapping_rule" value="1">
                            <label class="form-check-label" for="createMappingRule">
                                Create mapping rule from this transaction
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reviewNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="reviewNotes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-warning" onclick="skipTransaction()">Skip for Now</button>
                    <button type="submit" class="btn btn-primary" id="resolveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Resolve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@push('scripts')
@can('review', 'categorize')
<script>
let selectedExpenseId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Review form submission
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        resolveTransaction();
    });
});

// Open review modal
function openReviewModal(expenseId) {
    selectedExpenseId = expenseId;
    
    fetch(`/api/expenses/${expenseId}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(expense => {
        document.getElementById('reviewExpenseId').value = expense.id;
        document.getElementById('reviewDate').textContent = new Date(expense.transaction_date).toLocaleDateString();
        document.getElementById('reviewAmount').textContent = '$' + parseFloat(expense.amount).toFixed(2);
        document.getElementById('reviewStore').textContent = expense.store?.store_info || 'Unknown';
        document.getElementById('reviewType').textContent = expense.transaction_type;
        document.getElementById('reviewReference').textContent = expense.reference_number || 'N/A';
        document.getElementById('reviewDescription').textContent = expense.description || expense.vendor_name_raw || 'No description';
        
        new bootstrap.Modal(document.getElementById('reviewModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading transaction');
    });
}

// Resolve transaction
function resolveTransaction() {
    const formData = new FormData(document.getElementById('reviewForm'));
    const data = {
        vendor_id: formData.get('vendor_id'),
        coa_id: formData.get('coa_id'),
        notes: formData.get('notes'),
        create_mapping_rule: formData.get('create_mapping_rule') === '1'
    };
    
    const spinner = document.getElementById('resolveBtn').querySelector('.spinner-border');
    spinner.classList.remove('d-none');
    document.getElementById('resolveBtn').disabled = true;
    
    fetch(`/api/expenses/${selectedExpenseId}/resolve`, {
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
        if (result.message) {
            alert('Success: ' + result.message);
        } else if (result.error) {
            alert('Error: ' + result.error);
        }
        spinner.classList.add('d-none');
        document.getElementById('resolveBtn').disabled = false;
        bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
        setTimeout(() => window.location.reload(), 500);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error resolving transaction');
        spinner.classList.add('d-none');
        document.getElementById('resolveBtn').disabled = false;
    });
}

function skipTransaction() {
    bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
}
</script>
@endcan
@endpush
