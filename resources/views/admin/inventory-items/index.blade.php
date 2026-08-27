@extends('layouts.tabler')

@section('title', 'Inventory Items')

@php
    $sortLink = function (string $column, string $label) use ($sort, $direction) {
        $next = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $arrow = $sort === $column ? ($direction === 'asc' ? ' ↑' : ' ↓') : '';
        $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next]);
        return '<a href="'.e($url).'" class="text-decoration-none text-reset">'.e($label).$arrow.'</a>';
    };
@endphp

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Inventory Items</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">
                {{ $store->store_info }} &middot; the master list every order and variance figure is built from
            </p>
        </div>
        <div class="d-flex" style="gap: 0.5rem;">
            <a href="{{ route('admin.inventory-items.import', ['store_id' => $store->id]) }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Bulk Import
            </a>
            <button class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;" onclick="openCreateModal()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                Add Item
            </button>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @if($unmappedCount > 0)
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
        <span>
            <strong>{{ $unmappedCount }}</strong> active {{ Str::plural('item', $unmappedCount) }}
            {{ $unmappedCount === 1 ? 'has' : 'have' }} no vendor yet, so
            {{ $unmappedCount === 1 ? 'it will' : 'they will' }} be left off generated orders.
            This is fine while the list is still being set up.
        </span>
        <a class="btn btn-sm btn-outline-secondary"
           href="{{ route('admin.inventory-items.index', array_merge(request()->query(), ['store_id' => $store->id, 'unmapped' => 1])) }}">
            Show them
        </a>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.inventory-items.index') }}" method="GET" class="row g-3">
                @if($stores->isNotEmpty())
                <div class="col-md-3">
                    <label class="form-label">Store</label>
                    <select class="form-select" name="store_id" onchange="this.form.submit()">
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}" {{ $s->id === $store->id ? 'selected' : '' }}>{{ $s->store_info }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="inventory_category_id">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) request('inventory_category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Vendor</label>
                    <select class="form-select" name="vendor_id">
                        <option value="">All vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ (string) request('vendor_id') === (string) $vendor->id ? 'selected' : '' }}>{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active">
                        <option value="">All</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" placeholder="Item name..." value="{{ request('search') }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card" id="bulkBar" style="display: none;">
        <div class="card-body d-flex align-items-center flex-wrap" style="gap: 0.75rem;">
            <strong><span id="bulkCount">0</span> selected</strong>
            <select class="form-select form-select-sm" id="bulkVendorId" style="max-width: 260px;">
                <option value="">Assign to vendor...</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->vendor_name }}</option>
                @endforeach
            </select>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="bulkMakePreferred">
                <label class="form-check-label small" for="bulkMakePreferred">Also set as preferred</label>
            </div>
            <button class="btn btn-sm btn-primary" onclick="bulkAssign()">Assign</button>
            <button class="btn btn-sm btn-link text-muted" onclick="clearSelection()">Clear</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 0.875rem;">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa); border-bottom: 2px solid var(--google-grey-200, #e8eaed);">
                        <tr>
                            <th style="padding: 1rem; width: 36px;">
                                <input type="checkbox" class="form-check-input" id="selectAll" title="Select all on this page">
                            </th>
                            <th style="padding: 1rem;">{!! $sortLink('name', 'Name') !!}</th>
                            <th style="padding: 1rem;">{!! $sortLink('category', 'Category') !!}</th>
                            <th style="padding: 1rem;">Unit</th>
                            <th style="padding: 1rem; text-align: right;">{!! $sortLink('units_per_purchase', 'Portions / Unit') !!}</th>
                            <th style="padding: 1rem;">Portion</th>
                            <th style="padding: 1rem;">Vendors</th>
                            <th style="padding: 1rem;">{!! $sortLink('is_active', 'Status') !!}</th>
                            <th style="padding: 1rem; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                        <tr>
                            <td style="padding: 1rem; vertical-align: middle;">
                                <input type="checkbox" class="form-check-input item-select" value="{{ $item->id }}">
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                <strong>{{ $item->name }}</strong>
                                @if($item->vendors->isEmpty())
                                    <span class="badge bg-warning text-dark" title="No vendor mapped">no vendor</span>
                                @endif
                                @if($item->notes)
                                    <div><small class="text-muted">{{ Str::limit($item->notes, 60) }}</small></div>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                {{ $item->inventoryCategory?->name ?? $item->category ?? '—' }}
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">{{ $item->purchase_unit }}</td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: right;">
                                <strong>{{ rtrim(rtrim(number_format((float) $item->units_per_purchase, 4, '.', ''), '0'), '.') }}</strong>
                                <div><small class="text-muted">{{ $item->base_unit }} per {{ $item->purchase_unit }}</small></div>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @if($item->portion_size)
                                    {{ rtrim(rtrim(number_format((float) $item->portion_size, 2, '.', ''), '0'), '.') }} {{ $item->portion_unit }}
                                    <div><small class="text-muted">{{ $item->portion_total }} {{ $item->portion_unit }} per {{ $item->purchase_unit }}</small></div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                @forelse($item->vendors as $vendor)
                                    <a href="{{ route('admin.vendors.index', ['search' => $vendor->vendor_name]) }}"
                                       class="badge {{ $vendor->pivot->is_preferred_vendor ? 'bg-primary' : 'bg-light text-dark' }} text-decoration-none"
                                       title="{{ $vendor->pivot->is_preferred_vendor ? 'Preferred vendor' : 'Also supplies this item' }}">{{ $vendor->vendor_name }}</a>
                                @empty
                                    <span class="text-muted">No vendor</span>
                                @endforelse
                            </td>
                            <td style="padding: 1rem; vertical-align: middle;">
                                <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-danger' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td style="padding: 1rem; vertical-align: middle; text-align: center;">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editItem({{ $item->id }})" title="Edit">Edit</button>
                                    <button class="btn btn-outline-danger" onclick="openDeleteModal({{ $item->id }}, @js($item->name))" title="Hide item">Hide</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No items found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-pagination :paginator="$items" />
        </div>
    </div>
