@extends('layouts.tabler')

@section('title', 'Sales Projections')

@section('content')
<div class="container-xl py-4">
    <h2 class="mb-1">Sales Projection Calendar</h2>
    <p class="text-muted">Enter a daily sales projection for each store and compare it against actual net sales.</p>

    @if ($stores->isEmpty())
        <div class="alert alert-info">You don't have any stores yet. Create a store to start projecting sales.</div>
    @else
        {{-- Store + month controls --}}
        <form method="GET" action="{{ route('sales-projections.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-auto">
                <label class="form-label mb-1">Store</label>
                <select name="store_id" class="form-select" onchange="this.form.submit()">
                    @foreach ($stores as $s)
                        <option value="{{ $s->id }}" @selected($s->id === $storeId)>{{ $s->store_info }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1">Month</label>
                <div class="btn-group">
                    <a href="{{ route('sales-projections.index', ['store_id' => $storeId, 'month' => $prevMonth]) }}" class="btn btn-outline-secondary">‹</a>
                    <span class="btn btn-outline-secondary disabled" style="min-width: 160px;">{{ $month->format('F Y') }}</span>
                    <a href="{{ route('sales-projections.index', ['store_id' => $storeId, 'month' => $nextMonth]) }}" class="btn btn-outline-secondary">›</a>
                </div>
            </div>
        </form>

        {{-- Month summary --}}
        <div class="row row-cards mb-3">
            <div class="col-sm-4"><div class="card"><div class="card-body py-2">
                <div class="text-muted small">Projected (month)</div>
                <div class="h3 mb-0">${{ number_format($totals['projected'], 2) }}</div>
            </div></div></div>
            <div class="col-sm-4"><div class="card"><div class="card-body py-2">
                <div class="text-muted small">Actual (month)</div>
                <div class="h3 mb-0">${{ number_format($totals['actual'], 2) }}</div>
            </div></div></div>
            <div class="col-sm-4"><div class="card"><div class="card-body py-2">
                <div class="text-muted small">Variance</div>
                <div class="h3 mb-0 {{ $totals['variance'] >= 0 ? 'text-green' : 'text-red' }}">
                    {{ $totals['variance'] >= 0 ? '+' : '−' }}${{ number_format(abs($totals['variance']), 2) }}
                </div>
            </div></div></div>
        </div>

        {{-- Calendar grid --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 sales-cal">
                    <thead>
                        <tr class="text-center text-muted">
                            <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weeks as $week)
                            <tr>
                                @foreach ($week as $cell)
                                    <td class="sales-cal__cell {{ $cell['in_month'] ? '' : 'is-muted' }}" style="vertical-align: top; height: 96px;">
                                        @if ($cell['in_month'])
                                            <div class="d-flex justify-content-between align-items-start">
                                                <span class="fw-bold">{{ $cell['day'] }}</span>
                                                @if ($cell['variance'] !== null)
                                                    <span class="badge bg-{{ $cell['variance'] >= 0 ? 'green' : 'red' }}-lt" title="Actual − Projected">
                                                        {{ $cell['variance'] >= 0 ? '+' : '−' }}${{ number_format(abs($cell['variance']), 0) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="input-group input-group-sm mt-1">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" min="0" class="form-control proj-input"
                                                       data-date="{{ $cell['key'] }}"
                                                       value="{{ $cell['projection'] !== null ? number_format($cell['projection'], 2, '.', '') : '' }}"
                                                       placeholder="proj.">
                                            </div>
                                            <div class="small text-muted mt-1">
                                                @if ($cell['actual'] !== null)
                                                    Actual ${{ number_format($cell['actual'], 2) }}
                                                @else
                                                    <span class="text-muted-light">no report</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            (function () {
                const url = @json(route('sales-projections.store'));
                const token = @json(csrf_token());
                const storeId = @json($storeId);

                document.querySelectorAll('.proj-input').forEach((input) => {
                    let last = input.value;
                    input.addEventListener('blur', () => save(input));
                    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); input.blur(); } });

                    async function save(el) {
                        const val = el.value.trim();
                        if (val === '' || val === last) return;
                        el.classList.remove('is-invalid', 'is-valid');
                        try {
                            const res = await fetch(url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                                body: JSON.stringify({ store_id: storeId, date: el.dataset.date, amount: parseFloat(val) }),
                            });
                            if (!res.ok) throw new Error('save failed');
                            last = el.value;
                            el.classList.add('is-valid');
                            setTimeout(() => el.classList.remove('is-valid'), 1200);
                        } catch (err) {
                            el.classList.add('is-invalid');
                        }
                    }
                });
            })();
        </script>
    @endif
</div>
@endsection
