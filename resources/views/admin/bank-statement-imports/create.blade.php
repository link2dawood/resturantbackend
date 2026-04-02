@extends('layouts.tabler')

@section('title', 'Import Bank Statement')

@section('content')
<div class="container-xl mt-4">
    <div class="mb-4">
        <a href="{{ route('admin.bank-statement-imports.index') }}" class="btn btn-ghost-secondary btn-sm">Back to imports</a>
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

                    @if (! $hasAnyBankAccount)
                        <div class="alert alert-warning">
                            No active bank accounts were found for your stores.
                            <a href="{{ route('admin.bank.accounts.index') }}">Open Bank Accounts</a> (or reconciliation) and add an account for this store first.
                        </div>
                    @endif

                    <form action="{{ route('admin.bank-statement-imports.store') }}" method="POST" enctype="multipart/form-data" id="bank-statement-import-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label required" for="import_store_id">Store</label>
                            <select name="store_id" id="import_store_id" class="form-select @error('store_id') is-invalid @enderror" required {{ ! $hasAnyBankAccount ? 'disabled' : '' }}>
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
                            <select name="bank_account_id" id="import_bank_account_id" class="form-select @error('bank_account_id') is-invalid @enderror" required {{ ! $hasAnyBankAccount ? 'disabled' : '' }}></select>
                            @error('bank_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-hint">Accounts are filtered by the store you select. Names matching Bank of the West are listed first when available.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">CSV file</label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required {{ ! $hasAnyBankAccount ? 'disabled' : '' }}>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="bank-statement-import-submit" {{ ! $hasAnyBankAccount ? 'disabled' : '' }}>Import</button>
                            <a href="{{ route('admin.bank-statement-imports.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@if ($hasAnyBankAccount)
<script>
(function () {
    var banks = @json($bankAccountsPayload);
    var storeSelect = document.getElementById('import_store_id');
    var bankSelect = document.getElementById('import_bank_account_id');
    var oldStore = @json(old('store_id'));
    var oldBank = @json(old('bank_account_id'));
    var submitBtn = document.getElementById('bank-statement-import-submit');

    function setSubmitState() {
        if (!submitBtn) return;
        submitBtn.disabled = bankSelect.disabled || bankSelect.value === '';
    }

    function rebuildBankOptions() {
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

        var list = banks.filter(function (b) { return b.store_id === sid; });
        if (list.length === 0) {
            bankSelect.disabled = true;
            placeholder.textContent = 'No bank accounts for this store — add one under Bank Accounts';
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
            && list.some(function (x) { return x.id === oldBank; });
        var selectedId = useOld ? oldBank : (sorted.find(function (x) { return x.bow; }) || sorted[0]).id;

        sorted.forEach(function (b) {
            var opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = b.label + (b.bow ? ' ({{ $bankLabel }} CSV)' : '');
            if (Number(b.id) === Number(selectedId)) opt.selected = true;
            bankSelect.appendChild(opt);
        });
        setSubmitState();
    }

    bankSelect.addEventListener('change', setSubmitState);
    storeSelect.addEventListener('change', rebuildBankOptions);
    document.addEventListener('DOMContentLoaded', rebuildBankOptions);
})();
</script>
@endif
@endsection
