@extends('layouts.tabler')

@section('title', 'Vendors')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Vendors</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage your suppliers and vendors</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;" data-bs-toggle="modal" data-bs-target="#vendorModal" onclick="openCreateModal()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Vendor
        </button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.vendors.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Vendor Type</label>
                    <select class="form-select" name="vendor_type">
                        <option value="">All Types</option>
                        <option value="Food" {{ request('vendor_type') == 'Food' ? 'selected' : '' }}>Food</option>
                        <option value="Beverage" {{ request('vendor_type') == 'Beverage' ? 'selected' : '' }}>Beverage</option>
                        <option value="Supplies" {{ request('vendor_type') == 'Supplies' ? 'selected' : '' }}>Supplies</option>
                        <option value="Utilities" {{ request('vendor_type') == 'Utilities' ? 'selected' : '' }}>Utilities</option>
                        <option value="Services" {{ request('vendor_type') == 'Services' ? 'selected' : '' }}>Services</option>
                        <option value="Other" {{ request('vendor_type') == 'Other' ? 'selected' : '' }}>Other</option>
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
                    <input type="text" class="form-control" name="search" placeholder="Search by name or identifier..." value="{{ request('search') }}">
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

    <!-- Vendors Table -->
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">All Vendors</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 0.875rem;">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                        <tr>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Name</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Type</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Default COA</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Stores</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem;">Status</th>
                            <th style="font-weight: 500; padding: 1rem; border: none; font-size: 0.813rem; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                        <tr>
                            <td style="padding: 1rem; vertical-align: middle;">
                                <div><strong>{{ $vendor->vendor_name }}</strong></div>
                                @if($vendor->vendor_identifier)
                                    <small class="text-muted">{{ $vendor->vendor_identifier }}</small>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;"><span class="badge bg-secondary">{{ $vendor->vendor_type }}</span></td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($vendor->defaultCoa)
                                    {{ $vendor->defaultCoa->account_code }} - {{ $vendor->defaultCoa->account_name }}
                                @else
                                    <span class="text-muted">Not assigned</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($vendor->stores->count() === 0)
                                    <span class="text-muted">All Stores</span>
                                @else
                                    <span class="badge bg-light text-dark">{{ $vendor->stores->count() }} store(s)</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($vendor->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: center;">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-primary" onclick="editVendor({{ $vendor->id }})" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deleteVendor({{ $vendor->id }})" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                            <line x1="10" y1="11" x2="10" y2="17"/>
                                            <line x1="14" y1="11" x2="14" y2="17"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No vendors found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <x-pagination :paginator="$vendors" />
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="vendorModal" tabindex="-1" aria-labelledby="vendorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vendorModalLabel">Add Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="vendorForm">
                <div class="modal-body">
                    <input type="hidden" id="vendorId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vendorName" class="form-label">Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vendorName" name="vendor_name" required maxlength="100">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vendorType" class="form-label">Vendor Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="vendorType" name="vendor_type" required>
                                <option value="">Select Type</option>
                                <option value="Food">Food</option>
                                <option value="Beverage">Beverage</option>
                                <option value="Supplies">Supplies</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Services">Services</option>
                                <option value="Other">Other</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="vendorIdentifier" class="form-label">Vendor Identifier</label>
                        <input type="text" class="form-control" id="vendorIdentifier" name="vendor_identifier" maxlength="100" placeholder="For CSV matching (e.g., SAMSCLUB)">
                        <small class="text-muted">Optional: Used to match bank/credit card transactions</small>
                    </div>

                    <div class="mb-3">
                        <label for="defaultCoa" class="form-label">Default COA Category</label>
                        <input type="text" class="form-control mb-2" id="defaultCoaSearch" placeholder="Search COA by code or name (e.g., 5100, Rent)" autocomplete="off">
                        <select class="form-select" id="defaultCoa" name="default_coa_id">
                            <option value="">Loading chart of accounts...</option>
                            <!-- Options loaded via JavaScript -->
                        </select>
                        <small class="text-muted">Auto-assign this COA to transactions from this vendor. Type to search and filter options from all chart of accounts.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Store Assignment</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="isGlobal" name="is_global" checked>
                            <label class="form-check-label" for="isGlobal">
                                Available to All Stores
                            </label>
                        </div>
                        <div id="storeSelection" class="border rounded p-3">
                            <div class="text-muted text-center">Loading stores...</div>
                        </div>
                    </div>

                    <!-- Collapsible Contact Information -->
                    <div class="mb-3">
                        <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#contactSection">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            Contact Information
                        </button>
                        <div class="collapse" id="contactSection">
                            <div class="card card-body mt-2">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="contactName" class="form-label">Contact Name</label>
                                        <input type="text" class="form-control" id="contactName" name="contact_name" maxlength="100">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contactEmail" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="contactEmail" name="contact_email" maxlength="100">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="contactPhone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="contactPhone" name="contact_phone" maxlength="50">
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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
                        Save Vendor
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
                <p>Are you sure you want to deactivate this vendor?</p>
                <p class="text-muted mb-0">This action can be reversed by editing the vendor later.</p>
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
let deleteVendorId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadStores();
    loadCOAs();
    
    // Vendor form submission
    document.getElementById('vendorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveVendor();
    });

    // Delete confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        deleteVendor(deleteVendorId);
    });

    // Toggle store selection based on global checkbox
    document.getElementById('isGlobal').addEventListener('change', function() {
        document.getElementById('storeSelection').style.display = this.checked ? 'none' : 'block';
    });

    // Auto-suggest COA based on vendor type
    document.getElementById('vendorType').addEventListener('change', function() {
        suggestCOA(this.value);
    });

    // COA search filter (by code or name)
    const coaSearch = document.getElementById('defaultCoaSearch');
    if (coaSearch) {
        coaSearch.addEventListener('input', function() {
            renderCoaOptions();
        });
        
        // Clear search when modal is opened for create
        const vendorModal = document.getElementById('vendorModal');
        if (vendorModal) {
            vendorModal.addEventListener('show.bs.modal', function() {
                if (!document.getElementById('vendorId').value) {
                    // Create mode - clear search
                    coaSearch.value = '';
                    renderCoaOptions();
                }
            });
        }
    }
});


