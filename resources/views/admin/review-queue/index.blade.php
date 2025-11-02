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
            <button class="btn btn-outline-primary d-flex align-items-center" style="gap: 0.5rem;" onclick="refreshQueue()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                    <path d="M21 3v5h-5"/>
                    <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                    <path d="M8 16H3v5"/>
                </svg>
                Refresh
            </button>
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
                            <div class="h4 mb-0" id="totalPending">0</div>
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
                            <div class="h4 mb-0" id="noVendorCount">0</div>
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
                            <div class="h4 mb-0" id="noCategoryCount">0</div>
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
                            <div class="h4 mb-0" id="duplicateCount">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grouped Transaction Lists -->
    <div class="accordion" id="reviewAccordion">
        <!-- Will be populated by JavaScript -->
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="card text-center d-none">
        <div class="card-body py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-success mb-3">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <h3 class="text-success">All Caught Up! 🎉</h3>
            <p class="text-muted">No transactions need review at this time.</p>
        </div>
    </div>
</div>

<!-- Individual Transaction Review Modal -->
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
                                <!-- Options loaded via JavaScript -->
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="reviewCoa" class="form-label">Category (COA) <span class="text-danger">*</span></label>
                            <select class="form-select" id="reviewCoa" name="coa_id" required>
                                <option value="">Select Category</option>
                                <!-- Options loaded via JavaScript -->
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Suggestions -->
                    <div id="suggestionsSection" class="alert alert-info d-none">
                        <strong>Suggestions:</strong>
                        <div id="suggestionsList"></div>
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

<!-- Bulk Review Modal -->
<div class="modal fade" id="bulkReviewModal" tabindex="-1" aria-labelledby="bulkReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkReviewModalLabel">Bulk Categorize</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bulkReviewForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong><span id="bulkSelectedCount">0</span> transactions</strong> selected for bulk categorization.
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="bulkVendor" class="form-label">Vendor <span class="text-danger">*</span></label>
                            <select class="form-select" id="bulkVendor" name="vendor_id" required>
                                <option value="">Select Vendor</option>
                                <!-- Options loaded via JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bulkCoa" class="form-label">Category (COA) <span class="text-danger">*</span></label>
                            <select class="form-select" id="bulkCoa" name="coa_id" required>
                                <option value="">Select Category</option>
                                <!-- Options loaded via JavaScript -->
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bulkCreateMappingRule" name="create_mapping_rule" value="1">
                            <label class="form-check-label" for="bulkCreateMappingRule">
                                Create mapping rule from first transaction
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="bulkNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="bulkNotes" name="notes" rows="2"></textarea>
                    </div>

                    <div class="alert alert-warning">
                        <strong>Preview:</strong> This will apply the selected vendor and category to all <span id="bulkPreviewCount">0</span> selected transactions.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bulkResolveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Apply to All
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Global variables
let reviewQueue = {};
let selectedExpenses = [];
let allVendors = [];
let allCOAs = [];

// Auth flags from backend
const userPermissions = {
    isAdmin: @json(auth()->user()->isAdmin()),
    isOwner: @json(auth()->user()->isOwner()),
    isManager: @json(auth()->user()->isManager())
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadReviewQueue();
    loadVendors();
    loadCOAs();
    
    // Review form submission
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        resolveTransaction();
    });

    // Bulk form submission
    document.getElementById('bulkReviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        bulkResolve();
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            bootstrap.Modal.getInstance(document.getElementById('reviewModal'))?.hide();
        }
    });
});

// Load review queue
function loadReviewQueue() {
    fetch('/api/expenses/review-queue', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        reviewQueue = data;
        renderSummary(data);
        renderGroupedTransactions(data);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error loading review queue', 'error');
    });
}

// Render summary cards
function renderSummary(data) {
    document.getElementById('totalPending').textContent = data.total || 0;
    
    const byReason = data.by_reason || {};
    document.getElementById('noVendorCount').textContent = byReason['Vendor not found']?.count || 0;
    document.getElementById('noCategoryCount').textContent = byReason['COA not assigned']?.count || 0;
    document.getElementById('duplicateCount').textContent = byReason['Possible duplicate']?.count || 0;
}

// Render grouped transactions
function renderGroupedTransactions(data) {
    const accordion = document.getElementById('reviewAccordion');
    accordion.innerHTML = '';
    selectedExpenses = [];

    if (data.total === 0) {
        document.getElementById('emptyState').classList.remove('d-none');
        accordion.innerHTML = '';
        return;
    }

    document.getElementById('emptyState').classList.add('d-none');

    const byReason = data.by_reason || {};
    Object.keys(byReason).forEach((reason, index) => {
        const group = byReason[reason];
        const accordionId = `accordion-${index}`;
        const collapseId = `collapse-${index}`;

        const accordionItem = `
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button ${index === 0 ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                        <strong>${reason}</strong> <span class="badge bg-primary ms-2">${group.count}</span>
                    </button>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" data-bs-parent="#reviewAccordion">
                    <div class="accordion-body p-0">
                        ${renderTransactionList(group.transactions, reason)}
                    </div>
                </div>
            </div>
        `;
        accordion.innerHTML += accordionItem;
    });

    // Reinitialize accordion
    const accordionElements = accordion.querySelectorAll('.accordion-collapse');
    accordionElements.forEach(el => {
        new bootstrap.Collapse(el, {toggle: false});
    });
}