</div>

@include('admin.inventory-items._form-modal')

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1080;"></div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Hide Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Hide <strong id="deleteItemName">this item</strong>?</p>
                <p class="text-muted mb-0">It drops off the count sheet and order guide. Past counts, orders and recipes keep their numbers.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Hide item</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const STORE_ID = {{ $store->id }};
let deleteItemId = null;

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : 'success'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `<div class="d-flex"><div class="toast-body"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
    toast.querySelector('.toast-body').textContent = message;
    container.appendChild(toast);
    const instance = new bootstrap.Toast(toast, { delay: 3000 });
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
    instance.show();
}

function request(url, method, body) {
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf()
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : undefined
    }).then(response => response.json().then(data => ({ status: response.status, data })));
}

// ---- Create / edit --------------------------------------------------------
function openCreateModal() {
    document.getElementById('itemModalLabel').textContent = 'Add Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemId').value = '';
    document.getElementById('isActive').checked = true;
    clearErrors();
    resetVendorMapping();
    updatePortionHint();
    new bootstrap.Modal(document.getElementById('itemModal')).show();
}

// ---- Vendor mapping table -------------------------------------------------
function vendorRows() {
    return Array.from(document.querySelectorAll('#vendorMappingTable tr[data-vendor-row]'));
}

function rowInputs(vendorId) {
    return {
        enabled: document.querySelector(`.vendor-enabled[data-vendor="${vendorId}"]`),
        sku: document.querySelector(`.vendor-sku[data-vendor="${vendorId}"]`),
        price: document.querySelector(`.vendor-price[data-vendor="${vendorId}"]`),
        preferred: document.querySelector(`.vendor-preferred[data-vendor="${vendorId}"]`),
        stamp: document.querySelector(`.vendor-price-stamp[data-stamp-for="${vendorId}"]`)
    };
}

function resetVendorMapping() {
    vendorRows().forEach(row => {
        const inputs = rowInputs(row.dataset.vendorRow);
        inputs.enabled.checked = false;
        inputs.sku.value = '';
        inputs.price.value = '';
        inputs.preferred.checked = false;
        inputs.stamp.textContent = '';
        applyRowState(row.dataset.vendorRow);
    });
    updateVendorWarning();
}

// SKU, price and preferred only mean anything for a ticked vendor.
function applyRowState(vendorId) {
    const inputs = rowInputs(vendorId);
    const on = inputs.enabled.checked;

    inputs.sku.disabled = !on;
    inputs.price.disabled = !on;
    inputs.preferred.disabled = !on;

    if (!on) {
        inputs.sku.value = '';
        inputs.price.value = '';
        // Unticking the preferred vendor must clear the flag, not leave a
        // preferred vendor that no longer supplies the item.
        inputs.preferred.checked = false;
    }
}

function updateVendorWarning() {
    const any = vendorRows().some(row => rowInputs(row.dataset.vendorRow).enabled.checked);
    document.getElementById('noVendorWarning').classList.toggle('d-none', any);
}

document.addEventListener('change', function (event) {
    if (event.target.classList.contains('vendor-enabled')) {
        applyRowState(event.target.dataset.vendor);
        updateVendorWarning();
    }
    // Ticking preferred implies the vendor supplies the item.
    if (event.target.classList.contains('vendor-preferred') && event.target.checked) {
        const inputs = rowInputs(event.target.dataset.vendor);
        if (!inputs.enabled.checked) {
            inputs.enabled.checked = true;
            applyRowState(event.target.dataset.vendor);
            inputs.preferred.checked = true;
            updateVendorWarning();
        }
    }
});

function collectVendorMapping() {
    const mapping = {};
    vendorRows().forEach(row => {
        const vendorId = row.dataset.vendorRow;
        const inputs = rowInputs(vendorId);
        if (!inputs.enabled.checked) return;
        mapping[vendorId] = {
            enabled: true,
            vendor_sku: inputs.sku.value || null,
            current_price: inputs.price.value === '' ? null : inputs.price.value,
            is_preferred: inputs.preferred.checked
        };
    });
    return mapping;
}

async function editItem(id) {
    const response = await fetch(`/inventory-items/${id}`, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
        credentials: 'same-origin'
    });

    if (!response.ok) {
        showToast('Could not load that item', 'error');
        return;
    }

    const item = await response.json();
    clearErrors();

    document.getElementById('itemModalLabel').textContent = 'Edit Item';
    document.getElementById('itemId').value = item.id;
    document.getElementById('name').value = item.name;
    document.getElementById('inventoryCategoryId').value = item.inventory_category_id || '';
    document.getElementById('purchaseUnit').value = item.purchase_unit || '';
    document.getElementById('baseUnit').value = item.base_unit || '';
    document.getElementById('unitsPerPurchase').value = trimNumber(item.units_per_purchase);
    document.getElementById('portionSize').value = item.portion_size ? trimNumber(item.portion_size) : '';
    document.getElementById('portionUnit').value = item.portion_unit || '';
    document.getElementById('minStockLevel').value = trimNumber(item.min_stock_level);
    document.getElementById('notes').value = item.notes || '';
    document.getElementById('isActive').checked = !!item.is_active;

    resetVendorMapping();
    (item.vendors || []).forEach(vendor => {
        const inputs = rowInputs(vendor.id);
        if (!inputs.enabled) return; // vendor is inactive, so it has no row
        inputs.enabled.checked = true;
        applyRowState(vendor.id);
        inputs.sku.value = vendor.pivot?.vendor_sku || '';
        inputs.price.value = vendor.pivot?.current_price ? trimNumber(vendor.pivot.current_price) : '';
        inputs.preferred.checked = !!vendor.pivot?.is_preferred_vendor;
        inputs.stamp.textContent = vendor.pivot?.price_updated_at
            ? `price updated ${String(vendor.pivot.price_updated_at).slice(0, 10)}`
            : '';
    });
    updateVendorWarning();

    updatePortionHint();
    new bootstrap.Modal(document.getElementById('itemModal')).show();
}

function trimNumber(value) {
    if (value === null || value === undefined || value === '') return '';
    return String(parseFloat(value));
}

function clearErrors() {
    document.querySelectorAll('#itemForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#itemForm .invalid-feedback').forEach(el => el.textContent = '');
}

function showErrors(errors) {
    clearErrors();
    Object.entries(errors).forEach(([field, messages]) => {
        const input = document.querySelector(`#itemForm [name="${field}"]`);
        if (!input) return;
        input.classList.add('is-invalid');
        const feedback = input.parentElement.querySelector('.invalid-feedback');
        if (feedback) feedback.textContent = messages[0];
    });
}