// Open create modal
function openCreateModal() {
    document.getElementById('vendorModalLabel').textContent = 'Add Vendor';
    document.getElementById('vendorForm').reset();
    document.getElementById('vendorId').value = '';
    document.getElementById('defaultCoaSearch').value = '';
    clearValidationErrors();
    // Ensure COAs are rendered
    if (window.__coaCache) {
        renderCoaOptions();
    }
}

// Edit vendor
async function editVendor(id) {
    try {
        // Ensure COAs are loaded before opening edit modal
        if (!window.__coaCache || window.__coaCache.length === 0) {
            await loadCOAs();
        }
        
        const response = await fetch(`/api/vendors/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const vendor = await response.json();
        
        document.getElementById('vendorModalLabel').textContent = 'Edit Vendor';
        document.getElementById('vendorId').value = vendor.id;
        document.getElementById('vendorName').value = vendor.vendor_name;
        document.getElementById('vendorIdentifier').value = vendor.vendor_identifier || '';
        document.getElementById('vendorType').value = vendor.vendor_type;
        
        // Clear search field when editing
        document.getElementById('defaultCoaSearch').value = '';
        
        // Set default COA - ensure all options are rendered first
        const defaultCoaId = vendor.default_coa_id || '';
        
        // Render all COA options (without search filter)
        renderCoaOptions();
        
        // Set the selected COA value after options are rendered
        // Use a small delay to ensure DOM is updated
        setTimeout(() => {
            const coaSelect = document.getElementById('defaultCoa');
            if (defaultCoaId) {
                // Check if the option exists
                const optionExists = Array.from(coaSelect.options).some(opt => opt.value === String(defaultCoaId));
                if (optionExists) {
                    coaSelect.value = defaultCoaId;
                } else {
                    // If COA not found in cache, it might have been deleted
                    console.warn(`COA with ID ${defaultCoaId} not found in available options`);
                    coaSelect.value = '';
                }
            } else {
                coaSelect.value = '';
            }
        }, 50);
        
        document.getElementById('isActive').checked = vendor.is_active;
        
        // Handle contact info
        document.getElementById('contactName').value = vendor.contact_name || '';
        document.getElementById('contactEmail').value = vendor.contact_email || '';
        document.getElementById('contactPhone').value = vendor.contact_phone || '';
        document.getElementById('address').value = vendor.address || '';
        document.getElementById('notes').value = vendor.notes || '';
        
        const isGlobal = vendor.stores.length === 0;
        document.getElementById('isGlobal').checked = isGlobal;
        document.getElementById('storeSelection').style.display = isGlobal ? 'none' : 'block';
        
        if (!isGlobal) {
            const storeIds = vendor.stores.map(s => s.id);
            document.querySelectorAll('input[name="store_ids[]"]').forEach(checkbox => {
                checkbox.checked = storeIds.includes(parseInt(checkbox.value));
            });
        }
        
        // Clear any validation errors
        clearValidationErrors();
        
        // Show the modal
        new bootstrap.Modal(document.getElementById('vendorModal')).show();
    } catch (error) {
        console.error('Error loading vendor:', error);
        showToast('Error loading vendor details: ' + error.message, 'error');
    }
}

// Save vendor
function saveVendor() {
    const form = document.getElementById('vendorForm');
    const formData = new FormData(form);
    const id = document.getElementById('vendorId').value;
    
    const data = {
        vendor_name: formData.get('vendor_name'),
        vendor_identifier: formData.get('vendor_identifier'),
        vendor_type: formData.get('vendor_type'),
        default_coa_id: formData.get('default_coa_id') || null,
        is_global: formData.get('is_global') === 'on',
        is_active: formData.get('is_active') === 'on',
        contact_name: formData.get('contact_name'),
        contact_email: formData.get('contact_email'),
        contact_phone: formData.get('contact_phone'),
        address: formData.get('address'),
        notes: formData.get('notes'),
        store_ids: formData.getAll('store_ids[]').map(id => parseInt(id))
    };
    
    const url = id ? `/api/vendors/${id}` : '/api/vendors';
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
        showToast('Error saving vendor', 'error');
        setButtonLoading('saveBtn', false);
    });
}

// Delete modal
function openDeleteModal(id) {
    deleteVendorId = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Delete vendor
function deleteVendor(id) {
    fetch(`/api/vendors/${id}`, {
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
        showToast('Error deleting vendor', 'error');
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

// Load COAs - fetch all chart of accounts (active and inactive)
async function loadCOAs() {
    try {
        let allCOAs = [];
        
        // Try to fetch all COAs with a very high per_page value first
        const response = await fetch(`/api/coa-list?per_page=10000`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        console.log('COA API Response:', data);
        
        // Handle Laravel pagination response structure
        if (data.data && Array.isArray(data.data)) {
            allCOAs = data.data;
            const totalPages = data.last_page || 1;
            const total = data.total || data.data.length;
            
            console.log(`Found ${total} total COAs across ${totalPages} page(s), loaded ${data.data.length} from first page`);
            
            // If there are more pages, fetch them
            if (totalPages > 1) {
                console.log(`Fetching remaining ${totalPages - 1} page(s)...`);
                const pagePromises = [];
                for (let page = 2; page <= totalPages; page++) {
                    pagePromises.push(
                        fetch(`/api/coa-list?per_page=10000&page=${page}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            credentials: 'same-origin'
                        }).then(res => {
                            if (!res.ok) {
                                throw new Error(`HTTP error! status: ${res.status}`);
                            }
                            return res.json();
                        })
                    );
                }
                
                const pageResults = await Promise.all(pagePromises);
                pageResults.forEach((pageData, index) => {
                    if (pageData.data && Array.isArray(pageData.data)) {
                        console.log(`Loaded ${pageData.data.length} COAs from page ${index + 2}`);
                        allCOAs = allCOAs.concat(pageData.data);
                    }
                });
            }
        } else if (Array.isArray(data)) {
            // If response is a direct array (non-paginated)
            allCOAs = data;
            console.log(`Received direct array with ${data.length} COAs`);
        } else {
            console.error('Unexpected API response format:', data);
            throw new Error('Unexpected response format from API. Check console for details.');
        }
        
        console.log(`Total loaded: ${allCOAs.length} chart of accounts`);
        
        if (allCOAs.length === 0) {
            console.warn('No chart of accounts found');
            showToast('No chart of accounts found', 'warning');
        }
        
        // Store all COAs in cache
        window.__coaCache = allCOAs;
        
        // Sort by numeric code for easier selection
        window.__coaCache.sort((a, b) => {
            const codeA = parseInt(a.account_code, 10) || 0;
            const codeB = parseInt(b.account_code, 10) || 0;
            if (codeA !== codeB) {
                return codeA - codeB;
            }
            return a.account_name.localeCompare(b.account_name);
        });
        
        renderCoaOptions();
    } catch (error) {
        console.error('Error loading COAs:', error);
        showToast('Error loading chart of accounts: ' + error.message, 'error');
        
        // Show error in the select dropdown
        const select = document.getElementById('defaultCoa');
        if (select) {
            // Clear existing options except the first one
            while (select.options.length > 1) {
                select.remove(1);
            }
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Error loading chart of accounts. Please refresh the page.';
            option.disabled = true;
            select.appendChild(option);
        }
    }
}