// Render transaction list for a group
function renderTransactionList(transactions, reason) {
    if (!transactions || transactions.length === 0) {
        return '<div class="p-3 text-center text-muted">No transactions in this group</div>';
    }

    let html = `
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="selectAllInGroup('${reason}')">
                    Select All
                </button>
                ${userPermissions.isAdmin || userPermissions.isOwner ? `
                <button class="btn btn-sm btn-success" onclick="openBulkReview('${reason}')" id="bulkBtn-${reason}" disabled>
                    Bulk Categorize (<span id="selectedCount-${reason}">0</span>)
                </button>
                ` : ''}
            </div>
        </div>
        <div class="list-group list-group-flush">
    `;

    transactions.forEach(expense => {
        html += renderTransactionCard(expense, reason);
    });

    html += '</div>';
    return html;
}

// Render individual transaction card
function renderTransactionCard(expense, reason) {
    const badgeColor = reason === 'Vendor not found' ? 'danger' : 
                      reason === 'COA not assigned' ? 'info' : 'secondary';

    return `
        <div class="list-group-item">
            <div class="row align-items-center">
                <div class="col-auto">
                    <input type="checkbox" class="form-check-input" data-expense-id="${expense.id}" data-group="${reason}" onchange="updateSelection('${reason}')">
                </div>
                <div class="col-md-2">
                    <strong>${formatDate(expense.transaction_date)}</strong><br>
                    <small class="text-muted">${expense.store?.store_info || 'Unknown'}</small>
                </div>
                <div class="col-md-4">
                    <div class="fw-bold">${expense.description || expense.vendor_name_raw || 'No description'}</div>
                    ${expense.reference_number ? `<small class="text-muted">Ref: ${expense.reference_number}</small>` : ''}
                </div>
                <div class="col-md-2 text-end">
                    <strong class="text-danger">$${parseFloat(expense.amount).toFixed(2)}</strong><br>
                    <small class="text-muted">${expense.transaction_type}</small>
                </div>
                <div class="col-md-2 text-center">
                    <span class="badge bg-${badgeColor}">${reason}</span>
                </div>
                <div class="col-md-2 text-end">
                    ${userPermissions.isAdmin || userPermissions.isOwner ? `
                    <button class="btn btn-sm btn-outline-primary" onclick="openReviewModal(${expense.id})">
                        Review
                    </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

// Open review modal
function openReviewModal(expenseId) {
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
        document.getElementById('reviewDate').textContent = formatDate(expense.transaction_date);
        document.getElementById('reviewAmount').textContent = '$' + parseFloat(expense.amount).toFixed(2);
        document.getElementById('reviewStore').textContent = expense.store?.store_info || 'Unknown';
        document.getElementById('reviewType').textContent = expense.transaction_type;
        document.getElementById('reviewReference').textContent = expense.reference_number || 'N/A';
        document.getElementById('reviewDescription').textContent = expense.description || expense.vendor_name_raw || 'No description';
        
        // Load suggestions
        loadSuggestions(expense);
        
        new bootstrap.Modal(document.getElementById('reviewModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error loading transaction', 'error');
    });
}

