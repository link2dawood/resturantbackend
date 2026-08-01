@extends('layouts.tabler')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="container mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">
                Profit & Loss Statement
            </h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">
                {{ $trackingContext['header_subtitle'] ?? 'Comprehensive financial report' }}
            </p>
        </div>
        <div class="btn-group">
            @can('reports', 'export')
            <a href="{{ route('admin.reports.profit-loss.export.csv', ['store_id' => $storeId, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export CSV
            </a>
            <a href="{{ route('admin.reports.profit-loss.export.pdf', ['store_id' => $storeId, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Export PDF
            </a>
            @endcan
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect width="12" height="8" x="6" y="14" rx="1"/>
                </svg>
                Print
            </button>
            @can('reports', 'export')
            <a href="{{ route('admin.reports.profit-loss.comparison') }}" class="btn btn-outline-secondary">
                Store Comparison
            </a>
            <a href="{{ route('admin.reports.profit-loss.snapshots') }}" class="btn btn-outline-secondary">
                Snapshots
            </a>
            @endcan
        </div>
    </div>

    @if(!empty($snapshotMode) && isset($snapshot))
    <div class="alert alert-info mb-4">
        Viewing saved snapshot <strong>{{ $snapshot->name }}</strong> from
        {{ $snapshot->created_at->format(config('dates.display_datetime_24h')) }}.
    </div>
    @endif

    @if(!empty($trackingContext['summary']))
    <div class="alert alert-primary mb-4" role="status">
        <div class="fw-semibold mb-1">{{ $trackingContext['scope_heading'] ?? 'P&L Tracking' }}</div>
        <div>{{ $trackingContext['summary'] }}</div>
    </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="profitLossFilterForm" action="{{ route('admin.reports.profit-loss.index') }}" method="GET" class="row g-3">
                @canViewAllStores
                <div class="col-md-3">
                    <label class="form-label">{{ $trackingContext['filter_label'] ?? 'Store' }}</label>
                    <select class="form-select" name="store_id">
                        <option value="">{{ $trackingContext['all_option_label'] ?? 'All Stores' }}</option>
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
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date Preset</label>
                    <select class="form-select" id="datePreset" onchange="applyDatePreset(this.value)">
                        <option value="">Custom</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="this_year">This Year</option>
                        <option value="all_years">All Years</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Compare With</label>
                    <select class="form-select" name="comparison_period">
                        <option value="">None</option>
                        <option value="previous_period" {{ $comparisonPeriod == 'previous_period' ? 'selected' : '' }}>Previous Period</option>
                        <option value="previous_year" {{ $comparisonPeriod == 'previous_year' ? 'selected' : '' }}>Previous Year</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg> Generate Report
                    </button>
                    @can('reports', 'export')
                    @if(empty($snapshotMode))
                    <button type="button" class="btn btn-primary" onclick="saveSnapshot()">
                        Save Snapshot
                    </button>
                    @endif
                    @endcan
                </div>
            </form>
        </div>
    </div>

    @if(isset($data['pl']))
    @php
        $coaActivitySummary = $data['pl']['coa_activity_summary'] ?? [
            'year_months' => [],
            'income' => ['rows' => [], 'entry_count' => 0, 'total_amount' => 0, 'monthly_totals' => []],
            'cogs' => ['rows' => [], 'entry_count' => 0, 'total_amount' => 0, 'monthly_totals' => []],
            'expense' => ['rows' => [], 'entry_count' => 0, 'total_amount' => 0, 'monthly_totals' => []],
        ];
        $yearMonths = $coaActivitySummary['year_months'] ?? [];
    @endphp

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">COA Activity Summary</h3>
        </div>
        <div class="card-body">
            <div class="text-muted small mb-3">
                Monthly breakdown of all reportable P&amp;L COAs for the selected date range.
            </div>
            <div class="row g-4">
                {{-- Income Table --}}
                <div class="col-12">
                    <h4 class="h6 mb-2">Income by COA</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.8rem;">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th style="min-width:70px;">COA No.</th>
                                    <th style="min-width:160px;">COA Name</th>
                                    @foreach($yearMonths as $ym)
                                    <th class="text-end" style="min-width:90px;">{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M Y') }}</th>
                                    @endforeach
                                    <th class="text-end" style="min-width:100px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($coaActivitySummary['income']['rows'] ?? []) as $row)
                                @php $isMain = ($row['is_rollup'] ?? false) || empty($row['parent_account_id']); @endphp
                                <tr style="{{ $isMain ? 'background-color:#f0f4ff;font-weight:600;' : '' }}">
                                    <td>{{ $row['account_code'] }}</td>
                                    <td style="{{ $isMain ? '' : 'padding-left:1.25rem;' }}">{{ $row['account_name'] }}</td>
                                    @foreach($yearMonths as $ym)
                                    @php $amt = $row['monthly_amounts'][$ym] ?? 0; @endphp
                                    <td class="text-end {{ $amt != 0 ? 'text-success' : 'text-muted' }}">
                                        {{ $amt != 0 ? '$'.number_format($amt, 2) : '-' }}
                                    </td>
                                    @endforeach
                                    <td class="text-end text-success">${{ number_format($row['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ 2 + count($yearMonths) + 1 }}" class="text-center text-muted py-3">No income COA activity found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #e8f5e9; font-weight: 600;">
                                    <td colspan="2">Total Income</td>
                                    @foreach($yearMonths as $ym)
                                    @php $tot = $coaActivitySummary['income']['monthly_totals'][$ym] ?? 0; @endphp
                                    <td class="text-end text-success">${{ number_format($tot, 2) }}</td>
                                    @endforeach
                                    <td class="text-end text-success">${{ number_format($coaActivitySummary['income']['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                {{-- COGS Table --}}
                <div class="col-12">
                    <h4 class="h6 mb-2">COGS by COA</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.8rem;">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th style="min-width:70px;">COA No.</th>
                                    <th style="min-width:160px;">COA Name</th>
                                    @foreach($yearMonths as $ym)
                                    <th class="text-end" style="min-width:90px;">{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M Y') }}</th>
                                    @endforeach
                                    <th class="text-end" style="min-width:100px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($coaActivitySummary['cogs']['rows'] ?? []) as $row)
                                @php $isMain = ($row['is_rollup'] ?? false) || empty($row['parent_account_id']); @endphp
                                <tr style="{{ $isMain ? 'background-color:#fff3e0;font-weight:600;' : '' }}">
                                    <td>{{ $row['account_code'] }}</td>
                                    <td style="{{ $isMain ? '' : 'padding-left:1.25rem;' }}">{{ $row['account_name'] }}</td>
                                    @foreach($yearMonths as $ym)
                                    @php $amt = $row['monthly_amounts'][$ym] ?? 0; @endphp
                                    <td class="text-end {{ $amt != 0 ? 'text-warning' : 'text-muted' }}">
                                        {{ $amt != 0 ? '$'.number_format($amt, 2) : '-' }}
                                    </td>
                                    @endforeach
                                    <td class="text-end text-warning">${{ number_format($row['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ 2 + count($yearMonths) + 1 }}" class="text-center text-muted py-3">No COGS activity found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #ffe0b2; font-weight: 600;">
                                    <td colspan="2">Total COGS</td>
                                    @foreach($yearMonths as $ym)
                                    @php $tot = $coaActivitySummary['cogs']['monthly_totals'][$ym] ?? 0; @endphp
                                    <td class="text-end text-warning">${{ number_format($tot, 2) }}</td>
                                    @endforeach
                                    <td class="text-end text-warning">${{ number_format($coaActivitySummary['cogs']['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                {{-- Expense Table --}}
                <div class="col-12">
                    <h4 class="h6 mb-2">Expense by COA</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.8rem;">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th style="min-width:70px;">COA No.</th>
                                    <th style="min-width:160px;">COA Name</th>
                                    @foreach($yearMonths as $ym)
                                    <th class="text-end" style="min-width:90px;">{{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M Y') }}</th>
                                    @endforeach
                                    <th class="text-end" style="min-width:100px;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($coaActivitySummary['expense']['rows'] ?? []) as $row)
                                @php $isMain = ($row['is_rollup'] ?? false) || empty($row['parent_account_id']); @endphp
                                <tr style="{{ $isMain ? 'background-color:#fff8f0;font-weight:600;' : '' }}">
                                    <td>{{ $row['account_code'] }}</td>
                                    <td style="{{ $isMain ? '' : 'padding-left:1.25rem;' }}">{{ $row['account_name'] }}</td>
                                    @foreach($yearMonths as $ym)
                                    @php $amt = $row['monthly_amounts'][$ym] ?? 0; @endphp
                                    <td class="text-end {{ $amt != 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $amt != 0 ? '$'.number_format($amt, 2) : '-' }}
                                    </td>
                                    @endforeach
                                    <td class="text-end text-danger">${{ number_format($row['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ 2 + count($yearMonths) + 1 }}" class="text-center text-muted py-3">No expense COA activity found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #fce4ec; font-weight: 600;">
                                    <td colspan="2">Total Expenses</td>
                                    @foreach($yearMonths as $ym)
                                    @php $tot = $coaActivitySummary['expense']['monthly_totals'][$ym] ?? 0; @endphp
                                    <td class="text-end text-danger">${{ number_format($tot, 2) }}</td>
                                    @endforeach
                                    <td class="text-end text-danger">${{ number_format($coaActivitySummary['expense']['total_amount'] ?? 0, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- P&L Report -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size: 0.875rem;">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                        <tr>
                            <th style="width: 40%; font-weight: 600;">Line Item</th>
                            @if($comparisonPeriod)
                            <th class="text-end" style="font-weight: 600;">Current Period</th>
                            <th class="text-end" style="font-weight: 600;">Comparison</th>
                            <th class="text-end" style="font-weight: 600;">Variance</th>
                            <th class="text-end" style="font-weight: 600;">Variance %</th>
                            @else
                            <th class="text-end" style="font-weight: 600;">Amount</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <!-- REVENUE SECTION -->
                        <tr style="background-color: #f8f9fa;">
                            <td colspan="{{ $comparisonPeriod ? 5 : 2 }}" style="font-weight: 600; font-size: 1rem;">REVENUE</td>
                        </tr>
                        @foreach($data['pl']['revenue']['items'] as $item)
                        <tr>
                            <td style="padding-left: 2rem;">{{ $item['name'] }}</td>
                            @if($comparisonPeriod)
                            <td class="text-end">${{ number_format($item['amount'] ?? 0, 2) }}</td>
                            <td class="text-end">${{ number_format($item['comparison_amount'] ?? 0, 2) }}</td>
                            <td class="text-end {{ ($item['variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($item['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($item['variance_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($item['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end">${{ number_format($item['amount'] ?? 0, 2) }}</td>
                            @endif
                        </tr>
                        @endforeach
                        <tr style="background-color: #e8f5e9; font-weight: 600;">
                            <td>TOTAL REVENUE</td>
                            @if($comparisonPeriod)
                            <td class="text-end">${{ number_format($data['pl']['revenue']['total'], 2) }}</td>
                            <td class="text-end">${{ number_format($data['pl']['revenue']['comparison_total'] ?? 0, 2) }}</td>
                            <td class="text-end {{ ($data['pl']['revenue']['variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['revenue']['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['revenue']['variance_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['revenue']['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end">${{ number_format($data['pl']['revenue']['total'], 2) }}</td>
                            @endif
                        </tr>

                        <!-- COGS SECTION -->
                        <tr style="background-color: #fff3e0;">
                            <td colspan="{{ $comparisonPeriod ? 5 : 2 }}" style="font-weight: 600; font-size: 1rem;">COST OF GOODS SOLD (COGS)</td>
                        </tr>
                        @foreach($data['pl']['cogs']['items'] as $item)
                        <tr>
                            <td style="padding-left: 2rem;">{{ $item['name'] }}</td>
                            @if($comparisonPeriod)
                            <td class="text-end text-danger">(${{ number_format($item['amount'] ?? 0, 2) }})</td>
                            <td class="text-end text-danger">(${{ number_format($item['comparison_amount'] ?? 0, 2) }})</td>
                            <td class="text-end {{ ($item['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($item['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($item['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($item['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-danger">(${{ number_format($item['amount'] ?? 0, 2) }})</td>
                            @endif
                        </tr>
                        @endforeach
                        <tr style="background-color: #ffe0b2; font-weight: 600;">
                            <td>TOTAL COGS</td>
                            @if($comparisonPeriod)
                            <td class="text-end text-danger">(${{ number_format($data['pl']['cogs']['total'], 2) }})</td>
                            <td class="text-end text-danger">(${{ number_format($data['pl']['cogs']['comparison_total'] ?? 0, 2) }})</td>
                            <td class="text-end {{ ($data['pl']['cogs']['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['cogs']['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['cogs']['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['cogs']['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-danger">(${{ number_format($data['pl']['cogs']['total'], 2) }})</td>
                            @endif
                        </tr>

                        <!-- GROSS PROFIT -->
                        <tr style="background-color: #e8f5e9; font-weight: 600; font-size: 1.05rem;">
                            <td>GROSS PROFIT</td>
                            @if($comparisonPeriod)
                            <td class="text-end text-success">${{ number_format($data['pl']['gross_profit'], 2) }}</td>
                            <td class="text-end text-success">${{ number_format($data['pl']['comparison_gross_profit'] ?? 0, 2) }}</td>
                            <td class="text-end {{ ($data['pl']['gross_profit_variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['gross_profit_variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['gross_profit_variance_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['gross_profit_variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-success">${{ number_format($data['pl']['gross_profit'], 2) }}</td>
                            @endif
                        </tr>
                        <tr>
                            <td style="padding-left: 2rem;">Gross Margin</td>
                            @if($comparisonPeriod)
                            <td class="text-end">{{ number_format($data['pl']['gross_margin'], 2) }}%</td>
                            <td class="text-end">{{ number_format($data['pl']['comparison_gross_margin'] ?? 0, 2) }}%</td>
                            <td colspan="2"></td>
                            @else
                            <td class="text-end">{{ number_format($data['pl']['gross_margin'], 2) }}%</td>
                            @endif
                        </tr>

                        <!-- OPERATING EXPENSES -->
                        <tr style="background-color: #fce4ec;">
                            <td colspan="{{ $comparisonPeriod ? 5 : 2 }}" style="font-weight: 600; font-size: 1rem;">OPERATING EXPENSES</td>
                        </tr>
                        @foreach($data['pl']['operating_expenses']['items'] as $item)
                            @if(isset($item['items']))
                                {{-- Parent category: render children first, then subtotal row --}}
                                @foreach($item['items'] as $subItem)
                                <tr>
                                    <td style="padding-left: 2rem;">
                                        {{ $subItem['name'] }}
                                        @if($subItem['coa_id'])
                                        <a href="{{ route('admin.reports.profit-loss.drill-down', ['coa_id' => $subItem['coa_id'], 'start_date' => $startDate, 'end_date' => $endDate, 'store_id' => $storeId]) }}"
                                           class="text-decoration-none" title="View transactions">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                        @endif
                                    </td>
                                    @if($comparisonPeriod)
                                    <td class="text-end text-danger">(${{ number_format($subItem['amount'] ?? 0, 2) }})</td>
                                    <td class="text-end text-danger">(${{ number_format($subItem['comparison_amount'] ?? 0, 2) }})</td>
                                    <td class="text-end {{ ($subItem['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format($subItem['variance'] ?? 0, 2) }}
                                    </td>
                                    <td class="text-end {{ ($subItem['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($subItem['variance_percent'] ?? 0, 2) }}%
                                    </td>
                                    @else
                                    <td class="text-end text-danger">(${{ number_format($subItem['amount'] ?? 0, 2) }})</td>
                                    @endif
                                </tr>
                                @endforeach
                                <tr style="background-color: #fff8f9;">
                                    <td style="padding-left: 1rem; font-weight: 600;">{{ $item['name'] }} Total</td>
                                    @if($comparisonPeriod)
                                    <td class="text-end text-danger fw-semibold">(${{ number_format($item['total'] ?? 0, 2) }})</td>
                                    <td class="text-end text-danger fw-semibold">(${{ number_format($item['comparison_amount'] ?? 0, 2) }})</td>
                                    <td class="text-end fw-semibold {{ ($item['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format($item['variance'] ?? 0, 2) }}
                                    </td>
                                    <td class="text-end fw-semibold {{ ($item['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($item['variance_percent'] ?? 0, 2) }}%
                                    </td>
                                    @else
                                    <td class="text-end text-danger fw-semibold">(${{ number_format($item['total'] ?? 0, 2) }})</td>
                                    @endif
                                </tr>
                            @else
                                {{-- Standalone top-level expense (no sub-items) — treated as a main category --}}
                                <tr style="background-color: #fff8f9;">
                                    <td style="padding-left: 1rem; font-weight: 600;">
                                        {{ $item['name'] }}
                                        @if($item['coa_id'])
                                        <a href="{{ route('admin.reports.profit-loss.drill-down', ['coa_id' => $item['coa_id'], 'start_date' => $startDate, 'end_date' => $endDate, 'store_id' => $storeId]) }}"
                                           class="text-decoration-none" title="View transactions">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                        @endif
                                    </td>
                                    @if($comparisonPeriod)
                                    <td class="text-end text-danger fw-semibold">(${{ number_format($item['amount'] ?? 0, 2) }})</td>
                                    <td class="text-end text-danger fw-semibold">(${{ number_format($item['comparison_amount'] ?? 0, 2) }})</td>
                                    <td class="text-end fw-semibold {{ ($item['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format($item['variance'] ?? 0, 2) }}
                                    </td>
                                    <td class="text-end fw-semibold {{ ($item['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($item['variance_percent'] ?? 0, 2) }}%
                                    </td>
                                    @else
                                    <td class="text-end text-danger fw-semibold">(${{ number_format($item['amount'] ?? 0, 2) }})</td>
                                    @endif
                                </tr>
                            @endif
                        @endforeach
                        <tr style="background-color: #f8bbd0; font-weight: 600;">
                            <td>TOTAL OPERATING EXPENSES</td>
                            @if($comparisonPeriod)
                            <td class="text-end text-danger">(${{ number_format($data['pl']['operating_expenses']['total'], 2) }})</td>
                            <td class="text-end text-danger">(${{ number_format($data['pl']['operating_expenses']['comparison_total'] ?? 0, 2) }})</td>
                            <td class="text-end {{ ($data['pl']['operating_expenses']['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['operating_expenses']['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['operating_expenses']['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['operating_expenses']['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-danger">(${{ number_format($data['pl']['operating_expenses']['total'], 2) }})</td>
                            @endif
                        </tr>

                        <!-- NET PROFIT -->
                        <tr style="background-color: #c8e6c9; font-weight: 700; font-size: 1.1rem;">
                            <td>NET PROFIT</td>
                            @if($comparisonPeriod)
                            <td class="text-end {{ $data['pl']['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['net_profit'], 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['comparison_net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['comparison_net_profit'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['net_profit_variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['net_profit_variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['net_profit_variance_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['net_profit_variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end {{ $data['pl']['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['net_profit'], 2) }}
                            </td>
                            @endif
                        </tr>
                        <tr>
                            <td style="padding-left: 2rem;">Net Margin</td>
                            @if($comparisonPeriod)
                            <td class="text-end">{{ number_format($data['pl']['net_margin'], 2) }}%</td>
                            <td class="text-end">{{ number_format($data['pl']['comparison_net_margin'] ?? 0, 2) }}%</td>
                            <td colspan="2"></td>
                            @else
                            <td class="text-end">{{ number_format($data['pl']['net_margin'], 2) }}%</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body text-center py-5">
            <p class="text-muted">Select date range and click "Generate Report" to view P&L statement.</p>
        </div>
    </div>
    @endif
</div>

@can('reports', 'export')
@if(empty($snapshotMode))
<!-- Save Snapshot Modal -->
<div class="modal fade" id="snapshotModal" tabindex="-1" aria-labelledby="snapshotModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="snapshotModalLabel">Save P&L Snapshot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="snapshotForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="snapshotName" class="form-label">Snapshot Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="snapshotName" name="name" required>
                        <small class="text-muted">e.g., "Q1 2024", "January 2024"</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan

@endsection

@push('scripts')
<script>
function toLocalYmd(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + day;
}

// The date range for a preset, as {start, end} in Y-m-d. Null for "Custom".
function computePresetRange(preset) {
    const today = new Date();
    let start, end;
    switch(preset) {
        case 'this_month':
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'this_quarter': {
            // Calendar quarters: Jan–Mar, Apr–Jun, Jul–Sep, Oct–Dec.
            const quarter = Math.floor(today.getMonth() / 3); // 0..3
            start = new Date(today.getFullYear(), quarter * 3, 1);
            end = new Date(today.getFullYear(), quarter * 3 + 3, 0);
            break;
        }
        case 'this_year':
            start = new Date(today.getFullYear(), 0, 1);
            end = new Date(today.getFullYear(), 11, 31);
            break;
        case 'all_years':
            start = new Date('{{ $allYearsStartDate }}T00:00:00');
            end = new Date('{{ $allYearsEndDate }}T00:00:00');
            break;
        default:
            return null;
    }
    return { start: toLocalYmd(start), end: toLocalYmd(end) };
}

function applyDatePreset(preset) {
    const range = computePresetRange(preset);
    if (!range) return;

    const form = document.getElementById('profitLossFilterForm');
    if (!form) return;
    const startHidden = form.querySelector('input[name="start_date"]');
    const endHidden = form.querySelector('input[name="end_date"]');
    if (!startHidden || !endHidden) return;
    startHidden.value = range.start;
    endHidden.value = range.end;
    if (window.refreshUsDateVisible) {
        window.refreshUsDateVisible(startHidden);
        window.refreshUsDateVisible(endHidden);
    }
    // Apply immediately so picking a preset loads that period (e.g. This Quarter).
    form.submit();
}

// On load, reflect the current date range in the preset dropdown — so a
// Jul 1 – Sep 30 range shows "This Quarter" rather than "Custom".
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('profitLossFilterForm');
    const select = document.getElementById('datePreset');
    if (!form || !select) return;
    const startInput = form.querySelector('input[name="start_date"]');
    const endInput = form.querySelector('input[name="end_date"]');
    if (!startInput || !endInput || !startInput.value || !endInput.value) return;

    let matched = '';
    ['this_month', 'last_month', 'this_quarter', 'this_year', 'all_years'].forEach(function (p) {
        const r = computePresetRange(p);
        if (r && r.start === startInput.value && r.end === endInput.value) matched = p;
    });
    select.value = matched; // '' = Custom
});
</script>
@endpush

@can('reports', 'export')
@if(empty($snapshotMode))
@push('scripts')
<script>
function saveSnapshot() {
    @if(isset($data['pl']))
    new bootstrap.Modal(document.getElementById('snapshotModal')).show();
    @else
    alert('Please generate a report first before saving a snapshot.');
    @endif
}

document.getElementById('snapshotForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const plForm = document.getElementById('profitLossFilterForm');
    const formData = {
        name: document.getElementById('snapshotName').value,
        store_id: plForm ? (plForm.querySelector('select[name="store_id"]')?.value || null) : null,
        start_date: plForm?.querySelector('input[name="start_date"]')?.value,
        end_date: plForm?.querySelector('input[name="end_date"]')?.value,
    };
    
    fetch('/api/reports/pl/snapshot', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin',
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.message) {
            alert('Snapshot saved successfully!');
            bootstrap.Modal.getInstance(document.getElementById('snapshotModal')).hide();
        } else if (result.error) {
            alert('Error: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving snapshot');
    });
});
</script>
@endpush
@endif
@endcan