// Live readout so a wrong pack size is visible before it is saved.
function updatePortionHint() {
    const per = parseFloat(document.getElementById('unitsPerPurchase').value);
    const size = parseFloat(document.getElementById('portionSize').value);
    const portionUnit = document.getElementById('portionUnit').value.trim();
    const baseUnit = document.getElementById('baseUnit').value.trim() || 'base unit';
    const purchaseUnit = document.getElementById('purchaseUnit').value.trim() || 'unit';
    const hint = document.getElementById('portionHint');

    if (!per || isNaN(per)) {
        hint.textContent = '';
        return;
    }

    let text = `1 ${purchaseUnit} = ${per} ${baseUnit}`;
    if (size && !isNaN(size) && portionUnit) {
        text += ` · ${per} × ${size} ${portionUnit} = ${(per * size).toFixed(2)} ${portionUnit} per ${purchaseUnit}`;
    }
    hint.textContent = text;
}

['unitsPerPurchase', 'portionSize', 'portionUnit', 'baseUnit', 'purchaseUnit'].forEach(id => {
    document.getElementById(id).addEventListener('input', updatePortionHint);
});

document.getElementById('itemForm').addEventListener('submit', function (event) {
    event.preventDefault();

    const id = document.getElementById('itemId').value;
    const payload = {
        store_id: STORE_ID,
        name: document.getElementById('name').value,
        inventory_category_id: document.getElementById('inventoryCategoryId').value || null,
        purchase_unit: document.getElementById('purchaseUnit').value,
        base_unit: document.getElementById('baseUnit').value,
        units_per_purchase: document.getElementById('unitsPerPurchase').value,
        portion_size: document.getElementById('portionSize').value || null,
        portion_unit: document.getElementById('portionUnit').value || null,
        min_stock_level: document.getElementById('minStockLevel').value || 0,
        vendors: collectVendorMapping(),
        notes: document.getElementById('notes').value,
        is_active: document.getElementById('isActive').checked
    };

    request(id ? `/inventory-items/${id}` : '{{ route('admin.inventory-items.store') }}', id ? 'PUT' : 'POST', payload)
        .then(({ status, data }) => {
            if (status === 422) {
                showErrors(data.errors || {});
                return;
            }
            window.location.reload();
        })
        .catch(() => showToast('Error saving item', 'error'));
});

