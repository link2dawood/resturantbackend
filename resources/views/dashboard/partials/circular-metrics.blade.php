{{-- Phase 4 — Four circular headline metrics: Sales, Food Cost %, Payroll %, Rent %. --}}
<style>
    .metric-rings { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
    @media (max-width: 992px) { .metric-rings { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 540px) { .metric-rings { grid-template-columns: 1fr; } }
    .metric-ring-card {
        background: #fff; border: 1px solid #e6eaf0; border-radius: 16px;
        padding: 1.25rem 1rem; text-align: center;
        box-shadow: 0 2px 6px rgba(16,30,54,.04);
    }
    .metric-ring-card.is-empty { background: #f6f8fb; }
    .metric-ring { position: relative; width: 132px; height: 132px; margin: 0 auto .75rem; }
    .metric-ring svg { transform: rotate(-90deg); }
    .metric-ring__center {
        position: absolute; inset: 0; display: flex; flex-direction: column;
        align-items: center; justify-content: center;
    }
    .metric-ring__value { font-size: 1.25rem; font-weight: 700; color: #1d2b3a; line-height: 1.1; }
    .metric-ring__value.is-muted { color: #9aa7b6; font-weight: 600; }
    .metric-ring__label { font-weight: 600; color: #43536b; margin-bottom: .15rem; }
    .metric-ring__sub { font-size: .8rem; color: #8a98a8; min-height: 1rem; }
    .metric-ring__variance { display: inline-block; margin-top: .5rem; font-size: .82rem; font-weight: 600; padding: .15rem .55rem; border-radius: 999px; }
    .metric-ring__variance.ahead { background: #e9f7ee; color: #1f8a4c; }
    .metric-ring__variance.behind { background: #fdecec; color: #d63939; }
    .metric-ring__variance.none { background: #eef1f5; color: #9aa7b6; }
    .metric-rings__bar { display: flex; align-items: center; gap: .5rem; margin: 0 0 .7rem; }
    .metric-rings__bar label { font-size: .85rem; color: #8a98a8; font-weight: 600; }
    .metric-rings__select { border: 1px solid #dde3ec; border-radius: 10px; padding: .35rem .6rem; font-size: .9rem; font-weight: 600; color: #1d2b3a; background: #fff; cursor: pointer; }
    .metric-rings__select:focus { outline: none; border-color: #206bc4; box-shadow: 0 0 0 3px rgba(32,107,196,.14); }
    .metric-rings__bar { flex-wrap: wrap; }
    .metric-rings__sep { color: #c5cdd8; }
</style>

@isset($yearOptions)
    <form method="GET" action="{{ url()->current() }}" class="metric-rings__bar">
        <label for="ring-month">Showing</label>
        <select id="ring-month" name="m" class="metric-rings__select" onchange="this.form.submit()">
            @for ($mn = 1; $mn <= 12; $mn++)
                <option value="{{ $mn }}" @selected($mn === ($selectedMonthNum ?? 0))>{{ \Carbon\Carbon::create(null, $mn, 1)->format('F') }}</option>
            @endfor
        </select>
        <select name="y" class="metric-rings__select" onchange="this.form.submit()">
            @foreach ($yearOptions as $yr)
                <option value="{{ $yr }}" @selected($yr === ($selectedYear ?? 0))>{{ $yr }}</option>
            @endforeach
        </select>
        @isset($storeOptions)
            @if ($storeOptions->isNotEmpty())
                <span class="metric-rings__sep">·</span>
                <select name="store" class="metric-rings__select" onchange="this.form.submit()">
                    <option value="all" @selected(($selectedStore ?? 'all') === 'all')>All stores</option>
                    @foreach ($storeOptions as $s)
                        <option value="{{ $s->id }}" @selected((string) ($selectedStore ?? '') === (string) $s->id)>{{ $s->store_info }}</option>
                    @endforeach
                </select>
            @endif
        @endisset
    </form>
@endisset

<div class="metric-rings">
    @foreach (['sales', 'food', 'payroll', 'rent'] as $k)
        @php($m = $circularMetrics[$k])
        @php($r = 56)
        @php($circ = 2 * M_PI * $r)
        @php($pct = $m['has_data'] ? $m['percent'] : 0)
        @php($offset = $circ * (1 - $pct / 100))
        @php($color = ! $m['has_data'] ? '#d3dae3' : ($m['ahead'] ? '#2fb344' : '#d63939'))
        <div class="metric-ring-card {{ $m['has_data'] ? '' : 'is-empty' }}">
            <div class="metric-ring__label">{{ $m['label'] }}</div>
            <div class="metric-ring">
                <svg width="132" height="132" viewBox="0 0 132 132">
                    <circle cx="66" cy="66" r="{{ $r }}" fill="none" stroke="#eef1f5" stroke-width="12"/>
                    <circle cx="66" cy="66" r="{{ $r }}" fill="none" stroke="{{ $color }}" stroke-width="12"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $circ }}"
                            stroke-dashoffset="{{ $offset }}"/>
                </svg>
                <div class="metric-ring__center">
                    <span class="metric-ring__value {{ $m['has_data'] ? '' : 'is-muted' }}">{{ $m['display'] }}</span>
                </div>
            </div>
            <div class="metric-ring__sub">{{ $m['has_data'] ? $m['sub'] : 'No data yet' }}</div>
            @if ($m['has_data'] && ! empty($m['variance_label']))
                <span class="metric-ring__variance {{ $m['ahead'] ? 'ahead' : 'behind' }}">
                    {{ $m['variance_label'] }}
                </span>
            @elseif (! $m['has_data'])
                <span class="metric-ring__variance none">Awaiting reports</span>
            @endif
        </div>
    @endforeach
</div>
