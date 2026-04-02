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
                    <h3 class="card-title">Import Bank Statement (Bank of the West)</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        Choose the store and upload the activity CSV exported from Bank of the West. The bank account is resolved from this store's active Bank of the West checking account.
                    </p>
                    <p class="text-muted small mb-3">
                        Expected columns: Account, ChkRef, Debit, Credit, Balance, Date, Description (header names may vary slightly).
                    </p>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('admin.bank-statement-imports.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label required">Store</label>
                            <select name="store_id" class="form-select @error('store_id') is-invalid @enderror" required>
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
                            <label class="form-label">Bank</label>
                            <input type="text" class="form-control" value="{{ $bankLabel }}" readonly disabled>
                            <div class="form-hint">Single supported bank for this import flow.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">CSV file</label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Import</button>
                            <a href="{{ route('admin.bank-statement-imports.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
