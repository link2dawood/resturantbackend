@extends('layouts.tabler')

@section('title', 'Import Owner CC Statement')

@section('content')
<div class="container-xl mt-4">
    <div class="mb-4">
        <a href="{{ route('admin.owner-cc-statements.index') }}" class="btn btn-ghost-secondary btn-sm">← Back to statements</a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Import Owner CC Statement</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Upload a CSV or XLSX file exported from your credit card statement. Expected columns: <strong>Status</strong>, <strong>Date</strong>, <strong>Description</strong>, <strong>Debit</strong>, <strong>Credit</strong>, <strong>Member Name</strong>.
                    </p>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('admin.owner-cc-statements.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label required">File (CSV or XLSX)</label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.xlsx,.xls" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Store (optional)</label>
                            <select class="form-select" name="store_id">
                                <option value="">— None —</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ old('store_id') == $store->id ? 'selected' : '' }}>
                                        {{ $store->store_info }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Import</button>
                            <a href="{{ route('admin.owner-cc-statements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
