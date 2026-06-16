@extends('layouts.tabler')

@section('title', 'Sales Projections')

@section('content')
<div class="container-xl py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h2 class="mb-1" style="font-weight:700;">Sales Projection Calendar</h2>
            <p class="text-muted mb-0">Type a projection on any day — it saves instantly and compares against actual net sales.</p>
        </div>

        @unless ($stores->isEmpty())
            <form method="GET" action="{{ route('sales-projections.index') }}" class="d-flex align-items-center gap-2">
                <select name="store_id" class="form-select" style="border-radius:10px; min-width:200px;" onchange="this.form.submit()">
                    @foreach ($stores as $s)
                        <option value="{{ $s->id }}" @selected($s->id === $storeId)>{{ $s->store_info }}</option>
                    @endforeach
                </select>
                <div class="spc-monthnav">
                    <a href="{{ route('sales-projections.index', ['store_id' => $storeId, 'month' => $prevMonth]) }}" aria-label="Previous month">‹</a>
                    <span>{{ $month->format('F Y') }}</span>
                    <a href="{{ route('sales-projections.index', ['store_id' => $storeId, 'month' => $nextMonth]) }}" aria-label="Next month">›</a>
                </div>
            </form>
        @endunless
    </div>

    @if ($stores->isEmpty())
        <div class="alert alert-info">You don't have any stores yet. Create a store to start projecting sales.</div>
    @else
        <style>
            .spc-monthnav { display:flex; align-items:center; gap:.25rem; background:#fff; border:1px solid #e6eaf0; border-radius:999px; padding:.2rem; }
            .spc-monthnav a { width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; border-radius:999px; color:#43536b; text-decoration:none; font-size:1.1rem; line-height:1; transition:background .12s; }
            .spc-monthnav a:hover { background:#eef3fb; color:#206bc4; }
            .spc-monthnav span { min-width:140px; text-align:center; font-weight:600; color:#1d2b3a; }

            .spc-summary { display:grid; grid-template-columns:repeat(3,1fr); gap:1rem; margin-bottom:1.25rem; }
            @media (max-width:640px){ .spc-summary{ grid-template-columns:1fr; } }
            .spc-stat { background:#fff; border:1px solid #e9edf3; border-radius:14px; padding:1rem 1.15rem; }
            .spc-stat .lbl { font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; color:#8a98a8; margin-bottom:.25rem; }
            .spc-stat .val { font-size:1.6rem; font-weight:700; color:#1d2b3a; line-height:1.1; }
            .spc-stat .val.up { color:#1f8a4c; } .spc-stat .val.down { color:#d63939; }

            .spc-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:1px; background:#e9edf3; border:1px solid #e9edf3; border-radius:14px; overflow:hidden; }
            .spc-dow { background:#f7f9fc; text-align:center; padding:.55rem; font-size:.72rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase; color:#9aa7b6; }
            .spc-cell { background:#fff; min-height:112px; padding:.5rem .55rem; display:flex; flex-direction:column; gap:.4rem; }
            .spc-cell.out { background:#fbfcfe; }
            .spc-cell.today { box-shadow: inset 0 0 0 2px #206bc4; }
            .spc-cell:hover { background:#fbfdff; }
            .spc-daynum { font-size:.85rem; font-weight:600; color:#5b6b7c; }
            .spc-cell.today .spc-daynum { color:#206bc4; }
            .spc-var { font-size:.72rem; font-weight:600; padding:.05rem .4rem; border-radius:999px; }
            .spc-var.up { background:#e9f7ef; color:#1f8a4c; } .spc-var.down { background:#fdecec; color:#d63939; }
            .spc-inputwrap { position:relative; }
            .spc-inputwrap .cur { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#aab4c0; font-size:.85rem; pointer-events:none; }
            .proj-input { width:100%; padding:.34rem .45rem .34rem 1.15rem; border:1px solid #dde3ec; border-radius:9px; font-size:.9rem; color:#1d2b3a; transition:border-color .12s, box-shadow .12s; -moz-appearance:textfield; }
            .proj-input::-webkit-outer-spin-button, .proj-input::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
            .proj-input:focus { outline:none; border-color:#206bc4; box-shadow:0 0 0 3px rgba(32,107,196,.14); }
            .proj-input.is-valid { border-color:#2fb344; box-shadow:0 0 0 3px rgba(47,179,68,.14); }
            .proj-input.is-invalid { border-color:#d63939; box-shadow:0 0 0 3px rgba(214,57,57,.14); }
            .spc-actual { font-size:.76rem; color:#aab4c0; margin-top:auto; }
            .spc-actual strong { color:#1f8a4c; font-weight:600; }
        </style>

        {{-- Month summary --}}
        <div class="spc-summary">
            <div class="spc-stat"><div class="lbl">Projected · month</div><div class="val">${{ number_format($totals['projected'], 2) }}</div></div>
            <div class="spc-stat"><div class="lbl">Actual · month</div><div class="val">${{ number_format($totals['actual'], 2) }}</div></div>
            <div class="spc-stat">
                <div class="lbl">Variance</div>
                <div class="val {{ $totals['variance'] >= 0 ? 'up' : 'down' }}">
                    {{ $totals['variance'] >= 0 ? '+' : '−' }}${{ number_format(abs($totals['variance']), 2) }}
                </div>
            </div>
        </div>

        {{-- Calendar grid --}}
        <div class="spc-grid">
            @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
                <div class="spc-dow">{{ $dow }}</div>
            @endforeach

            @foreach ($weeks as $week)
                @foreach ($week as $cell)
                    @php($isToday = $cell['date']->isToday())
                    <div class="spc-cell {{ $cell['in_month'] ? '' : 'out' }} {{ $isToday ? 'today' : '' }}">
                        @if ($cell['in_month'])
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="spc-daynum">{{ $cell['day'] }}</span>
                                @if ($cell['variance'] !== null)
                                    <span class="spc-var {{ $cell['variance'] >= 0 ? 'up' : 'down' }}" title="Actual − Projected">
                                        {{ $cell['variance'] >= 0 ? '+' : '−' }}${{ number_format(abs($cell['variance']), 0) }}
                                    </span>
                                @endif
                            </div>

                            <div class="spc-inputwrap">
                                <span class="cur">$</span>
                                <input type="number" step="0.01" min="0" class="proj-input" inputmode="decimal"
                                       data-date="{{ $cell['key'] }}"
                                       value="{{ $cell['projection'] !== null ? number_format($cell['projection'], 2, '.', '') : '' }}"
                                       placeholder="0.00">
                            </div>

                            <div class="spc-actual">
                                @if ($cell['actual'] !== null)
                                    Actual <strong>${{ number_format($cell['actual'], 2) }}</strong>
                                @else
                                    &mdash;
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            @endforeach
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
