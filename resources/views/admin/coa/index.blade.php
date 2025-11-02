@extends('layouts.tabler')

@section('title', 'Chart of Accounts')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Chart of Accounts</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage your accounting categories</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;" data-bs-toggle="modal" data-bs-target="#coaModal" onclick="openCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Account
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.coa.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Account Type</label>
                    <select class="form-select" name="account_type">
                        <option value="">All Types</option>
                        <option value="Revenue" {{ request('account_type') == 'Revenue' ? 'selected' : '' }}>Revenue</option>
                        <option value="COGS" {{ request('account_type') == 'COGS' ? 'selected' : '' }}>COGS</option>
                        <option value="Expense" {{ request('account_type') == 'Expense' ? 'selected' : '' }}>Expense</option>
                        <option value="Other Income" {{ request('account_type') == 'Other Income' ? 'selected' : '' }}>Other Income</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active">
                        <option value="">All Status</option>
                        <option value="1" {{ request('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" placeholder="Search by name or code..." value="{{ request('search') }}">
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

    <!-- COA Table -->
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">All Accounts</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 0.875rem;">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                        <tr>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Code</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Account Name</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Type</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Stores</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Status</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coas as $coa)
                        <tr>
                            <td style="padding: 1rem; vertical-align: middle;"><strong>{{ $coa->account_code }}</strong></td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                {{ $coa->account_name }}
                                @if($coa->is_system_account)
                                    <span class="badge bg-info ms-2">System</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;"><span class="badge bg-secondary">{{ $coa->account_type }}</span></td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($coa->stores->count() === 0)
                                    <span class="text-muted">All Stores</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ $coa->stores->count() }} store(s)</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($coa->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: center;">
                                <div class="btn-group btn-group-sm" role="group">
                                    @if(!$coa->is_system_account)
                                    <button class="btn btn-outline-primary" onclick="editCOA({{ $coa->id }})" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteCOA({{ $coa->id }})" title="Deactivate">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                    </button>
                                    @else
                                    <span class="text-muted">System</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No accounts found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="card-footer">
                {{ $coas->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="coaModal" tabindex="-1" aria-labelledby="coaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="coaModalLabel">Add Chart of Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="coaForm">
                <div class="modal-body">
                    <input type="hidden" id="coaId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="accountCode" class="form-label">Account Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="accountCode" name="account_code" required maxlength="10">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="accountType" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="accountType" name="account_type" required>
                                <option value="">Select Type</option>
                                <option value="Revenue">Revenue</option>
                                <option value="COGS">COGS</option>
                                <option value="Expense">Expense</option>
                                <option value="Other Income">Other Income</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="accountName" class="form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="accountName" name="account_name" required maxlength="100">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="parentAccount" class="form-label">Parent Account (Optional)</label>
                        <select class="form-select" id="parentAccount" name="parent_account_id">
                            <option value="">None (Top Level)</option>
                            <!-- Options loaded via JavaScript -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Store Assignment</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="isGlobal" name="is_global">
                            <label class="form-check-label" for="isGlobal">
                                Available to All Stores
                            </label>
                        </div>
                        <div id="storeSelection" class="border rounded p-3">
                            <div class="text-muted text-center">Loading stores...</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deactivation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate this account?</p>
                <p class="text-muted mb-0">This action can be reversed by editing the account later.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Deactivate</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Global variables
let deleteCoaId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadStores();
    loadParentAccounts();
    
    // COA form submission
    document.getElementById('coaForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveCOA();
    });

    // Delete confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        deleteCOA(deleteCoaId);
    });

    // Toggle store selection based on global checkbox
    document.getElementById('isGlobal').addEventListener('change', function() {
        document.getElementById('storeSelection').style.display = this.checked ? 'none' : 'block';
    });
});

// Open create modal
function openCreateModal() {
    document.getElementById('coaModalLabel').textContent = 'Add Chart of Account';
    document.getElementById('coaForm').reset();
    document.getElementById('coaId').value = '';
    clearValidationErrors();
}

// Edit COA
function editCOA(id) {
    fetch(`/api/coa/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(coa => {
        document.getElementById('coaModalLabel').textContent = 'Edit Chart of Account';
        document.getElementById('coaId').value = coa.id;
        document.getElementById('accountCode').value = coa.account_code;
        document.getElementById('accountName').value = coa.account_name;
        document.getElementById('accountType').value = coa.account_type;
        document.getElementById('parentAccount').value = coa.parent_account_id || '';
        document.getElementById('isActive').checked = coa.is_active;
        
        const isGlobal = coa.stores.length === 0;
        document.getElementById('isGlobal').checked = isGlobal;
        document.getElementById('storeSelection').style.display = isGlobal ? 'none' : 'block';
        
        if (!isGlobal) {
            const storeIds = coa.stores.map(s => s.id);
            document.querySelectorAll('input[name="store_ids[]"]').forEach(checkbox => {
                checkbox.checked = storeIds.includes(parseInt(checkbox.value));
            });
        }
        
        new bootstrap.Modal(document.getElementById('coaModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error loading account details', 'error');
    });
}

// Save COA
function saveCOA() {
    const form = document.getElementById('coaForm');
    const formData = new FormData(form);
    const id = document.getElementById('coaId').value;
    
    const data = {
        account_code: formData.get('account_code'),
        account_name: formData.get('account_name'),
        account_type: formData.get('account_type'),
        parent_account_id: formData.get('parent_account_id') || null,
        is_global: formData.get('is_global') === 'on',
        is_active: formData.get('is_active') === 'on',
        store_ids: formData.getAll('store_ids[]').map(id => parseInt(id))
    };
    
    const url = id ? `/api/coa/${id}` : '/api/coa';
    const method = id ? 'PUT' : 'POST';
    
    setButtonLoading('saveBtn', true);
    
    fetch(url, {
        method: method,
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
        showToast('Error saving account', 'error');
        setButtonLoading('saveBtn', false);
    });
}

// Delete modal
function openDeleteModal(id) {
    deleteCoaId = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Delete COA
function deleteCOA(id) {
    fetch(`/api/coa/${id}`, {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(result => {
        showToast(result.message, result.error ? 'error' : 'success');
        if (!result.error) {
            setTimeout(() => window.location.reload(), 500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error deleting account', 'error');
    });
}

// Load stores for assignment
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
        const container = document.getElementById('storeSelection');
        container.innerHTML = '';
        stores.forEach(store => {
            container.innerHTML += `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="store_ids[]" value="${store.id}" id="store${store.id}">
                    <label class="form-check-label" for="store${store.id}">${store.name}</label>
                </div>
            `;
        });
    })
    .catch(error => {
        console.error('Error loading stores:', error);
        document.getElementById('storeSelection').innerHTML = '<div class="text-danger">Error loading stores</div>';
    });
}

// Load parent accounts
function loadParentAccounts() {
    fetch('/api/coa?per_page=1000', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById('parentAccount');
        // Clear existing options except the first one
        while (select.options.length > 1) {
            select.remove(1);
        }
        data.data.forEach(coa => {
            const option = document.createElement('option');
            option.value = coa.id;
            option.textContent = `${coa.account_code} - ${coa.account_name}`;
            select.appendChild(option);
        });
    })
    .catch(error => {
        console.error('Error loading parent accounts:', error);
    });
}

// Utility functions
function showToast(message, type = 'success') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

function setButtonLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    const spinner = btn.querySelector('.spinner-border');
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

