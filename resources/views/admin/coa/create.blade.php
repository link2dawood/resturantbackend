@extends('layouts.tabler')

@section('title', 'Create Chart of Account')

@section('content')
<div class="container-xl mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-0">Add Chart of Account</h1>
                    <p class="text-muted mb-0">Pick where it belongs — the code is assigned automatically.</p>
                </div>
                <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('coa.store') }}" method="POST">
                        @csrf

                        {{-- Step 1: Account type --}}
                        <div class="mb-3">
                            <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select id="account_type" name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                @foreach($accountTypes as $type)
                                    <option value="{{ $type }}" @selected(old('account_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text text-primary" id="code-range-hint"></div>
                        </div>

                        {{-- Step 2: Parent account (top level, e.g. 6000) --}}
                        <div class="mb-3">
                            <label for="parent_top" class="form-label">Category <span class="text-danger">*</span></label>
                            <select id="parent_top" class="form-select" disabled required>
                                <option value="">Choose a type first…</option>
                            </select>
                            <small class="text-muted">The category this account belongs to (e.g. Online Ordering, Insurance, Payroll).</small>
                        </div>

                        {{-- Step 3: Sub-parent (only parents that have children) --}}
                        <div class="mb-3">
                            <label for="sub_parent" class="form-label">Sub-category <span class="text-muted">(optional)</span></label>
                            <select id="sub_parent" class="form-select" disabled>
                                <option value="">Add directly under the category</option>
                            </select>
                            <small class="text-muted">Optional — nest one level deeper (e.g. Online Ordering → DoorDash).</small>
                            <div id="parent-children" class="mt-2 d-none">
                                <div class="small text-muted mb-1">Accounts already under this parent:</div>
                                <div id="parent-children-list" class="d-flex flex-wrap gap-1"></div>
                            </div>
                        </div>

                        {{-- Step 4: Name + auto-assigned code --}}
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                                <input type="text" id="account_name" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name') }}" maxlength="100" required>
                                @error('account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Account Code</label>
                                <input type="text" id="auto_code_display" class="form-control bg-light" value="—" readonly>
                                <small class="text-muted">Auto-assigned from the parent.</small>
                            </div>
                        </div>

                        {{-- Submitted values (set by the cascade) --}}
                        <input type="hidden" name="parent_account_id" id="parent_account_id" value="{{ old('parent_account_id') }}">
                        <input type="hidden" name="account_code" id="account_code" value="{{ old('account_code') }}">
                        @error('account_code')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        @error('parent_account_id')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                        <div id="rollup-warning" class="alert alert-warning py-2 d-none"></div>

                        <div class="mb-3">
                            <label class="form-label">Store Assignment</label>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1" @checked(old('is_global'))>
                                <label class="form-check-label" for="is_global">Available to all stores</label>
                            </div>
                            <div id="store_selection" class="border rounded p-3 @if(old('is_global')) d-none @endif">
                                @foreach($stores as $store)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $store->id }}" id="store{{ $store->id }}" @checked(in_array($store->id, old('store_ids', $defaultStoreIds ?? [])))>
                                        <label class="form-check-label" for="store{{ $store->id }}">{{ $store->store_info }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('store_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Save Account
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
    // Store assignment toggle
    const globalToggle = document.getElementById('is_global');
    const storeSelection = document.getElementById('store_selection');
    if (globalToggle && storeSelection) {
        globalToggle.addEventListener('change', function () {
            if (this.checked) {
                storeSelection.classList.add('d-none');
                storeSelection.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            } else {
                storeSelection.classList.remove('d-none');
            }
        });
    }

    // Every account, with its stored parent. The Category/Sub-category cascade
    // walks parent_account_id — the account number only decides the new code.
    const ACCOUNTS = @json($parentAccounts);
    const rollupCodes = @json(\App\Models\ChartOfAccount::totalRollupAccountCodes());
    const typeRange = { Assets:[1000,1999], Liability:[2000,2999], Taxes:[3000,3999], Revenue:[4000,4999], COGS:[5000,5999], Expense:[6000,6999], Adjustments:[7000,7999], Equity:[8000,8999] };

    const typeSel = document.getElementById('account_type');
    const parentSel = document.getElementById('parent_top');
    const subSel = document.getElementById('sub_parent');
    const codeHidden = document.getElementById('account_code');
    const parentHidden = document.getElementById('parent_account_id');
    const codeDisplay = document.getElementById('auto_code_display');
    const codeHint = document.getElementById('code-range-hint');
    const rollupWarn = document.getElementById('rollup-warning');
    const childrenWrap = document.getElementById('parent-children');
    const childrenList = document.getElementById('parent-children-list');

    const usedCodes = new Set(ACCOUNTS.map(a => parseInt(a.account_code, 10)).filter(n => !isNaN(n)));

    function isFourDigit(code) { return /^\d{4}$/.test(String(code)); }
    function childRange(code) {
        if (!isFourDigit(code)) return null;
        const n = parseInt(code, 10);
        if (n % 1000 === 0) return [n + 1, n + 999];
        if (n % 100 === 0) return [n + 1, n + 99];
        if (n % 10 === 0) return [n + 1, n + 9];
        return null;
    }
    function naturalStep(code) {
        const n = parseInt(code, 10);
        if (n % 1000 === 0) return 100;
        if (n % 100 === 0) return 10;
        return 1;
    }
    function nextCode(parentCode) {
        const r = childRange(parentCode);
        if (!r) return null;
        const step = naturalStep(parentCode);
        // Prefer the natural block step (100 under x000, 10 under xy00, 1 under xyz0).
        for (let c = parseInt(parentCode, 10) + step; c <= r[1]; c += step) {
            if (!usedCodes.has(c)) return String(c);
        }
        for (let c = r[0]; c <= r[1]; c++) {
            if (!usedCodes.has(c)) return String(c);
        }
        return null;
    }

    function resetSelect(sel, placeholder) {
        sel.innerHTML = '';
        const o = document.createElement('option');
        o.value = ''; o.textContent = placeholder;
        sel.appendChild(o);
    }
    function addOption(sel, acct, suffix) {
        const o = document.createElement('option');
        o.value = acct.id;
        o.dataset.code = acct.account_code;
        o.textContent = acct.account_code + ' - ' + acct.account_name + (suffix || '');
        sel.appendChild(o);
    }
    function selectedCode(sel) {
        const o = sel.options[sel.selectedIndex];
        return (sel.value && o) ? o.dataset.code : '';
    }

    // The type's root account (e.g. 6000 Expenses All) — it has no parent.
    function typeRoot(type) {
        return ACCOUNTS.find(a => a.account_type === type && !a.parent_account_id) || null;
    }
    // Children of an account, straight from the stored tree.
    function childrenOf(parentId) {
        return ACCOUNTS
            .filter(a => String(a.parent_account_id) === String(parentId))
            .sort((a, b) => parseInt(a.account_code) - parseInt(b.account_code));
    }

    function fillParents() {
        const type = typeSel.value;
        resetSelect(parentSel, type ? 'Select category…' : 'Choose a type first…');
        parentSel.disabled = !type;
        if (type) {
            const root = typeRoot(type);
            if (root) {
                // Categories = the type root's children (stored tree, not codes).
                childrenOf(root.id)
                    .filter(a => a.can_have_children)
                    .forEach(a => addOption(
                        parentSel,
                        a,
                        a.children_count > 0
                            ? ' · ' + a.children_count + ' sub-account' + (a.children_count === 1 ? '' : 's')
                            : ''
                    ));
            }
            if (parentSel.options.length === 2) parentSel.value = parentSel.options[1].value;
        }
        fillSubParents();
    }

    function fillSubParents() {
        resetSelect(subSel, 'Add directly under the category');
        subSel.disabled = !parentSel.value;
        if (parentSel.value) {
            // Sub-categories = the selected category's children (stored tree).
            // Detail codes (e.g. 6451 DoorDash) can't hold sub-accounts.
            childrenOf(parentSel.value)
                .filter(a => a.can_have_children)
                .forEach(a => addOption(
                    subSel,
                    a,
                    a.children_count > 0
                        ? ' · ' + a.children_count + ' sub-account' + (a.children_count === 1 ? '' : 's')
                        : ' · no sub-accounts yet'
                ));
        }
        updateCode();
    }

    function deepest() {
        if (selectedCode(subSel)) return { id: subSel.value, code: selectedCode(subSel) };
        if (selectedCode(parentSel)) return { id: parentSel.value, code: selectedCode(parentSel) };
        return null;
    }

    function updateCode() {
        const d = deepest();
        if (d) {
            const code = nextCode(d.code);
            codeHidden.value = code || '';
            parentHidden.value = d.id;
            codeDisplay.value = code ? code : 'Parent is full — pick another';
        } else {
            codeHidden.value = ''; parentHidden.value = ''; codeDisplay.value = '—';
        }
        // Rollup hint
        let msg = '';
        if (d && rollupCodes.includes(String(d.code))) {
            msg = 'New accounts roll up into the ' + d.code + ' total — post transactions to them, not to ' + d.code + ' directly.';
        }
        rollupWarn.textContent = msg;
        rollupWarn.classList.toggle('d-none', !msg);
        // Existing children preview (skip noisy top-level lists)
        if (d && parseInt(d.code, 10) % 1000 !== 0) {
            const kids = childrenOf(d.id);
            childrenList.innerHTML = '';
            kids.forEach(c => { const b = document.createElement('span'); b.className = 'badge bg-blue-lt'; b.textContent = c.account_code + ' ' + c.account_name; childrenList.appendChild(b); });
            if (!kids.length) childrenList.innerHTML = '<span class="small text-muted">No sub-accounts yet.</span>';
            childrenWrap.classList.remove('d-none');
        } else {
            childrenWrap.classList.add('d-none');
        }
    }

    function showCodeRange() {
        const r = typeRange[typeSel.value];
        codeHint.textContent = r ? (typeSel.value + ' accounts use codes ' + r[0] + '–' + r[1] + '.') : '';
    }

    typeSel.addEventListener('change', function () { fillParents(); showCodeRange(); });
    parentSel.addEventListener('change', fillSubParents);
    subSel.addEventListener('change', updateCode);

    fillParents();
    showCodeRange();

    // "Add sub-account" deep-link: preselect the parent's type + category so the
    // new account lands under it (its number stays in the parent's range).
    @php $preselectParentId = optional($preselectParent ?? null)->id; @endphp
    const PRESELECT_PARENT_ID = @json($preselectParentId);
    if (PRESELECT_PARENT_ID) {
        const parent = ACCOUNTS.find(a => String(a.id) === String(PRESELECT_PARENT_ID));
        if (parent) {
            typeSel.value = parent.account_type;
            fillParents();
            // If the parent is a category (child of the type root) select it directly;
            // if it's a sub-category, select its category first, then the sub-category.
            const grand = parent.parent_account_id ? ACCOUNTS.find(a => String(a.id) === String(parent.parent_account_id)) : null;
            if (grand && grand.parent_account_id) {
                parentSel.value = String(grand.id);
                fillSubParents();
                subSel.value = String(parent.id);
            } else {
                parentSel.value = String(parent.id);
                fillSubParents();
            }
            updateCode();
            showCodeRange();
        }
    }
});
</script>
@endpush
