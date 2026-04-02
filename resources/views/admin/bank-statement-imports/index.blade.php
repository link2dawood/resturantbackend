@extends('layouts.tabler')

@section('title', 'Bank Statement Imports')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400;">Bank Statement Imports</h1>
            <p class="text-muted mb-0 mt-1">Bank of the West activity CSV (month range exports)</p>
        </div>
        <a href="{{ route('admin.bank-statement-imports.create') }}" class="btn btn-primary">Import CSV</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.bank-statement-imports.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Store</label>
                    <select class="form-select" name="store_id">
                        <option value="">All stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-striped">
                <thead>
                    <tr>
                        <th>Imported</th>
                        <th>File</th>
                        <th>Store</th>
                        <th class="text-end">Rows</th>
                        <th class="text-end">Imported</th>
                        <th>By</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $b)
                        <tr>
                            <td>{{ $b->imported_at ? $b->imported_at->format(config('dates.display_datetime')) : '—' }}</td>
                            <td>{{ $b->file_name }}</td>
                            <td>{{ $b->store ? $b->store->store_info : '—' }}</td>
                            <td class="text-end">{{ number_format($b->transaction_count) }}</td>
                            <td class="text-end">{{ number_format($b->imported_count) }}</td>
                            <td>{{ $b->importer ? $b->importer->name : '—' }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.bank-statement-imports.show', $b) }}" class="btn btn-sm btn-primary me-1">View / COA</a>
                                <form action="{{ route('admin.bank-statement-imports.destroy', $b) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this bank statement import, all imported bank lines, and any expenses created from this import? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No imports yet. <a href="{{ route('admin.bank-statement-imports.create') }}">Import a Bank of the West CSV</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
            <div class="card-footer">{{ $batches->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
