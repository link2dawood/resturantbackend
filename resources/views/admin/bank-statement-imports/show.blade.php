@extends('layouts.tabler')

@section('title', 'Bank import: ' . $batch->file_name)

@section('content')
<div class="container-xl mt-4">
    <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="{{ route('admin.bank-statement-imports.index') }}" class="btn btn-ghost-secondary btn-sm">← Back to all imports</a>
        <form action="{{ route('admin.bank-statement-imports.destroy', $batch) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this bank statement import, all imported bank lines, and any expenses created from this import? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Delete import</button>
        </form>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h1 class="mb-2" style="font-size: 1.5rem; font-weight: 500;">{{ $batch->file_name }}</h1>
            <p class="text-muted mb-0">
                Imported {{ $batch->imported_at?->format(config('dates.display_datetime')) ?? '—' }}
                by {{ $batch->importer?->name ?? '—' }}
                @if($batch->store)
                    · Store: {{ $batch->store->store_info }}
                @endif
                · {{ \App\Constants\BankStatementSupportedBank::BANK_OF_THE_WEST_LABEL }}
                · {{ number_format($batch->imported_count) }} posted · {{ number_format($batch->duplicate_count) }} duplicates skipped
                @if(($batch->needs_review_count ?? 0) > 0)
                    · <span class="text-warning">{{ number_format($batch->needs_review_count) }} need review</span>
                @endif
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title mb-0">Transactions ({{ number_format($transactions->count()) }})</h3>
            <p class="text-muted small mb-0">
                For <strong>debits</strong> that created an expense, assign Chart of Account (same list as Owner CC statements). Credits / deposits have no expense — COA does not apply.
            </p>
        </div>
        <div class="table-responsive">
            <form action="{{ route('admin.bank-statement-imports.bulk-update-coa', $batch) }}" method="POST">
                @csrf
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance</th>
                            <th>Chart of Account</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $index => $txn)
                            <tr>
                                <td>{{ $txn->transaction_date->format(config('dates.display')) }}</td>
                                <td>
                                    @if($txn->transaction_type === 'debit')
                                        <span class="badge bg-red-lt">Debit</span>
                                    @else
                                        <span class="badge bg-green-lt">Credit</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($txn->description, 64) }}</td>
                                <td class="text-end">${{ number_format((float) $txn->amount, 2) }}</td>
                                <td class="text-end">{{ $txn->balance !== null ? '$' . number_format((float) $txn->balance, 2) : '—' }}</td>
                                <td>
                                    @if($txn->matched_expense_id)
                                        <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $txn->id }}">
                                        @php
                                            $coaId = $txn->matchedExpense?->coa_id;
                                        @endphp
                                        <select name="lines[{{ $index }}][coa_id]" class="form-select form-select-sm" style="min-width: 220px;">
                                            <option value="">— None —</option>
                                            @foreach($chartOfAccounts as $coa)
                                                <option value="{{ $coa->id }}" {{ (int) $coaId === (int) $coa->id ? 'selected' : '' }}>
                                                    {{ $coa->account_code }} - {{ $coa->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No transactions in this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($transactions->where('matched_expense_id', '!=', null)->count() > 0)
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Save COA assignments</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
