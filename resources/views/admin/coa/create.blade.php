@extends('layouts.tabler')

@section('title', 'Create Chart of Account')

@section('content')
<div class="container-xl mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-0">Add Chart of Account</h1>
                    <p class="text-muted mb-0">Define a new category for classifying financial activity.</p>
                </div>
                <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to List
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('coa.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="account_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                                <input type="text" id="account_code" name="account_code" class="form-control @error('account_code') is-invalid @enderror" value="{{ old('account_code') }}" maxlength="10" required>
                                @error('account_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Example: 4000, 5100, 6300</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select id="account_type" name="account_type" class="form-select @error('account_type') is-invalid @enderror" required>
                                    <option value="">Select Type</option>
                                    @foreach($accountTypes as $type)
                                        <option value="{{ $type }}" @selected(old('account_type') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('account_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" id="account_name" name="account_name" class="form-control @error('account_name') is-invalid @enderror" value="{{ old('account_name') }}" maxlength="100" required>
                            @error('account_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="parent_account_id" class="form-label">Parent Account (optional)</label>
                            <select id="parent_account_id" name="parent_account_id" class="form-select @error('parent_account_id') is-invalid @enderror">
                                <option value="">None (top level)</option>
                                @foreach($parentAccounts as $parent)
                                    <option value="{{ $parent->id }}" data-account-code="{{ $parent->account_code }}" @selected((string) old('parent_account_id') === (string) $parent->id)>
                                        {{ $parent->account_code }} - {{ $parent->account_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Store Assignment</label>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="is_global" name="is_global" value="1" @checked(old('is_global'))>
                                <label class="form-check-label" for="is_global">Available to all stores</label>
                            </div>
                            <div id="store_selection" class="border rounded p-3 @if(old('is_global')) d-none @endif">
                                @foreach($stores as $store)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="store_ids[]" value="{{ $store->id }}" id="store{{ $store->id }}" @checked(in_array($store->id, old('store_ids', [])))>
                                        <label class="form-check-label" for="store{{ $store->id }}">{{ $store->store_info }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('store_ids')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
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

        // Parent Account: show only options in the selected type's range (e.g. Assets → 1000-1999) and auto-select default
        const accountTypeRange = {
            'Assets':      { min: 1000, max: 1999, defaultCode: '1000' },
            'Liability':  { min: 2000, max: 2999, defaultCode: '2000' },
            'Taxes':      { min: 3000, max: 3999, defaultCode: '3000' },
            'Revenue':    { min: 4000, max: 4999, defaultCode: '4000' },
            'COGS':       { min: 5000, max: 5999, defaultCode: '5000' },
            'Expense':    { min: 6000, max: 6999, defaultCode: '6000' },
            'Adjustments': { min: 7000, max: 7999, defaultCode: '7000' },
            'Equity':     { min: 8000, max: 8999, defaultCode: '8000' }
        };
        const accountTypeSelect = document.getElementById('account_type');
        const parentSelect = document.getElementById('parent_account_id');
        if (accountTypeSelect && parentSelect) {
            var parentOptionsCache = [];
            parentSelect.querySelectorAll('option').forEach(function(o) {
                parentOptionsCache.push({ value: o.value, code: o.dataset.accountCode || '', text: o.textContent.trim() });
            });
            function syncParentFromType() {
                const type = accountTypeSelect.value;
                const range = accountTypeRange[type];
                parentSelect.innerHTML = '';
                const none = document.createElement('option');
                none.value = '';
                none.textContent = 'None (top level)';
                parentSelect.appendChild(none);
                if (!range) {
                    parentSelect.value = '';
                    return;
                }
                let defaultVal = '';
                for (let i = 0; i < parentOptionsCache.length; i++) {
                    const item = parentOptionsCache[i];
                    if (!item.value) continue;
                    const codeNum = parseInt(item.code, 10);
                    if (isNaN(codeNum) || codeNum < range.min || codeNum > range.max) continue;
                    const opt = document.createElement('option');
                    opt.value = item.value;
                    opt.textContent = item.text;
                    opt.dataset.accountCode = item.code;
                    parentSelect.appendChild(opt);
                    if (item.code.trim() === range.defaultCode) defaultVal = item.value;
                }
                parentSelect.value = defaultVal || '';
            }
            accountTypeSelect.addEventListener('change', syncParentFromType);
            syncParentFromType();
        }
    });
</script>
@endpush


