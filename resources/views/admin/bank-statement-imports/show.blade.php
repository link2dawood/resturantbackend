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
                For <strong>debits</strong> that created an expense, pick a Chart of Account from the same list as <strong>Owner CC statements</strong> (Expense / COGS detail accounts). The dropdown pre-selects a COA when you (or Owner CC) already chose one for the same normalized description. <strong>Your choice saves as soon as you change it.</strong> Credits / deposits have no expense — COA does not apply.
            </p>
        </div>
        <div class="table-responsive">
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
                                        @php
                                            $descPattern = \App\Models\OwnerCcDescriptionMapping::normalizeDescription($txn->description);
                                            $storedCoaId = $txn->matchedExpense?->coa_id;
                                            $learnedCoaId = ($descPattern !== '' && $learnedCoaByPattern->has($descPattern))
                                                ? (int) $learnedCoaByPattern->get($descPattern)
                                                : null;
                                            $selectedCoaId = $storedCoaId ? (int) $storedCoaId : $learnedCoaId;
                                            $suggestedOnly = ! $storedCoaId && $learnedCoaId;
                                        @endphp
                                        <select
                                            class="form-select form-select-sm js-bank-coa-select"
                                            style="min-width: 220px;"
                                            data-save-url="{{ route('admin.bank-statement-imports.transaction-coa', [$batch, $txn]) }}"
                                            @if($suggestedOnly) title="Suggested from a prior assignment for this description; saving applies it to this expense." @endif
                                        >
                                            <option value="">— None —</option>
                                            @foreach($chartOfAccounts as $coa)
                                                <option value="{{ $coa->id }}" {{ (int) $selectedCoaId === (int) $coa->id ? 'selected' : '' }}>
                                                    {{ $coa->account_code }} - {{ $coa->account_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="text-muted small mt-1 js-bank-coa-status" aria-live="polite"></div>
                                        @if($suggestedOnly)
                                            <div class="text-muted small mt-1 bank-coa-suggested-note">Suggested</div>
                                        @endif
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
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    document.querySelectorAll('select.js-bank-coa-select').forEach(function (sel) {
        let lastSaved = sel.value;
        sel.addEventListener('change', function () {
            const url = sel.getAttribute('data-save-url');
            if (!url || !token) return;
            const statusEl = sel.closest('td')?.querySelector('.js-bank-coa-status');
            const suggestedNote = sel.closest('td')?.querySelector('.bank-coa-suggested-note');
            const coaId = sel.value === '' ? null : parseInt(sel.value, 10);
            if (coaId !== null && Number.isNaN(coaId)) return;
            sel.disabled = true;
            if (statusEl) {
                statusEl.textContent = 'Saving…';
                statusEl.classList.remove('text-danger');
            }
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ coa_id: coaId }),
            })
                .then(async function (r) {
                    let data = {};
                    try {
                        data = await r.json();
                    } catch (e) { /* non-JSON error body */ }
                    return { ok: r.ok, status: r.status, data: data };
                })
                .then(function (res) {
                    sel.disabled = false;
                    if (res.ok) {
                        lastSaved = sel.value;
                        if (statusEl) {
                            statusEl.textContent = 'Saved';
                            statusEl.classList.remove('text-danger');
                        }
                        if (suggestedNote) suggestedNote.remove();
                        window.setTimeout(function () {
                            if (statusEl && statusEl.textContent === 'Saved') statusEl.textContent = '';
                        }, 2000);
                    } else {
                        if (statusEl) {
                            statusEl.textContent = res.data?.message || ('Error (' + res.status + ')');
                            statusEl.classList.add('text-danger');
                        }
                        sel.value = lastSaved;
                    }
                })
                .catch(function () {
                    sel.disabled = false;
                    if (statusEl) {
                        statusEl.textContent = 'Network error';
                        statusEl.classList.add('text-danger');
                    }
                    sel.value = lastSaved;
                });
        });
    });
})();
</script>
@endpush