// Load suggestions
function loadSuggestions(expense) {
    const suggestionsSection = document.getElementById('suggestionsSection');
    const suggestionsList = document.getElementById('suggestionsList');
    
    // Try fuzzy vendor matching
    if (expense.vendor_name_raw) {
        fetch(`/api/vendors/match?description=${encodeURIComponent(expense.vendor_name_raw)}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(result => {
            if (result.match && result.confidence >= 60) {
                suggestionsList.innerHTML = `
                    <div class="mt-2">
                        <strong>Suggested Vendor:</strong> ${result.vendor.vendor_name} 
                        (${Math.round(result.confidence)}% confidence)
                        <button class="btn btn-sm btn-outline-primary ms-2" onclick="applySuggestion('vendor', ${result.vendor.id})">Apply</button>
                    </div>
                `;
                if (result.vendor.default_coa_id) {
                    suggestionsList.innerHTML += `
                        <div class="mt-2">
                            <strong>Suggested Category:</strong> ${result.vendor.default_coa?.account_code || ''} - ${result.vendor.default_coa?.account_name || ''}
                            <button class="btn btn-sm btn-outline-primary ms-2" onclick="applySuggestion('coa', ${result.vendor.default_coa_id})">Apply</button>
                        </div>
                    `;
                }
                suggestionsSection.classList.remove('d-none');
            } else {
                suggestionsSection.classList.add('d-none');
            }
        })
        .catch(() => {
            suggestionsSection.classList.add('d-none');
        });
    } else {
        suggestionsSection.classList.add('d-none');
    }
}

// Apply suggestion
function applySuggestion(type, id) {
    if (type === 'vendor') {
        document.getElementById('reviewVendor').value = id;
    } else if (type === 'coa') {
        document.getElementById('reviewCoa').value = id;
    }
}

// Resolve transaction
function resolveTransaction() {
    const form = document.getElementById('reviewForm');
    const formData = new FormData(form);
    const id = document.getElementById('reviewExpenseId').value;
    
    const data = {};
    if (formData.get('vendor_id')) data.vendor_id = formData.get('vendor_id');
    if (formData.get('coa_id')) data.coa_id = formData.get('coa_id');
    if (formData.get('create_mapping_rule')) data.create_mapping_rule = true;
    if (formData.get('notes')) data.notes = formData.get('notes');

    if (!data.vendor_id || !data.coa_id) {
        showToast('Please select both vendor and category', 'error');
        return;
    }

    setButtonLoading('resolveBtn', true);

    fetch(`/api/expenses/${id}/resolve`, {
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
        if (result.error) {
            showToast(result.error, 'error');
        } else {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
            loadReviewQueue();
        }
        setButtonLoading('resolveBtn', false);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error resolving transaction', 'error');
        setButtonLoading('resolveBtn', false);
    });
}

// Skip transaction
function skipTransaction() {
    // Just close the modal for now
    bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
}

// Update selection count
function updateSelection(group) {
    const checkboxes = document.querySelectorAll(`input[data-group="${group}"]:checked`);
    const count = checkboxes.length;
    document.getElementById(`selectedCount-${group}`).textContent = count;
    
    const bulkBtn = document.getElementById(`bulkBtn-${group}`);
    if (bulkBtn) {
        bulkBtn.disabled = count === 0;
    }
}

// Select all in group
function selectAllInGroup(group) {
    const checkboxes = document.querySelectorAll(`input[data-group="${group}"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });
    updateSelection(group);
}

// Open bulk review modal
function openBulkReview(group) {
    const checkboxes = document.querySelectorAll(`input[data-group="${group}"]:checked`);
    selectedExpenses = Array.from(checkboxes).map(cb => parseInt(cb.dataset.expenseId));
    
    document.getElementById('bulkSelectedCount').textContent = selectedExpenses.length;
    document.getElementById('bulkPreviewCount').textContent = selectedExpenses.length;
    
    new bootstrap.Modal(document.getElementById('bulkReviewModal')).show();
}

// Bulk resolve
function bulkResolve() {
    const form = document.getElementById('bulkReviewForm');
    const formData = new FormData(form);
    
    const data = {
        expense_ids: selectedExpenses
    };
    
    if (formData.get('vendor_id')) data.vendor_id = formData.get('vendor_id');
    if (formData.get('coa_id')) data.coa_id = formData.get('coa_id');
    if (formData.get('create_mapping_rule')) data.create_mapping_rule = true;
    if (formData.get('notes')) data.notes = formData.get('notes');

    if (!data.vendor_id || !data.coa_id) {
        showToast('Please select both vendor and category', 'error');
        return;
    }

    setButtonLoading('bulkResolveBtn', true);

    fetch('/api/expenses/bulk-resolve', {
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
        if (result.error) {
            showToast(result.error, 'error');
        } else {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('bulkReviewModal')).hide();
            loadReviewQueue();
        }
        setButtonLoading('bulkResolveBtn', false);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error resolving transactions', 'error');
        setButtonLoading('bulkResolveBtn', false);
    });
}

// Refresh queue
function refreshQueue() {
    loadReviewQueue();
}

// Load vendors
function loadVendors() {
    fetch('/api/vendors?per_page=1000', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        allVendors = data.data || [];
        const selects = ['reviewVendor', 'bulkVendor'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.innerHTML = '<option value="">Select Vendor</option>';
            allVendors.forEach(vendor => {
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
        allCOAs = data.data || [];
        const selects = ['reviewCoa', 'bulkCoa'];
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.innerHTML = '<option value="">Select Category</option>';
            allCOAs.forEach(coa => {
                const option = document.createElement('option');
                option.value = coa.id;
                option.textContent = `${coa.account_code} - ${coa.account_name}`;
                select.appendChild(option);
            });
        });
    })
    .catch(error => console.error('Error loading COAs:', error));
}

// Utility functions
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
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
</script>
@endpush

