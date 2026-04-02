@extends('layouts.tabler')

@section('title', 'Owner CC Statements')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Owner CC Statements</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Import and view owner credit card statement transactions (CSV or XLSX)</p>
        </div>
        <a href="{{ route('admin.owner-cc-statements.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Import Statement
        </a>
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
            <form action="{{ route('admin.owner-cc-statements.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Store</label>
                    <select class="form-select" name="store_id">
                        <option value="">All Stores</option>
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
                        <th>Platform</th>
                        <th>Store</th>
                        <th>Rows</th>
                        <th>By</th>
                        <th class="text-center" style="min-width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imports as $imp)
                        <tr>
                            <td>{{ $imp->created_at->format('M j, Y g:i A') }}</td>
                            <td>{{ $imp->file_name }}</td>
                            <td>{{ $imp->cardPlatformLabel() ?? '—' }}</td>
                            <td>{{ $imp->store?->store_info ?? '—' }}</td>
                            <td>{{ number_format($imp->rows_imported) }}</td>
                            <td>{{ $imp->importer?->name ?? '—' }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.owner-cc-statements.show', $imp) }}" class="btn btn-sm btn-primary me-1">
                                    View
                                </a>
                                <form action="{{ route('admin.owner-cc-statements.destroy', $imp) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this CC statement import, its stored file, and all related records? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No imports yet. <a href="{{ route('admin.owner-cc-statements.create') }}">Import your first statement</a>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($imports->hasPages())
            <div class="card-footer d-flex align-items-center">
                {{ $imports->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
