@extends('layouts.tabler')

@section('title', 'Inventory Categories')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Inventory Categories</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">
                The groups items are filed under on the weekly count and order guide. Shared by every store.
            </p>
        </div>
        <button class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;" onclick="openAddRow()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Category
        </button>
    </div>

    <div class="card">
        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Sort order</h3>
            <small class="text-muted">Drag a row by its handle to reorder. Click a name to rename it.</small>
        </div>
        <div class="card-body">
            <div id="addRow" class="d-none mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" id="newCategoryName" maxlength="100" placeholder="Category name, e.g. Frozen Goods">
                    <button class="btn btn-primary" onclick="createCategory()">Save</button>
                    <button class="btn btn-outline-secondary" onclick="closeAddRow()">Cancel</button>
                </div>
                <div class="text-danger small mt-1 d-none" id="newCategoryError"></div>
            </div>

            <ul class="list-group" id="categoryList">
                @forelse($categories as $category)
                <li class="list-group-item d-flex align-items-center" draggable="true"
                    data-id="{{ $category->id }}" data-name="{{ $category->name }}"
                    style="cursor: default; gap: 0.75rem;">
                    <span class="drag-handle text-muted" style="cursor: grab;" title="Drag to reorder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="6" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="18" r="1"/>
                            <circle cx="15" cy="6" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="18" r="1"/>
                        </svg>
                    </span>

                    <span class="flex-grow-1">
                        <span class="category-name" onclick="startRename({{ $category->id }})"
                              style="cursor: text;" title="Click to rename">{{ $category->name }}</span>
                        <input type="text" class="form-control form-control-sm d-none category-input"
                               value="{{ $category->name }}" maxlength="100"
                               onblur="commitRename({{ $category->id }})"
                               onkeydown="renameKey(event, {{ $category->id }})">
                    </span>

                    <span class="text-muted small">
                        @if($category->active_items_count > 0)
                            <span class="badge bg-light text-dark">{{ $category->active_items_count }} active</span>
                        @else
                            <span class="badge bg-light text-muted">no active items</span>
                        @endif
                        @if($category->items_count > $category->active_items_count)
                            <span class="badge bg-light text-muted">{{ $category->items_count - $category->active_items_count }} inactive</span>
                        @endif
                    </span>

                    <button class="btn btn-sm btn-outline-danger" onclick="openDeleteModal({{ $category->id }}, @js($category->name))" title="Delete category">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                    </button>
                </li>
                @empty
                <li class="list-group-item text-center text-muted py-4" id="emptyRow">No categories yet</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1080;"></div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Delete <strong id="deleteCategoryName">this category</strong>?</p>
                <p class="text-muted">Items keep existing; they just lose this grouping on the order guide.</p>
                <div id="deleteBlockedBox" class="alert alert-danger d-none">
                    <div id="deleteBlockedMessage" class="mb-2"></div>
                    <ul id="deleteBlockedItems" class="mb-0 ps-3"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let deleteCategoryId = null;
let draggedRow = null;

const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

// layouts.tabler has no global toast helper, and rename/reorder report without
// reloading, so this page carries its own.
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : 'success'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `<div class="d-flex">
        <div class="toast-body"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>`;
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

// ---- Add ------------------------------------------------------------------
function openAddRow() {
    document.getElementById('addRow').classList.remove('d-none');
    document.getElementById('newCategoryError').classList.add('d-none');
    document.getElementById('newCategoryName').value = '';
    document.getElementById('newCategoryName').focus();
}

function closeAddRow() {
    document.getElementById('addRow').classList.add('d-none');
}

function createCategory() {
    const name = document.getElementById('newCategoryName').value.trim();
    const error = document.getElementById('newCategoryError');

    if (!name) {
        error.textContent = 'Enter a category name.';
        error.classList.remove('d-none');
        return;
    }

    request('{{ route('admin.inventory-categories.store') }}', 'POST', { name: name })
        .then(({ status, data }) => {
            if (status === 422) {
                error.textContent = (data.errors && data.errors.name) ? data.errors.name[0] : 'Could not add that category.';
                error.classList.remove('d-none');
                return;
            }
            window.location.reload();
        })
        .catch(() => showToast('Error adding category', 'error'));
}

