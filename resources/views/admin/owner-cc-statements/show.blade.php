@extends('layouts.tabler')

@section('title', 'CC Statement: ' . $import->file_name)

@section('content')
<div class="container-xl mt-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.owner-cc-statements.index') }}" class="btn btn-ghost-secondary btn-sm">← Back to all statements</a>
        <form action="{{ route('admin.owner-cc-statements.destroy', $import) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this CC statement import, the stored file, and all related records? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">
                Delete import
            </button>
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1 class="mb-2" style="font-size: 1.5rem; font-weight: 500;">{{ $import->file_name }}</h1>
                <p class="text-muted mb-0">
                    Imported {{ $import->created_at->format(config('dates.display_datetime')) }} by {{ $import->importer?->name ?? '—' }}
                    @if($import->cardPlatformLabel())
                        · {{ $import->cardPlatformLabel() }}
                    @endif
                    @if($import->store)
                        · Store: {{ $import->store->store_info }}
                    @endif
                    · {{ number_format($import->rows_imported) }} transactions
                    @if(($import->rows_skipped ?? 0) > 0)
                        · <span class="text-warning">{{ number_format($import->rows_skipped) }} row(s) skipped</span>
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2">
                @if(($import->rows_skipped ?? 0) > 0)
                    <a href="{{ route('admin.owner-cc-statements.exceptions', $import) }}" class="btn btn-outline-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Exception report
                    </a>
                @endif
                <button type="submit" form="owner-cc-lines-form" formaction="{{ route('admin.owner-cc-statements.download', $import) }}" formmethod="POST" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download CSV
                </button>
            </div>
        </div>
    </div>

    @if(($import->rows_skipped ?? 0) > 0)
    <div class="alert alert-warning mb-4">
        <strong>Exception report:</strong> {{ number_format($import->rows_skipped) }} row(s) were not imported (e.g. missing or invalid date). 
        <a href="{{ route('admin.owner-cc-statements.exceptions', $import) }}">Download exception report (CSV)</a> to review skipped rows.
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">Card last 4 digits</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.owner-cc-statements.card-last4', $import) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <label for="card_last4" class="form-label mb-0">Last 4 of credit card for this statement</label>
                    <input type="text" name="card_last4" id="card_last4" class="form-control form-control-sm" maxlength="4" pattern="[0-9]{4}" placeholder="1234" value="{{ old('card_last4', $import->card_last4) }}" style="width: 5rem;">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Save & apply to all transactions</button>
                </div>
            </form>
            <p class="text-muted small mb-0 mt-2">If your file doesn’t include the card number, enter it here; it will be applied to every transaction in this import.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title mb-0">Statement records ({{ number_format($import->lines->count()) }})</h3>
            <p class="text-muted small mb-0">Assign a store and Chart of Account below; Download CSV will use the current selections even before you save them.</p>
        </div>
        <div class="table-responsive">
            <form id="owner-cc-lines-form" action="{{ route('admin.owner-cc-statements.lines.bulk-update', $import) }}" method="POST">
                @csrf
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Last 4 CC</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th>Member</th>
                            <th>Store</th>
                            <th>Chart of Account</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($import->lines as $index => $line)
                            <tr>
                                <td>{{ $line->transaction_date->format(config('dates.display')) }}</td>
                                <td><span class="badge bg-azure-lt">{{ $line->card_last4 ?? $import->card_last4 ?? '—' }}</span></td>
                                <td>{{ Str::limit($line->description, 50) }}</td>
                                <td class="text-end">{{ $line->debit > 0 ? '$' . number_format($line->debit, 2) : '—' }}</td>
                                <td class="text-end">{{ $line->credit > 0 ? '$' . number_format($line->credit, 2) : '—' }}</td>
                                <td>{{ $line->member_name ?? '—' }}</td>
                                <td>
                                    @php
                                        $effectiveStoreId = $line->store_id ?: $import->store_id;
                                    @endphp
                                    <select name="lines[{{ $index }}][store_id]" class="form-select form-select-sm" style="min-width: 160px;">
                                        <option value="" {{ empty($line->store_id) ? 'selected' : '' }}>
                                            {{ $import->store ? $import->store->store_info . ' (Same as import)' : '— Same as import —' }}
                                        </option>
                                        @foreach($stores as $store)
                                            <option value="{{ $store->id }}" {{ (int) $effectiveStoreId === (int) $store->id && !empty($line->store_id) ? 'selected' : '' }}>{{ $store->store_info }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                    <select name="lines[{{ $index }}][coa_id]" class="form-select form-select-sm" style="min-width: 200px;">
                                        <option value="">— None —</option>
                                        @foreach($chartOfAccounts as $coa)
                                            <option value="{{ $coa->id }}" {{ (int) $line->coa_id === (int) $coa->id ? 'selected' : '' }}>
                                                {{ $coa->account_code }} - {{ $coa->account_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No transactions in this import.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($import->lines->count() > 0)
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            Save All Changes
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