// ---- Bulk vendor assignment ----------------------------------------------
function selectedItemIds() {
    return Array.from(document.querySelectorAll('.item-select:checked')).map(box => parseInt(box.value, 10));
}

function refreshBulkBar() {
    const count = selectedItemIds().length;
    document.getElementById('bulkCount').textContent = count;
    document.getElementById('bulkBar').style.display = count > 0 ? '' : 'none';
}

function clearSelection() {
    document.querySelectorAll('.item-select').forEach(box => { box.checked = false; });
    document.getElementById('selectAll').checked = false;
    refreshBulkBar();
}

document.getElementById('selectAll').addEventListener('change', function () {
    document.querySelectorAll('.item-select').forEach(box => { box.checked = this.checked; });
    refreshBulkBar();
});

document.querySelectorAll('.item-select').forEach(box => {
    box.addEventListener('change', refreshBulkBar);
});

function bulkAssign() {
    const vendorId = document.getElementById('bulkVendorId').value;
    const itemIds = selectedItemIds();

    if (!vendorId) {
        showToast('Pick a vendor first', 'error');
        return;
    }
    if (itemIds.length === 0) {
        showToast('Select at least one item', 'error');
        return;
    }

    request('{{ route('admin.inventory-items.bulk-assign-vendor') }}', 'POST', {
        store_id: STORE_ID,
        vendor_id: parseInt(vendorId, 10),
        item_ids: itemIds,
        make_preferred: document.getElementById('bulkMakePreferred').checked
    })
        .then(({ status, data }) => {
            if (status !== 200) {
                showToast(data.error || 'Could not assign that vendor', 'error');
                return;
            }
            window.location.reload();
        })
        .catch(() => showToast('Error assigning vendor', 'error'));
}

// ---- Hide -----------------------------------------------------------------
function openDeleteModal(id, name) {
    deleteItemId = id;
    document.getElementById('deleteItemName').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    request(`/inventory-items/${deleteItemId}`, 'DELETE')
        .then(() => window.location.reload())
        .catch(() => showToast('Error hiding item', 'error'));
});
</script>
@endpush