// ---- Inline rename --------------------------------------------------------
function rowFor(id) {
    return document.querySelector(`#categoryList li[data-id="${id}"]`);
}

function startRename(id) {
    const row = rowFor(id);
    row.querySelector('.category-name').classList.add('d-none');
    const input = row.querySelector('.category-input');
    input.classList.remove('d-none');
    input.focus();
    input.select();
}

function renameKey(event, id) {
    if (event.key === 'Enter') {
        event.preventDefault();
        event.target.blur();
    } else if (event.key === 'Escape') {
        const row = rowFor(id);
        row.querySelector('.category-input').value = row.dataset.name;
        cancelRename(id);
    }
}

function cancelRename(id) {
    const row = rowFor(id);
    row.querySelector('.category-input').classList.add('d-none');
    row.querySelector('.category-name').classList.remove('d-none');
}

function commitRename(id) {
    const row = rowFor(id);
    const input = row.querySelector('.category-input');
    const name = input.value.trim();

    if (!name || name === row.dataset.name) {
        input.value = row.dataset.name;
        cancelRename(id);
        return;
    }

    request(`/inventory-categories/${id}`, 'PUT', { name: name })
        .then(({ status, data }) => {
            if (status === 422) {
                const message = (data.errors && data.errors.name) ? data.errors.name[0] : 'Could not rename that category.';
                showToast(message, 'error');
                input.value = row.dataset.name;
                cancelRename(id);
                return;
            }
            row.dataset.name = name;
            row.querySelector('.category-name').textContent = name;
            cancelRename(id);
            showToast(data.message, 'success');
        })
        .catch(() => {
            input.value = row.dataset.name;
            cancelRename(id);
            showToast('Error renaming category', 'error');
        });
}

// ---- Delete ---------------------------------------------------------------
function openDeleteModal(id, name) {
    deleteCategoryId = id;
    document.getElementById('deleteCategoryName').textContent = name;
    document.getElementById('deleteBlockedBox').classList.add('d-none');
    document.getElementById('deleteBlockedItems').innerHTML = '';
    document.getElementById('confirmDeleteBtn').disabled = false;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    request(`/inventory-categories/${deleteCategoryId}`, 'DELETE')
        .then(({ status, data }) => {
            if (status === 422 && data.items) {
                document.getElementById('deleteBlockedMessage').textContent = data.error;
                const list = document.getElementById('deleteBlockedItems');
                list.innerHTML = '';
                data.items.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = item.name;
                    list.appendChild(li);
                });
                document.getElementById('deleteBlockedBox').classList.remove('d-none');
                document.getElementById('confirmDeleteBtn').disabled = true;
                return;
            }
            window.location.reload();
        })
        .catch(() => showToast('Error deleting category', 'error'));
});

// ---- Drag to reorder ------------------------------------------------------
// Native HTML5 drag events, so no sortable library is pulled into the bundle.
const list = document.getElementById('categoryList');

list.addEventListener('dragstart', function (event) {
    const row = event.target.closest('li[data-id]');
    if (!row) return;
    draggedRow = row;
    row.style.opacity = '0.4';
    event.dataTransfer.effectAllowed = 'move';
});

list.addEventListener('dragend', function () {
    if (!draggedRow) return;
    draggedRow.style.opacity = '';
    draggedRow = null;
    saveOrder();
});

list.addEventListener('dragover', function (event) {
    event.preventDefault();
    if (!draggedRow) return;

    const target = event.target.closest('li[data-id]');
    if (!target || target === draggedRow) return;

    const box = target.getBoundingClientRect();
    const dropAfter = (event.clientY - box.top) > (box.height / 2);
    list.insertBefore(draggedRow, dropAfter ? target.nextSibling : target);
});

function saveOrder() {
    const order = Array.from(list.querySelectorAll('li[data-id]')).map(li => parseInt(li.dataset.id, 10));
    if (order.length === 0) return;

    request('{{ route('admin.inventory-categories.reorder') }}', 'POST', { order: order })
        .then(({ status, data }) => {
            showToast(status === 200 ? data.message : (data.error || 'Could not save the new order'),
                      status === 200 ? 'success' : 'error');
        })
        .catch(() => showToast('Error saving order', 'error'));
}
</script>
@endpush