function renderCoaOptions() {
    const select = document.getElementById('defaultCoa');
    const searchEl = document.getElementById('defaultCoaSearch');
    const selected = select.value; // preserve current selection

    const q = (searchEl?.value || '').trim().toLowerCase();

    // Clear all options
    select.innerHTML = '';
    
    // Always add the "None" option first
    const noneOption = document.createElement('option');
    noneOption.value = '';
    noneOption.textContent = 'None (Select Manually)';
    select.appendChild(noneOption);

    if (!window.__coaCache || window.__coaCache.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Loading chart of accounts...';
        option.disabled = true;
        select.appendChild(option);
        return;
    }

    let matchCount = 0;
    const maxDisplay = 200; // Increased limit for better usability

    (window.__coaCache || []).forEach(coa => {
        const code = String(coa.account_code || '').toLowerCase();
        const name = String(coa.account_name || '').toLowerCase();
        const label = `${coa.account_code} - ${coa.account_name}${coa.is_active === false ? ' (Inactive)' : ''}`;
        
        // Search in both code and name
        const matches = !q || 
            code.includes(q) || 
            name.includes(q) ||
            label.toLowerCase().includes(q);
        
        if (matches && matchCount < maxDisplay) {
            const option = document.createElement('option');
            option.value = coa.id;
            option.textContent = label;
            if (coa.is_active === false) {
                option.style.color = '#999';
            }
            select.appendChild(option);
            matchCount++;
        }
    });

    // Show message if results are limited
    if (q && matchCount >= maxDisplay) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = `... and more (showing first ${maxDisplay} of ${window.__coaCache.length} results)`;
        option.disabled = true;
        option.style.fontStyle = 'italic';
        select.appendChild(option);
    }

    // Show message if no results
    if (q && matchCount === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = `No matching chart of accounts found (searched ${window.__coaCache.length} accounts)`;
        option.disabled = true;
        select.appendChild(option);
    }
    
    // Show total count when no search
    if (!q && window.__coaCache.length > 0) {
        // Add a subtle indicator of total count (optional)
        // The options are already there, so we don't need to add anything
    }

    // restore selection if still present
    if (selected) {
        const optionExists = Array.from(select.options).some(opt => opt.value === selected);
        if (optionExists) {
            select.value = selected;
        }
    }
}

// Suggest COA based on vendor type
async function suggestCOA(vendorType) {
    const mapping = {
        // Updated for new seeded COA codes
        'Food': '5100',       // COGS - Food Purchases
        'Beverage': '5200',   // COGS - Beverage Purchases
        'Supplies': '5300',   // COGS - Packaging Supplies
        'Utilities': '6400',  // Utilities - Electric
        'Services': '6100'    // Merchant Processing Fees
    };
    
    if (mapping[vendorType]) {
        // Ensure COAs are loaded
        if (!window.__coaCache || window.__coaCache.length === 0) {
            await loadCOAs();
        }
        
        const suggested = (window.__coaCache || []).find(coa => String(coa.account_code) === String(mapping[vendorType]));
        if (suggested) {
            document.getElementById('defaultCoa').value = suggested.id;
            renderCoaOptions(); // Re-render to show the selected option
        }
    }
}

// Utility functions
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

