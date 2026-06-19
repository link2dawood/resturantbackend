{{-- Phase 4 — Performance summary with period preset + MoM / YoY deltas. --}}
<style>
    .perf-bar { background: #fff; border: 1px solid #e6eaf0; border-radius: 16px; padding: 1.1rem 1.35rem; margin-bottom: 1.25rem;
        display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;
        box-shadow: 0 2px 6px rgba(16,30,54,.04); }
    .perf-bar__main { display: flex; flex-direction: column; gap: .15rem; }
    .perf-bar__eyebrow { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: #9aa7b6; font-weight: 700; }
    .perf-bar__value { font-size: 2rem; font-weight: 700; color: #1d2b3a; line-height: 1.1; }
    .perf-bar__label { font-size: .85rem; color: #8a98a8; }
    .perf-bar__right { display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; }
    .perf-delta { display: inline-flex; flex-direction: column; align-items: flex-start; }
    .perf-delta__pct { font-size: 1.05rem; font-weight: 700; }
    .perf-delta__pct.up { color: #1f8a4c; } .perf-delta__pct.down { color: #d63939; }
    .perf-delta__lbl { font-size: .76rem; color: #8a98a8; }
    .perf-delta__na { font-size: 1.05rem; font-weight: 600; color: #c0c8d2; }
    .perf-bar__filters { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
    .perf-select { border: 1px solid #dde3ec; border-radius: 999px; padding: .4rem .8rem; font-size: .88rem;
        font-weight: 600; color: #1d2b3a; background: #f7f9fc; cursor: pointer; }
    .perf-select:focus { outline: none; border-color: #206bc4; box-shadow: 0 0 0 3px rgba(32,107,196,.14); }
</style>

@isset($performance)
<div class="perf-bar">
    <div class="perf-bar__main">
        <span class="perf-bar__eyebrow">Performance</span>
        <span class="perf-bar__value">${{ number_format($performance['net_sales'], 2) }}</span>
        <span class="perf-bar__label">Net sales · {{ $performance['label'] }}</span>
    </div>

    <div class="perf-bar__right">
        {{-- Month-over-month / period-over-period --}}
        @if ($performance['show_pop'])
            <div class="perf-delta">
                @if (! empty($performance['pop']))
                    <span class="perf-delta__pct {{ $performance['pop']['up'] ? 'up' : 'down' }}">
                        {{ $performance['pop']['up'] ? '▲' : '▼' }} {{ abs($performance['pop']['pct']) }}%
                    </span>
                @else
                    <span class="perf-delta__na">—</span>
                @endif
                <span class="perf-delta__lbl">{{ $performance['pop_label'] ?: 'vs previous period' }}</span>
            </div>
        @endif

        {{-- Year-over-year --}}
        @if ($performance['show_yoy'])
            <div class="perf-delta">
                @if (! empty($performance['yoy']))
                    <span class="perf-delta__pct {{ $performance['yoy']['up'] ? 'up' : 'down' }}">
                        {{ $performance['yoy']['up'] ? '▲' : '▼' }} {{ abs($performance['yoy']['pct']) }}%
                    </span>
                @else
                    <span class="perf-delta__na">—</span>
                @endif
                <span class="perf-delta__lbl">year over year</span>
            </div>
        @endif

        {{-- Unified filters — drive the whole dashboard (period, month/year, store) --}}
        <form method="GET" action="{{ url()->current() }}" class="perf-bar__filters">
            <select name="period" id="dash-period" class="perf-select" onchange="this.form.submit()">
                @foreach ($periodLabels as $val => $lbl)
                    <option value="{{ $val }}" @selected($performance['preset'] === $val)>{{ $lbl }}</option>
                @endforeach
            </select>
            @isset($yearOptions)
                <select name="m" class="perf-select" title="Month" onchange="document.getElementById('dash-period').value='month'; this.form.submit()">
                    @for ($mn = 1; $mn <= 12; $mn++)
                        <option value="{{ $mn }}" @selected($mn === ($selectedMonthNum ?? 0))>{{ \Carbon\Carbon::create(null, $mn, 1)->format('M') }}</option>
                    @endfor
                </select>
                <select name="y" class="perf-select" title="Year" onchange="document.getElementById('dash-period').value='month'; this.form.submit()">
                    @foreach ($yearOptions as $yr)
                        <option value="{{ $yr }}" @selected($yr === ($selectedYear ?? 0))>{{ $yr }}</option>
                    @endforeach
                </select>
            @endisset
            @isset($storeOptions)
                @if ($storeOptions->isNotEmpty())
                    <select name="store" class="perf-select" title="Store" onchange="this.form.submit()">
                        <option value="all" @selected(($selectedStore ?? 'all') === 'all')>All stores</option>
                        @foreach ($storeOptions as $s)
                            <option value="{{ $s->id }}" @selected((string) ($selectedStore ?? '') === (string) $s->id)>{{ $s->store_info }}</option>
                        @endforeach
                    </select>
                @endif
            @endisset
        </form>
    </div>
</div>
@endisset
