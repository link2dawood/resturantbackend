@extends('layouts.tabler')

@section('title', 'Import Bank Statement')

@section('content')
<div class="container-xl mt-4">
    <div class="mb-4">
        <a href="{{ route('admin.bank-statement-imports.index') }}" class="btn btn-ghost-secondary btn-sm">Back to imports</a>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h3 class="card-title mb-0">Bank accounts</h3>
                <p class="text-muted small mb-0">Add or edit accounts here — they appear in the import dropdown (store-specific and <span class="text-muted">Corporate</span> accounts).</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" onclick="bsiOpenCreateModal()">Add bank account</button>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Bank</th>
                        <th>Last 4</th>
                        <th>Type</th>
                        <th>Store</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankAccounts as $ba)
                        <tr>
                            <td><strong>{{ $ba->bank_name }}</strong></td>
                            <td><span class="badge bg-light text-dark">…{{ $ba->account_number_last_four }}</span></td>
                            <td><span class="badge bg-info-lt">{{ ucwords(str_replace('_', ' ', $ba->account_type)) }}</span></td>
                            <td>
                                @if($ba->store)
                                    {{ $ba->store->store_info }}
                                @else
                                    <span class="text-muted">Corporate</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="bsiOpenEditModal({{ $ba->id }})">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No active bank accounts yet. Use <strong>Add bank account</strong> above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Import Bank Statement ({{ $bankLabel }} CSV)</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        Choose the <strong>store</strong> and the <strong>bank account</strong> this CSV belongs to, then upload your Bank of the West activity export (date range / by month).
                    </p>
                    <p class="text-muted small mb-3">
                        Expected columns: Account, ChkRef, Debit, Credit, Balance, Date, Description (header names may vary slightly).
                    </p>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if ($bankAccounts->isEmpty())
                        <div class="alert alert-info mb-3">
                            Add at least one bank account in the table above before importing.
                        </div>
                    @endif

                    <form action="{{ route('admin.bank-statement-imports.store') }}" method="POST" enctype="multipart/form-data" id="bank-statement-import-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label required" for="import_store_id">Store</label>
                            <select name="store_id" id="import_store_id" class="form-select @error('store_id') is-invalid @enderror" required @if($stores->isEmpty()) disabled @endif>
                                <option value="" disabled {{ old('store_id') ? '' : 'selected' }}>Select store</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ (string) old('store_id') === (string) $store->id ? 'selected' : '' }}>
                                        {{ $store->store_info }}
                                    </option>
                                @endforeach
                            </select>
                            @error('store_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label required" for="import_bank_account_id">Bank account</label>
                            <select name="bank_account_id" id="import_bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required></select>
                            @error('bank_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-hint">Includes accounts for the selected store and <strong>Corporate</strong> accounts (any store). Bank of the West–style names are sorted first when available.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">CSV file</label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required @if($stores->isEmpty() || $bankAccounts->isEmpty()) disabled @endif>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="bank-statement-import-submit" @if($stores->isEmpty() || $bankAccounts->isEmpty()) disabled @endif>Import</button>
                            <a href="{{ route('admin.bank-statement-imports.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bsiAccountModal" tabindex="-1" aria-labelledby="bsiAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bsiAccountModalLabel">Add bank account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="bsiAccountForm" onsubmit="return false;">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="bsi_bank_name">Bank name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bsi_bank_name" name="bank_name" required maxlength="100">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="bsi_last_four">Last 4 digits <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bsi_last_four" name="account_number_last_four" maxlength="4" pattern="[0-9]{4}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="bsi_account_type">Account type <span class="text-danger">*</span></label>
                            <select class="form-select" id="bsi_account_type" name="account_type" required>
                                <option value="">Select</option>
                                <option value="checking">Checking</option>
                                <option value="savings">Savings</option>
                                <option value="credit_card">Credit card</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="bsi_store_id">Store</label>
                        <select class="form-select" id="bsi_store_id" name="store_id">
                            <option value="">Corporate (any store on import)</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->store_info }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="bsi_opening_balance_wrap">
                        <label class="form-label" for="bsi_opening_balance">Opening balance <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="bsi_opening_balance" name="opening_balance" step="0.01" value="0">
                    </div>
                    <div class="mb-3 d-none" id="bsi_is_active_wrap">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="bsi_is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="bsi_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bsi_save_btn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var banks = @json($bankAccountsPayload);
    var storeSelect = document.getElementById('import_store_id');
    var bankSelect = document.getElementById('import_bank_account_id');
    var fileInput = document.querySelector('#bank-statement-import-form input[type="file"]');
    var oldStore = @json(old('store_id'));
    var oldBank = @json(old('bank_account_id'));
    var submitBtn = document.getElementById('bank-statement-import-submit');
    var bsiAccountId = null;

    function accountMatchesStore(b, sid) {
        return b.store_id === null || b.store_id === undefined || Number(b.store_id) === Number(sid);
    }

    function setSubmitState() {
        if (!submitBtn) return;
        var blocked = !storeSelect || storeSelect.disabled || !bankSelect || bankSelect.disabled || bankSelect.value === '';
        if (fileInput && fileInput.disabled) blocked = true;
        submitBtn.disabled = blocked;
    }

    function rebuildBankOptions() {
        if (!bankSelect || !storeSelect) return;
        var sid = storeSelect.value ? parseInt(storeSelect.value, 10) : null;
        bankSelect.innerHTML = '';

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.disabled = true;
        placeholder.textContent = sid ? 'Select bank account' : 'Select store first';
        bankSelect.appendChild(placeholder);

        if (!sid) {
            bankSelect.disabled = true;
            bankSelect.value = '';
            setSubmitState();
            return;
        }

        var list = banks.filter(function (b) { return accountMatchesStore(b, sid); });
        if (list.length === 0) {
            bankSelect.disabled = true;
            placeholder.textContent = 'No accounts for this store — add one above';
            setSubmitState();
            return;
        }

        bankSelect.disabled = false;
        var sorted = list.slice().sort(function (a, b) {
            if (a.bow !== b.bow) return b.bow ? 1 : -1;
            return a.label.localeCompare(b.label);
        });

        var useOld = oldStore !== null && oldStore !== undefined && String(oldStore) === String(sid)
            && oldBank !== null && oldBank !== undefined
            && list.some(function (x) { return Number(x.id) === Number(oldBank); });
        var selectedId = useOld ? Number(oldBank) : Number((sorted.find(function (x) { return x.bow; }) || sorted[0]).id);

        sorted.forEach(function (b) {
            var opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = b.label + (b.bow ? ' ({{ $bankLabel }} CSV)' : '');
            if (Number(b.id) === selectedId) opt.selected = true;
            bankSelect.appendChild(opt);
        });
        setSubmitState();
    }

    window.bsiOpenCreateModal = function () {
        bsiAccountId = null;
        document.getElementById('bsiAccountModalLabel').textContent = 'Add bank account';
        document.getElementById('bsiAccountForm').reset();
        document.getElementById('bsi_opening_balance_wrap').classList.remove('d-none');
        document.getElementById('bsi_is_active_wrap').classList.add('d-none');
        document.getElementById('bsi_opening_balance').required = true;
        new bootstrap.Modal(document.getElementById('bsiAccountModal')).show();
    };

    window.bsiOpenEditModal = function (id) {
        fetch('/api/bank-accounts/' + id, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (account) {
                bsiAccountId = account.id;
                document.getElementById('bsiAccountModalLabel').textContent = 'Edit bank account';
                document.getElementById('bsi_bank_name').value = account.bank_name;
                document.getElementById('bsi_last_four').value = account.account_number_last_four;
                document.getElementById('bsi_account_type').value = account.account_type;
                document.getElementById('bsi_store_id').value = account.store_id || '';
                document.getElementById('bsi_opening_balance_wrap').classList.add('d-none');
                document.getElementById('bsi_opening_balance').required = false;
                document.getElementById('bsi_is_active_wrap').classList.remove('d-none');
                document.getElementById('bsi_is_active').checked = !!account.is_active;
                new bootstrap.Modal(document.getElementById('bsiAccountModal')).show();
            })
            .catch(function () { alert('Could not load bank account.'); });
    };

    function bsiPayloadFromForm() {
        var data = {
            bank_name: document.getElementById('bsi_bank_name').value,
            account_number_last_four: document.getElementById('bsi_last_four').value,
            account_type: document.getElementById('bsi_account_type').value,
            store_id: document.getElementById('bsi_store_id').value || null
        };
        if (!bsiAccountId) {
            data.opening_balance = parseFloat(document.getElementById('bsi_opening_balance').value) || 0;
        } else {
            data.is_active = document.getElementById('bsi_is_active').checked;
        }
        return data;
    }

    document.getElementById('bsiAccountForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = document.getElementById('bsi_save_btn');
        var sp = btn.querySelector('.spinner-border');
        sp.classList.remove('d-none');
        btn.disabled = true;

        var url = bsiAccountId ? ('/api/bank-accounts/' + bsiAccountId) : '/api/bank-accounts';
        var method = bsiAccountId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            credentials: 'same-origin',
            body: JSON.stringify(bsiPayloadFromForm())
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, body: j }; }); })
            .then(function (res) {
                sp.classList.add('d-none');
                btn.disabled = false;
                if (!res.ok) {
                    var msg = res.body.errors ? JSON.stringify(res.body.errors) : (res.body.message || res.body.error || 'Save failed');
                    alert(msg);
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                sp.classList.add('d-none');
                btn.disabled = false;
                alert('Error saving bank account');
            });
    });

    if (bankSelect) bankSelect.addEventListener('change', setSubmitState);
    if (storeSelect) storeSelect.addEventListener('change', rebuildBankOptions);
    document.addEventListener('DOMContentLoaded', rebuildBankOptions);
})();
</script>
@endsection
