@extends('layouts.tabler')

@section('title', 'Edit Chart of Account')

@section('content')
<div class="container-xl mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-0">Edit Chart of Account</h1>
                    <p class="text-muted mb-0">Update the details for {{ $chartOfAccount->account_code }} - {{ $chartOfAccount->account_name }}.</p>
                </div>
                <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('coa.update', $chartOfAccount) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="account_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                                <input type="text" id="account_code" name="account_code" class="form-control @error('account_code') is-invalid @enderror" value="{{ old('account_code', $chartOfAccount->account_code) }}" maxlength="10" required>
                                @error('account_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select id="account_type" name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                                    @foreach($accountTypes as $type)
                                        <option value="{{ $type }}" @selected(old('account_type', $chartOfAccount->account_type) === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('account_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" id="account_name" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name', $chartOfAccount->account_name) }}" maxlength="100" required>
                            @error('account_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Where this account sits in the chart. Move it freely: the
                             stored parent wins, the account number does not. --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_select" class="form-label">Category</label>
                                <select id="category_select" class="form-select">
                                    <option value="">None (top level)</option>
                                </select>
                                <small class="text-muted">Move this account to a different category (e.g. Insurance instead of Delivery Fees).</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="subcategory_select" class="form-label">Sub-category <span class="text-muted">(optional)</span></label>
                                <select id="subcategory_select" class="form-select">
                                    <option value="">Directly under the category</option>
                                </select>
                                <small class="text-muted">Nest one level deeper (e.g. Online Ordering → DoorDash).</small>
                            </div>
                        </div>

                        <input type="hidden" name="parent_account_id" id="parent_account_id" value="{{ old('parent_account_id', $chartOfAccount->parent_account_id) }}">
                        @error('parent_account_id')
                            <div class="text-danger small mb-2">{{ $message }}</div>
                        @enderror

                        <div id="current-path" class="alert alert-light border py-2 small d-none"></div>

                        <div class="mb-3">
                            <label class="form-label">Store Assignment</label>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1" @checked(old('is_global', $chartOfAccount->stores->isEmpty()))>
                                <label class="form-check-label" for="is_global">Available to all stores</label>
                            </div>
                            <div id="store_selection" class="border rounded p-3 @if(old('is_global', $chartOfAccount->stores->isEmpty())) d-none @endif">
                                @foreach($stores as $store)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $store->id }}" id="store{{ $store->id }}" @checked(in_array($store->id, old('store_ids', $assignedStoreIds)))>
                                        <label class="form-check-label" for="store{{ $store->id }}">{{ $store->store_info }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('store_ids')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $chartOfAccount->is_active))>
                            <label class="form-check-label" for="is_active">Active</label>
                            @error('is_active')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Update Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const globalToggle = document.getElementById('is_global');
        const storeSelection = document.getElementById('store_selection');

        globalToggle.addEventListener('change', function () {
            if (this.checked) {
                storeSelection.classList.add('d-none');
                storeSelection.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            } else {
                storeSelection.classList.remove('d-none');
            }
        });

        // ── Category / Sub-category cascade ──────────────────────────────
        // Driven entirely by the STORED tree (parent_account_id). An account's
        // number no longer decides where it lives — the admin does.
        const ACCOUNTS = @json($parentAccounts);
        const SELF_ID = {{ (int) $chartOfAccount->id }};

        const typeSel = document.getElementById('account_type');
        const catSel = document.getElementById('category_select');
        const subSel = document.getElementById('subcategory_select');
        const parentField = document.getElementById('parent_account_id');
        const pathBox = document.getElementById('current-path');

        if (typeSel && catSel && subSel && parentField) {
            const byId = {};
            ACCOUNTS.forEach(a => { byId[String(a.id)] = a; });

            const typeRoot = type => ACCOUNTS.find(a => a.account_type === type && !a.parent_account_id) || null;
            const childrenOf = pid => ACCOUNTS
                .filter(a => String(a.parent_account_id) === String(pid))
                .sort((a, b) => parseInt(a.account_code) - parseInt(b.account_code));

            // An account can't be moved under itself or its own descendants.
            function isDescendant(candidate) {
                let cur = candidate, guard = 0;
                while (cur && guard++ < 20) {
                    if (String(cur.id) === String(SELF_ID)) return true;
                    cur = cur.parent_account_id ? byId[String(cur.parent_account_id)] : null;
                }
                return false;
            }

            function addOption(sel, a) {
                const o = document.createElement('option');
                o.value = a.id;
                o.textContent = a.account_code + ' - ' + a.account_name
                    + (a.children_count > 0 ? ' · ' + a.children_count + ' sub-account' + (a.children_count === 1 ? '' : 's') : '');
                sel.appendChild(o);
            }

            function reset(sel, placeholder) {
                sel.innerHTML = '';
                const o = document.createElement('option');
                o.value = '';
                o.textContent = placeholder;
                sel.appendChild(o);
            }

            function fillCategories(preselect) {
                reset(catSel, 'None (top level)');
                const root = typeRoot(typeSel.value);
                if (root) {
                    childrenOf(root.id)
                        .filter(a => a.can_have_children && !isDescendant(a))
                        .forEach(a => addOption(catSel, a));
                }
                if (preselect) catSel.value = preselect;
            }

            function fillSubcategories(preselect) {
                reset(subSel, 'Directly under the category');
                subSel.disabled = !catSel.value;
                if (catSel.value) {
                    childrenOf(catSel.value)
                        .filter(a => a.can_have_children && !isDescendant(a))
                        .forEach(a => addOption(subSel, a));
                }
                if (preselect) subSel.value = preselect;
            }

            // The submitted parent is the deepest thing chosen. "None (top level)"
            // means directly under the type root (e.g. Expenses 6000) — NOT a
            // detached account with no parent, which would fall out of the type.
            function syncParentField() {
                const root = typeRoot(typeSel.value);
                parentField.value = subSel.value || catSel.value || (root ? String(root.id) : '');
                renderPath();
            }

            function renderPath() {
                if (!pathBox) return;
                const chain = [];
                let cur = parentField.value ? byId[parentField.value] : null;
                let guard = 0;
                while (cur && guard++ < 20) {
                    chain.unshift(cur.account_code + ' ' + cur.account_name);
                    cur = cur.parent_account_id ? byId[String(cur.parent_account_id)] : null;
                }
                chain.push('{{ $chartOfAccount->account_code }} {{ $chartOfAccount->account_name }}');
                pathBox.textContent = 'Will appear under: ' + chain.join('  →  ');
                pathBox.classList.remove('d-none');
            }

            // Prefill from where the account currently sits: if its parent's own
            // parent is the type root, the parent IS the category; otherwise the
            // parent is a sub-category and its parent is the category.
            function prefill() {
                const current = parentField.value ? byId[parentField.value] : null;
                let catId = '', subId = '';
                // Only descend when the current parent is itself under something.
                // If the current parent is the type root (no parent), this account
                // is a top-level category → leave the Category as "None (top level)".
                if (current && current.parent_account_id) {
                    const grand = byId[String(current.parent_account_id)];
                    if (grand && grand.parent_account_id) {
                        catId = String(grand.id);
                        subId = String(current.id);
                    } else {
                        catId = String(current.id);
                    }
                }
                fillCategories(catId);
                fillSubcategories(subId);
                syncParentField();
            }

            typeSel.addEventListener('change', function () {
                fillCategories('');
                fillSubcategories('');
                syncParentField();
            });
            catSel.addEventListener('change', function () {
                fillSubcategories('');
                syncParentField();
            });
            subSel.addEventListener('change', syncParentField);

            prefill();
        }
    });
</script>
@endpush


