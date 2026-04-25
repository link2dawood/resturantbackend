@extends('layouts.tabler')

@section('title', 'Annual Profit & Loss — ' . $selectedYear)

@section('content')
<div class="container mt-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">
                Annual Profit &amp; Loss
            </h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">
                Full-year COA breakdown, with quick monthly and all-years generation
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.profit-loss.index') }}" class="btn btn-outline-secondary btn-sm">
                Period P&amp;L
            </a>
            @can('reports', 'export')
            <a id="exportCsvBtn"
               href="{{ route('admin.reports.profit-loss.export.csv', ['start_date' => $selectedYear.'-01-01', 'end_date' => $selectedYear.'-12-31', 'store_id' => $storeId]) }}"
               class="btn btn-outline-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export CSV
            </a>
            <a id="exportPdfBtn"
               href="{{ route('admin.reports.profit-loss.export.pdf', ['start_date' => $selectedYear.'-01-01', 'end_date' => $selectedYear.'-12-31', 'store_id' => $storeId]) }}"
               class="btn btn-outline-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Export PDF
            </a>
            @endcan
            <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect width="12" height="8" x="6" y="14" rx="1"/>
                </svg>
                Print
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form id="annualFilterForm" action="{{ route('admin.reports.profit-loss.annual') }}" method="GET" class="row g-3 align-items-end">
                @canViewAllStores
                <div class="col-md-4">
                    <label class="form-label">Store</label>
                    <select class="form-select" name="store_id" id="annualStoreSelect">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" name="store_id" value="{{ $storeId }}">
                @endcanViewAllStores

                <div class="col-md-2">
                    <label class="form-label">Report Type</label>
                    <select class="form-select" name="report_type" id="annualReportTypeSelect">
                        <option value="annual" {{ $reportType === 'annual' ? 'selected' : '' }}>Annual</option>
                        <option value="monthly" {{ $reportType === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="all_years" {{ $reportType === 'all_years' ? 'selected' : '' }}>All Years</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Year</label>
                    <select class="form-select" name="year" id="annualYearSelect">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3" id="annualMonthFilter">
                    <label class="form-label">Month</label>
                    <select class="form-select" name="month" id="annualMonthSelect">
                        @foreach($months as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" {{ $selectedMonth == $monthNumber ? 'selected' : '' }}>{{ $monthName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                        Generate
                    </button>
                </div>

                <div class="col-12">
                    <div class="form-text">
                        Annual refreshes this page. Monthly and All Years open the standard Period P&amp;L with the correct date range.
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary KPI Cards --}}
    @php
        $pl        = $data['pl'] ?? [];
        $revTotal  = $pl['revenue']['annual_total'] ?? 0;
        $cogsTotal = $pl['cogs']['annual_total'] ?? 0;
        $gpTotal   = $pl['grossProfit']['annual_total'] ?? 0;
        $gpMargin  = $pl['grossProfit']['avg_margin'] ?? 0;
        $expTotal  = $pl['operatingExpenses']['annual_total'] ?? 0;
        $npTotal   = $pl['netProfit']['annual_total'] ?? 0;
        $npMargin  = $pl['netProfit']['avg_margin'] ?? 0;
    @endphp

    <div class="row g-3 mb-4" id="kpiCards">
        <div class="col-6 col-md-3">
            <div class="card text-center h-100" style="border-left: 4px solid #34a853;">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Total Revenue</div>
                    <div class="fw-bold fs-5 text-success" id="kpiRevenue">${{ number_format($revTotal, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100" style="border-left: 4px solid #fbbc04;">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Gross Profit</div>
                    <div class="fw-bold fs-5" id="kpiGrossProfit" style="color: {{ $gpTotal >= 0 ? '#34a853' : '#ea4335' }}">${{ number_format($gpTotal, 2) }} <small class="text-muted fw-normal fs-6">({{ number_format($gpMargin, 1) }}%)</small></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100" style="border-left: 4px solid #ea4335;">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Total Expenses</div>
                    <div class="fw-bold fs-5 text-danger" id="kpiExpenses">${{ number_format($cogsTotal + $expTotal, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center h-100" style="border-left: 4px solid #4285f4;">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Net Profit</div>
                    <div class="fw-bold fs-5" id="kpiNetProfit" style="color: {{ $npTotal >= 0 ? '#34a853' : '#ea4335' }}">${{ number_format($npTotal, 2) }} <small class="text-muted fw-normal fs-6">({{ number_format($npMargin, 1) }}%)</small></div>
                </div>
            </div>
        </div>
    </div>

    @php
        $coaActivitySummary = $pl['coaActivitySummary'] ?? [
            'income' => ['rows' => [], 'entry_count' => 0, 'total_amount' => 0],
            'expense' => ['rows' => [], 'entry_count' => 0, 'total_amount' => 0, 'monthly_totals' => array_fill_keys(range(1, 12), 0)],
        ];
        $coaActivityMonths = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];
    @endphp

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">COA Activity Summary</h3>
        </div>
        <div class="card-body">
            <div class="text-muted small mb-3">
                This report shows all reportable P&amp;L COAs with month-by-month activity and annual totals for the selected year.
            </div>
            <div class="row g-4">
                <div class="col-12">
                    <h4 class="h6 mb-2">Income by COA</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" id="incomeCoaActivityTable">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th>COA No.</th>
                                    <th>COA Name</th>
                                    @foreach($coaActivityMonths as $monthLabel)
                                    <th class="text-end">{{ $monthLabel }}</th>
                                    @endforeach
                                    <th class="text-end">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($coaActivitySummary['income']['rows'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['account_code'] }}</td>
                                    <td>{{ $row['account_name'] }}</td>
                                    @foreach(array_keys($coaActivityMonths) as $monthNumber)
                                    <td class="text-end text-success">${{ number_format($row['monthly_amounts'][$monthNumber] ?? 0, 2) }}</td>
                                    @endforeach
                                    <td class="text-end text-success">${{ number_format($row['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="15" class="text-center text-muted py-3">No income COA activity found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #f8f9fa; font-weight: 600;">
                                    <td colspan="2">Total Income Activity</td>
                                    @foreach(array_keys($coaActivityMonths) as $monthNumber)
                                    <td class="text-end text-success" id="incomeCoaActivityMonthTotal{{ $monthNumber }}">${{ number_format($coaActivitySummary['income']['monthly_totals'][$monthNumber] ?? 0, 2) }}</td>
                                    @endforeach
                                    <td class="text-end text-success" id="incomeCoaActivityAmountTotal">${{ number_format($coaActivitySummary['income']['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <h4 class="h6 mb-2">Expense by COA</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" id="expenseCoaActivityTable">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th>COA No.</th>
                                    <th>COA Name</th>
                                    @foreach($coaActivityMonths as $monthLabel)
                                    <th class="text-end">{{ $monthLabel }}</th>
                                    @endforeach
                                    <th class="text-end">Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($coaActivitySummary['expense']['rows'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['account_code'] }}</td>
                                    <td>{{ $row['account_name'] }}</td>
                                    @foreach(array_keys($coaActivityMonths) as $monthNumber)
                                    <td class="text-end text-danger">${{ number_format($row['monthly_amounts'][$monthNumber] ?? 0, 2) }}</td>
                                    @endforeach
                                    <td class="text-end text-danger">${{ number_format($row['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="15" class="text-center text-muted py-3">No expense COA activity found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #f8f9fa; font-weight: 600;">
                                    <td colspan="2">Total Expense Activity</td>
                                    @foreach(array_keys($coaActivityMonths) as $monthNumber)
                                    <td class="text-end text-danger" id="expenseCoaActivityMonthTotal{{ $monthNumber }}">${{ number_format($coaActivitySummary['expense']['monthly_totals'][$monthNumber] ?? 0, 2) }}</td>
                                    @endforeach
                                    <td class="text-end text-danger" id="expenseCoaActivityAmountTotal">${{ number_format($coaActivitySummary['expense']['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Annual P&L Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div id="plTableWrapper" style="overflow-x: auto;">
                <table class="table table-bordered mb-0" style="font-size: 0.8rem; min-width: 1200px;" id="annualPlTable">
                    <thead>
                        <tr style="background-color: #f8f9fa; position: sticky; top: 0; z-index: 1;">
                            <th style="width: 220px; min-width: 220px; font-weight: 600; position: sticky; left: 0; background: #f8f9fa; z-index: 2;">Account</th>
                            @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $mn)
                            <th class="text-end" style="font-weight: 600; min-width: 90px;">{{ $mn }}</th>
                            @endforeach
                            <th class="text-end" style="font-weight: 600; min-width: 110px; background: #f1f3f4;">Annual Total</th>
                        </tr>
                    </thead>
                    <tbody>

                    @php
                        $months = range(1, 12);
                        $fmt = fn($v) => '$' . number_format(abs($v), 2);
                        $fmtNeg = fn($v) => $v < 0 ? '(' . $fmt($v) . ')' : $fmt($v);
                    @endphp

                    {{-- ── REVENUE ──────────────────────────────────────────── --}}
                    <tr style="background-color: #e8f5e9;">
                        <td colspan="14" style="font-weight: 700; font-size: 0.875rem; padding: 10px 12px; letter-spacing: 0.05em; position: sticky; left: 0; background: #e8f5e9;">REVENUE</td>
                    </tr>
                    @foreach(($pl['revenue']['items'] ?? []) as $item)
                    <tr>
                        <td style="padding-left: 1.5rem; position: sticky; left: 0; background: #fff;">{{ $item['name'] }}</td>
                        @foreach($months as $m)
                        <td class="text-end text-success">{{ $fmt($item['monthly'][$m] ?? 0) }}</td>
                        @endforeach
                        <td class="text-end text-success fw-semibold" style="background: #f1f3f4;">{{ $fmt($item['annual_total'] ?? 0) }}</td>
                    </tr>
                    @endforeach
                    <tr style="background-color: #c8e6c9; font-weight: 700;">
                        <td style="position: sticky; left: 0; background: #c8e6c9;">TOTAL REVENUE</td>
                        @foreach($months as $m)
                        <td class="text-end text-success">{{ $fmt($pl['revenue']['monthly_totals'][$m] ?? 0) }}</td>
                        @endforeach
                        <td class="text-end text-success" style="background: #b9dfba;">{{ $fmt($pl['revenue']['annual_total'] ?? 0) }}</td>
                    </tr>

                    {{-- ── COGS ─────────────────────────────────────────────── --}}
                    <tr style="background-color: #fff3e0;">
                        <td colspan="14" style="font-weight: 700; font-size: 0.875rem; padding: 10px 12px; letter-spacing: 0.05em; position: sticky; left: 0; background: #fff3e0;">COST OF GOODS SOLD (COGS)</td>
                    </tr>
                    @forelse(($pl['cogs']['items'] ?? []) as $item)
                    <tr>
                        <td style="padding-left: 1.5rem; position: sticky; left: 0; background: #fff;">{{ $item['name'] }}</td>
                        @foreach($months as $m)
                        <td class="text-end text-danger">({{ $fmt($item['monthly'][$m] ?? 0) }})</td>
                        @endforeach
                        <td class="text-end text-danger fw-semibold" style="background: #f1f3f4;">({{ $fmt($item['annual_total'] ?? 0) }})</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="14" class="text-center text-muted py-2" style="font-style: italic;">No COGS transactions for {{ $selectedYear }}</td>
                    </tr>
                    @endforelse
                    <tr style="background-color: #ffe0b2; font-weight: 700;">
                        <td style="position: sticky; left: 0; background: #ffe0b2;">TOTAL COGS</td>
                        @foreach($months as $m)
                        <td class="text-end text-danger">({{ $fmt($pl['cogs']['monthly_totals'][$m] ?? 0) }})</td>
                        @endforeach
                        <td class="text-end text-danger" style="background: #f5cda0;">({{ $fmt($pl['cogs']['annual_total'] ?? 0) }})</td>
                    </tr>

                    {{-- ── GROSS PROFIT ─────────────────────────────────────── --}}
                    <tr style="background-color: #e8f5e9; font-weight: 700; font-size: 0.9rem;">
                        <td style="position: sticky; left: 0; background: #e8f5e9;">GROSS PROFIT</td>
                        @foreach($months as $m)
                        @php $gp = $pl['grossProfit']['monthly'][$m] ?? 0; @endphp
                        <td class="text-end fw-bold {{ $gp >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $gp >= 0 ? $fmt($gp) : '(' . $fmt($gp) . ')' }}
                        </td>
                        @endforeach
                        @php $gpAnn = $pl['grossProfit']['annual_total'] ?? 0; @endphp
                        <td class="text-end fw-bold {{ $gpAnn >= 0 ? 'text-success' : 'text-danger' }}" style="background: #f1f3f4;">
                            {{ $gpAnn >= 0 ? $fmt($gpAnn) : '(' . $fmt($gpAnn) . ')' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding-left: 1.5rem; color: #666; position: sticky; left: 0; background: #fff;">Gross Margin %</td>
                        @foreach($months as $m)
                        <td class="text-end text-muted">{{ number_format($pl['grossProfit']['monthly_margins'][$m] ?? 0, 1) }}%</td>
                        @endforeach
                        <td class="text-end text-muted" style="background: #f1f3f4;">{{ number_format($pl['grossProfit']['avg_margin'] ?? 0, 1) }}%</td>
                    </tr>

                    {{-- ── OPERATING EXPENSES ───────────────────────────────── --}}
                    <tr style="background-color: #fce4ec;">
                        <td colspan="14" style="font-weight: 700; font-size: 0.875rem; padding: 10px 12px; letter-spacing: 0.05em; position: sticky; left: 0; background: #fce4ec;">OPERATING EXPENSES</td>
                    </tr>
                    @forelse(($pl['operatingExpenses']['items'] ?? []) as $item)
                        @if(isset($item['items']))
                        {{-- Parent category with sub-items --}}
                        <tr style="background-color: #fff8f9;">
                            <td style="padding-left: 1rem; font-weight: 600; position: sticky; left: 0; background: #fff8f9;">{{ $item['name'] }}</td>
                            @foreach($months as $m)
                            <td class="text-end text-danger fw-semibold">({{ $fmt($item['monthly'][$m] ?? 0) }})</td>
                            @endforeach
                            <td class="text-end text-danger fw-semibold" style="background: #f1f3f4;">({{ $fmt($item['annual_total'] ?? 0) }})</td>
                        </tr>
                        @foreach($item['items'] as $sub)
                        <tr>
                            <td style="padding-left: 2.5rem; color: #555; position: sticky; left: 0; background: #fff;">
                                {{ $sub['name'] }}
                                @if($sub['coa_id'])
                                <a href="{{ route('admin.reports.profit-loss.drill-down', ['coa_id' => $sub['coa_id'], 'start_date' => $selectedYear.'-01-01', 'end_date' => $selectedYear.'-12-31', 'store_id' => $storeId]) }}"
                                   class="text-decoration-none ms-1" title="View transactions">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                @endif
                            </td>
                            @foreach($months as $m)
                            <td class="text-end text-danger">({{ $fmt($sub['monthly'][$m] ?? 0) }})</td>
                            @endforeach
                            <td class="text-end text-danger" style="background: #f1f3f4;">({{ $fmt($sub['annual_total'] ?? 0) }})</td>
                        </tr>
                        @endforeach
                        @else
                        {{-- Standalone expense line --}}
                        <tr>
                            <td style="padding-left: 1.5rem; position: sticky; left: 0; background: #fff;">
                                {{ $item['name'] }}
                                @if($item['coa_id'])
                                <a href="{{ route('admin.reports.profit-loss.drill-down', ['coa_id' => $item['coa_id'], 'start_date' => $selectedYear.'-01-01', 'end_date' => $selectedYear.'-12-31', 'store_id' => $storeId]) }}"
                                   class="text-decoration-none ms-1" title="View transactions">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                @endif
                            </td>
                            @foreach($months as $m)
                            <td class="text-end text-danger">({{ $fmt($item['monthly'][$m] ?? 0) }})</td>
                            @endforeach
                            <td class="text-end text-danger" style="background: #f1f3f4;">({{ $fmt($item['annual_total'] ?? 0) }})</td>
                        </tr>
                        @endif
                    @empty
                    <tr>
                        <td colspan="14" class="text-center text-muted py-2" style="font-style: italic;">No expense transactions for {{ $selectedYear }}</td>
                    </tr>
                    @endforelse
                    <tr style="background-color: #f8bbd0; font-weight: 700;">
                        <td style="position: sticky; left: 0; background: #f8bbd0;">TOTAL OPERATING EXPENSES</td>
                        @foreach($months as $m)
                        <td class="text-end text-danger">({{ $fmt($pl['operatingExpenses']['monthly_totals'][$m] ?? 0) }})</td>
                        @endforeach
                        <td class="text-end text-danger" style="background: #f0a8bc;">({{ $fmt($pl['operatingExpenses']['annual_total'] ?? 0) }})</td>
                    </tr>

                    {{-- ── NET PROFIT ────────────────────────────────────────── --}}
                    <tr style="background-color: #c8e6c9; font-weight: 700; font-size: 0.95rem;">
                        <td style="position: sticky; left: 0; background: #c8e6c9;">NET PROFIT / (LOSS)</td>
                        @foreach($months as $m)
                        @php $np = $pl['netProfit']['monthly'][$m] ?? 0; @endphp
                        <td class="text-end fw-bold {{ $np >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $np >= 0 ? $fmt($np) : '(' . $fmt($np) . ')' }}
                        </td>
                        @endforeach
                        @php $npAnn = $pl['netProfit']['annual_total'] ?? 0; @endphp
                        <td class="text-end fw-bold {{ $npAnn >= 0 ? 'text-success' : 'text-danger' }}" style="background: #b9dfba;">
                            {{ $npAnn >= 0 ? $fmt($npAnn) : '(' . $fmt($npAnn) . ')' }}
                        </td>
                    </tr>
                    <tr style="background-color: #e8f5e9;">
                        <td style="padding-left: 1.5rem; color: #555; position: sticky; left: 0; background: #e8f5e9;">Net Margin %</td>
                        @foreach($months as $m)
                        <td class="text-end text-muted">{{ number_format($pl['netProfit']['monthly_margins'][$m] ?? 0, 1) }}%</td>
                        @endforeach
                        <td class="text-end text-muted" style="background: #d9eed9;">{{ number_format($pl['netProfit']['avg_margin'] ?? 0, 1) }}%</td>
                    </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const form        = document.getElementById('annualFilterForm');
    const yearSelect  = document.getElementById('annualYearSelect');
    const monthSelect = document.getElementById('annualMonthSelect');
    const reportTypeSelect = document.getElementById('annualReportTypeSelect');
    const storeField  = document.getElementById('annualStoreSelect') || form?.querySelector('[name="store_id"]');
    const storeSelect = document.getElementById('annualStoreSelect');
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    const exportPdfBtn = document.getElementById('exportPdfBtn');
    const monthFilter = document.getElementById('annualMonthFilter');

    function syncFilterVisibility() {
        const reportType = reportTypeSelect ? reportTypeSelect.value : 'annual';
        const showMonth = reportType === 'monthly';
        const showYear = reportType !== 'all_years';

        if (monthFilter) {
            monthFilter.style.display = showMonth ? '' : 'none';
        }

        if (yearSelect) {
            yearSelect.disabled = !showYear;
            yearSelect.closest('.col-md-3')?.classList.toggle('opacity-50', !showYear);
        }
    }

    // AJAX refresh when year or store changes
    function buildParams() {
        const p = new URLSearchParams();
        if (yearSelect)  p.set('year',     yearSelect.value);
        if (monthSelect) p.set('month',    monthSelect.value);
        if (reportTypeSelect) p.set('report_type', reportTypeSelect.value);
        if (storeField && storeField.value) p.set('store_id', storeField.value);
        return p;
    }

    function money(v) {
        return '$' + Math.abs(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function cell(v, cls) {
        const neg = v < 0;
        const txt = neg ? '(' + money(v) + ')' : money(v);
        return `<td class="text-end ${cls}">${txt}</td>`;
    }

    function pct(v) {
        return `<td class="text-end text-muted">${parseFloat(v).toFixed(1)}%</td>`;
    }

    function renderActivityMoney(value) {
        return '$' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function buildMonthlyCoaActivityRows(section, amountClass, emptyMessage) {
        const rows = section?.rows || [];
        const months = [1,2,3,4,5,6,7,8,9,10,11,12];

        if (!rows.length) {
            return `<tr><td colspan="15" class="text-center text-muted py-3">${emptyMessage}</td></tr>`;
        }

        return rows.map(row => `
            <tr>
                <td>${row.account_code || ''}</td>
                <td>${row.account_name || ''}</td>
                ${months.map(month => `<td class="text-end ${amountClass}">${renderActivityMoney(row.monthly_amounts?.[month] || 0)}</td>`).join('')}
                <td class="text-end ${amountClass}">${renderActivityMoney(row.total_amount || 0)}</td>
            </tr>
        `).join('');
    }

    function buildExpenseCoaActivityRows(section, amountClass) {
        return buildMonthlyCoaActivityRows(section, amountClass, 'No expense COA activity found.');
    }

    function buildTable(pl) {
        const months = [1,2,3,4,5,6,7,8,9,10,11,12];
        let html = '';

        // ── REVENUE ──────────────────────────────────────────────────────────
        html += `<tr style="background-color:#e8f5e9;"><td colspan="14" style="font-weight:700;font-size:0.875rem;padding:10px 12px;letter-spacing:0.05em;position:sticky;left:0;background:#e8f5e9;">REVENUE</td></tr>`;
        (pl.revenue.items || []).forEach(item => {
            html += `<tr><td style="padding-left:1.5rem;position:sticky;left:0;background:#fff;">${item.name}</td>`;
            months.forEach(m => { html += cell(item.monthly[m] || 0, 'text-success'); });
            html += cell(item.annual_total || 0, 'text-success fw-semibold') + '</tr>';
        });
        html += `<tr style="background-color:#c8e6c9;font-weight:700;"><td style="position:sticky;left:0;background:#c8e6c9;">TOTAL REVENUE</td>`;
        months.forEach(m => { html += cell(pl.revenue.monthly_totals[m] || 0, 'text-success'); });
        html += cell(pl.revenue.annual_total || 0, 'text-success') + '</tr>';

        // ── COGS ─────────────────────────────────────────────────────────────
        html += `<tr style="background-color:#fff3e0;"><td colspan="14" style="font-weight:700;font-size:0.875rem;padding:10px 12px;letter-spacing:0.05em;position:sticky;left:0;background:#fff3e0;">COST OF GOODS SOLD (COGS)</td></tr>`;
        (pl.cogs.items || []).forEach(item => {
            html += `<tr><td style="padding-left:1.5rem;position:sticky;left:0;background:#fff;">${item.name}</td>`;
            months.forEach(m => { html += `<td class="text-end text-danger">(${money(item.monthly[m] || 0)})</td>`; });
            html += `<td class="text-end text-danger fw-semibold" style="background:#f1f3f4;">(${money(item.annual_total || 0)})</td></tr>`;
        });
        html += `<tr style="background-color:#ffe0b2;font-weight:700;"><td style="position:sticky;left:0;background:#ffe0b2;">TOTAL COGS</td>`;
        months.forEach(m => { html += `<td class="text-end text-danger">(${money(pl.cogs.monthly_totals[m] || 0)})</td>`; });
        html += `<td class="text-end text-danger" style="background:#f5cda0;">(${money(pl.cogs.annual_total || 0)})</td></tr>`;

        // ── GROSS PROFIT ─────────────────────────────────────────────────────
        html += `<tr style="background-color:#e8f5e9;font-weight:700;font-size:0.9rem;"><td style="position:sticky;left:0;background:#e8f5e9;">GROSS PROFIT</td>`;
        months.forEach(m => {
            const gp = pl.grossProfit.monthly[m] || 0;
            const cls = gp >= 0 ? 'text-success' : 'text-danger';
            html += `<td class="text-end fw-bold ${cls}">${gp >= 0 ? money(gp) : '('+money(gp)+')'}</td>`;
        });
        const gpAnn = pl.grossProfit.annual_total || 0;
        html += `<td class="text-end fw-bold ${gpAnn >= 0 ? 'text-success' : 'text-danger'}" style="background:#f1f3f4;">${gpAnn >= 0 ? money(gpAnn) : '('+money(gpAnn)+')'}</td></tr>`;

        html += `<tr><td style="padding-left:1.5rem;color:#666;position:sticky;left:0;background:#fff;">Gross Margin %</td>`;
        months.forEach(m => { html += pct(pl.grossProfit.monthly_margins[m] || 0); });
        html += `<td class="text-end text-muted" style="background:#f1f3f4;">${parseFloat(pl.grossProfit.avg_margin || 0).toFixed(1)}%</td></tr>`;

        // ── OPERATING EXPENSES ───────────────────────────────────────────────
        html += `<tr style="background-color:#fce4ec;"><td colspan="14" style="font-weight:700;font-size:0.875rem;padding:10px 12px;letter-spacing:0.05em;position:sticky;left:0;background:#fce4ec;">OPERATING EXPENSES</td></tr>`;
        (pl.operatingExpenses.items || []).forEach(item => {
            if (item.items) {
                html += `<tr style="background-color:#fff8f9;"><td style="padding-left:1rem;font-weight:600;position:sticky;left:0;background:#fff8f9;">${item.name}</td>`;
                months.forEach(m => { html += `<td class="text-end text-danger fw-semibold">(${money(item.monthly[m] || 0)})</td>`; });
                html += `<td class="text-end text-danger fw-semibold" style="background:#f1f3f4;">(${money(item.annual_total || 0)})</td></tr>`;
                item.items.forEach(sub => {
                    html += `<tr><td style="padding-left:2.5rem;color:#555;position:sticky;left:0;background:#fff;">${sub.name}</td>`;
                    months.forEach(m => { html += `<td class="text-end text-danger">(${money(sub.monthly[m] || 0)})</td>`; });
                    html += `<td class="text-end text-danger" style="background:#f1f3f4;">(${money(sub.annual_total || 0)})</td></tr>`;
                });
            } else {
                html += `<tr><td style="padding-left:1.5rem;position:sticky;left:0;background:#fff;">${item.name}</td>`;
                months.forEach(m => { html += `<td class="text-end text-danger">(${money(item.monthly[m] || 0)})</td>`; });
                html += `<td class="text-end text-danger" style="background:#f1f3f4;">(${money(item.annual_total || 0)})</td></tr>`;
            }
        });
        html += `<tr style="background-color:#f8bbd0;font-weight:700;"><td style="position:sticky;left:0;background:#f8bbd0;">TOTAL OPERATING EXPENSES</td>`;
        months.forEach(m => { html += `<td class="text-end text-danger">(${money(pl.operatingExpenses.monthly_totals[m] || 0)})</td>`; });
        html += `<td class="text-end text-danger" style="background:#f0a8bc;">(${money(pl.operatingExpenses.annual_total || 0)})</td></tr>`;

        // ── NET PROFIT ───────────────────────────────────────────────────────
        html += `<tr style="background-color:#c8e6c9;font-weight:700;font-size:0.95rem;"><td style="position:sticky;left:0;background:#c8e6c9;">NET PROFIT / (LOSS)</td>`;
        months.forEach(m => {
            const np  = pl.netProfit.monthly[m] || 0;
            const cls = np >= 0 ? 'text-success' : 'text-danger';
            html += `<td class="text-end fw-bold ${cls}">${np >= 0 ? money(np) : '('+money(np)+')'}</td>`;
        });
        const npAnn = pl.netProfit.annual_total || 0;
        html += `<td class="text-end fw-bold ${npAnn >= 0 ? 'text-success' : 'text-danger'}" style="background:#b9dfba;">${npAnn >= 0 ? money(npAnn) : '('+money(npAnn)+')'}</td></tr>`;

        html += `<tr style="background-color:#e8f5e9;"><td style="padding-left:1.5rem;color:#555;position:sticky;left:0;background:#e8f5e9;">Net Margin %</td>`;
        months.forEach(m => { html += pct(pl.netProfit.monthly_margins[m] || 0); });
        html += `<td class="text-end text-muted" style="background:#d9eed9;">${parseFloat(pl.netProfit.avg_margin || 0).toFixed(1)}%</td></tr>`;

        return html;
    }

    function updateKpis(pl) {
        const rev  = pl.revenue.annual_total || 0;
        const cogs = pl.cogs.annual_total || 0;
        const gp   = pl.grossProfit.annual_total || 0;
        const gpM  = pl.grossProfit.avg_margin || 0;
        const exp  = pl.operatingExpenses.annual_total || 0;
        const np   = pl.netProfit.annual_total || 0;
        const npM  = pl.netProfit.avg_margin || 0;

        const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerHTML = val; };
        set('kpiRevenue',     money(rev));
        set('kpiGrossProfit', `${gp < 0 ? '('+money(gp)+')' : money(gp)} <small class="text-muted fw-normal fs-6">(${parseFloat(gpM).toFixed(1)}%)</small>`);
        set('kpiExpenses',    money(cogs + exp));
        set('kpiNetProfit',   `${np < 0 ? '('+money(np)+')' : money(np)} <small class="text-muted fw-normal fs-6">(${parseFloat(npM).toFixed(1)}%)</small>`);
    }

    async function refresh() {
        const params = buildParams();
        const tbody  = document.querySelector('#annualPlTable tbody');
        const incomeCoaActivityBody = document.querySelector('#incomeCoaActivityTable tbody');
        const expenseCoaActivityBody = document.querySelector('#expenseCoaActivityTable tbody');
        if (!tbody) return;

        tbody.style.opacity = '0.4';
        if (incomeCoaActivityBody) incomeCoaActivityBody.style.opacity = '0.4';
        if (expenseCoaActivityBody) expenseCoaActivityBody.style.opacity = '0.4';

        try {
            const res  = await fetch(`/api/reports/pl/annual?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!res.ok) throw new Error('API error');
            const json = await res.json();
            const pl   = json.pl;

            tbody.innerHTML = buildTable(pl);
            updateKpis(pl);
            if (incomeCoaActivityBody) {
                incomeCoaActivityBody.innerHTML = buildMonthlyCoaActivityRows(pl.coaActivitySummary?.income, 'text-success', 'No income COA activity found.');
            }
            if (expenseCoaActivityBody) {
                expenseCoaActivityBody.innerHTML = buildExpenseCoaActivityRows(pl.coaActivitySummary?.expense, 'text-danger');
            }
            const incomeAmountTotal = document.getElementById('incomeCoaActivityAmountTotal');
            const incomeMonthTotalEls = Array.from({ length: 12 }, (_, index) => document.getElementById(`incomeCoaActivityMonthTotal${index + 1}`));
            const expenseAmountTotal = document.getElementById('expenseCoaActivityAmountTotal');
            const expenseMonthTotalEls = Array.from({ length: 12 }, (_, index) => document.getElementById(`expenseCoaActivityMonthTotal${index + 1}`));
            incomeMonthTotalEls.forEach((el, index) => {
                if (el) {
                    el.textContent = renderActivityMoney(pl.coaActivitySummary?.income?.monthly_totals?.[index + 1] || 0);
                }
            });
            if (incomeAmountTotal) {
                incomeAmountTotal.textContent = renderActivityMoney(pl.coaActivitySummary?.income?.total_amount || 0);
            }
            expenseMonthTotalEls.forEach((el, index) => {
                if (el) {
                    el.textContent = renderActivityMoney(pl.coaActivitySummary?.expense?.monthly_totals?.[index + 1] || 0);
                }
            });
            if (expenseAmountTotal) {
                expenseAmountTotal.textContent = renderActivityMoney(pl.coaActivitySummary?.expense?.total_amount || 0);
            }

            // Update export links
            if (exportCsvBtn || exportPdfBtn) {
                const yr = params.get('year') || '{{ $selectedYear }}';
                const sid = params.get('store_id') || '';
                const exportParams = new URLSearchParams({ start_date: yr+'-01-01', end_date: yr+'-12-31' });
                if (sid) exportParams.set('store_id', sid);
                if (exportCsvBtn) {
                    exportCsvBtn.href = `/reports/profit-loss/export/csv?${exportParams}`;
                }
                if (exportPdfBtn) {
                    exportPdfBtn.href = `/reports/profit-loss/export/pdf?${exportParams}`;
                }
            }

            // Update URL without reload
            history.pushState({}, '', `?${params}`);
        } catch (e) {
            console.error('Annual P&L refresh failed', e);
        } finally {
            tbody.style.opacity = '1';
            if (incomeCoaActivityBody) incomeCoaActivityBody.style.opacity = '1';
            if (expenseCoaActivityBody) expenseCoaActivityBody.style.opacity = '1';
        }
    }

    // Intercept form submit to do AJAX refresh instead of page reload
    if (form) {
        form.addEventListener('submit', function (e) {
            if (reportTypeSelect && reportTypeSelect.value !== 'annual') {
                return;
            }

            e.preventDefault();
            refresh();
        });
    }

    // Also trigger on direct change (optional UX)
    if (reportTypeSelect) {
        reportTypeSelect.addEventListener('change', () => {
            syncFilterVisibility();
        });
    }
    if (yearSelect) {
        yearSelect.addEventListener('change', () => {
            if (!reportTypeSelect || reportTypeSelect.value === 'annual') {
                refresh();
            }
        });
    }
    if (storeSelect) {
        storeSelect.addEventListener('change', () => {
            if (!reportTypeSelect || reportTypeSelect.value === 'annual') {
                refresh();
            }
        });
    }

    syncFilterVisibility();
})();
</script>
@endpush
