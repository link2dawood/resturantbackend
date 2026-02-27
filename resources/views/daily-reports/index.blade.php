@extends('layouts.tabler')
@section('title', 'Daily Reports')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124;">Daily Reports</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Select a year and month to view daily reports</p>
        </div>
        @if(auth()->user()->hasPermission('create_reports'))
            <x-button-add href="{{ route('daily-reports.create') }}" text="Create Daily Report" />
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid #34a853;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#34a853" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22,4 12,14.01 9,11.01"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid #ea4335;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ea4335" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Year Selection -->
    @if(!$selectedYear)
        <div class="card mb-4">
            <div class="card-header border-0">
                <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Select a Year</h3>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach($years as $year)
                        <a href="{{ route('daily-reports.index', ['year' => $year]) }}" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                           style="padding: 1rem 1.5rem; border: none; border-bottom: 1px solid #e0e0e0; transition: all 0.2s; font-family: 'Google Sans', sans-serif; font-size: 0.875rem;"
                           onmouseover="this.style.background='#f8f9fa'; this.style.paddingLeft='2rem'"
                           onmouseout="this.style.background='white'; this.style.paddingLeft='1.5rem'">
                            <span style="font-weight: 500; color: #202124;">{{ $year }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #5f6368;">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <!-- Month Selection -->
        @if(!$selectedMonth)
            <div class="card mb-4">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Select a Month - {{ $selectedYear }}</h3>
                    <a href="{{ route('daily-reports.index') }}" class="btn btn-sm btn-outline-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <path d="M19 12H5M12 19l-7-7 7-7"/>
                        </svg>
                        Back to Years
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @php
                            $shortMonths = [
                                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                            ];
                        @endphp
                        @foreach($months as $monthNum => $monthName)
                            <a href="{{ route('daily-reports.index', ['year' => $selectedYear, 'month' => $monthNum]) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" 
                               style="padding: 1rem 1.5rem; border: none; border-bottom: 1px solid #e0e0e0; transition: all 0.2s; font-family: 'Google Sans', sans-serif; font-size: 0.875rem;"
                               onmouseover="this.style.background='#f8f9fa'; this.style.paddingLeft='2rem'"
                               onmouseout="this.style.background='white'; this.style.paddingLeft='1.5rem'">
                                <span style="font-weight: 500; color: #202124;">{{ $shortMonths[$monthNum] }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #5f6368;">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <!-- Reports Display -->
            <div class="card mb-4">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">
                        Daily Reports - {{ $months[$selectedMonth] }} {{ $selectedYear }}
                    </h3>
                    <div class="d-flex gap-2">
                        <a href="{{ route('daily-reports.index', ['year' => $selectedYear]) }}" class="btn btn-sm btn-outline-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                <path d="M19 12H5M12 19l-7-7 7-7"/>
                            </svg>
                            Back to Months
                        </a>
                        @if($reports->count() > 0)
                            <a href="{{ route('daily-reports.export-csv', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="btn btn-sm btn-outline-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                    <polyline points="7,10 12,15 17,10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Export CSV
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Date Range Filter and Export PDF -->
                    <form method="GET" action="{{ route('daily-reports.index') }}" id="dateFilterForm" class="mb-3">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ request('start_date', $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . '-01') }}" onchange="updateEndDateMin(); this.form.submit();">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">End Date</label>
                                @php
                                    $lastDay = date('t', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear));
                                    $defaultEndDate = $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . '-' . str_pad($lastDay, 2, '0', STR_PAD_LEFT);
                                @endphp
                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date', $defaultEndDate) }}" min="{{ $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . '-01' }}" onchange="this.form.submit();">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">Filter by Store</label>
                                <select name="store_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Stores</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_info }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                @php
                                    $startDateParam = request('start_date', $selectedYear . '-' . str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) . '-01');
                                    $endDateParam = request('end_date', $defaultEndDate);
                                    $storeIdParam = request('store_id');
                                @endphp
                                <a href="{{ route('daily-reports.export-pdf-range', ['start_date' => $startDateParam, 'end_date' => $endDateParam, 'store_id' => $storeIdParam]) }}" class="btn btn-success w-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14,2 14,8 20,8"/>
                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                    Export PDF
                                </a>
                            </div>
                        </div>
                    </form>

                    @if($reports->count() > 0)
                        @php
                            $headers = [
                                'Date',
                                'Store',
                                'Status',
                                'Gross Sales',
                                'Net Sales',
                                'Created By',
                                ['label' => 'Actions', 'align' => 'center']
                            ];
                        @endphp

                        <x-table 
                            :headers="$headers"
                            emptyMessage="No Reports Found"
                            emptyDescription="No daily reports found for {{ $months[$selectedMonth] }} {{ $selectedYear }}.">
                            @if($reports->count() > 0)
                                @foreach($reports as $report)
                                    <x-table-row>
                                        <x-table-cell>
                                            <div style="font-weight: 500;">{{ $report->report_date->format('M j, Y') }}</div>
                                            <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">{{ $report->report_date->format('l') }}</div>
                                        </x-table-cell>
                                        <x-table-cell>
                                            <div style="font-weight: 500;">{{ $report->store->store_info ?? 'N/A' }}</div>
                                            @if($report->page_number)
                                                <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">Page #{{ $report->page_number }}</div>
                                            @endif
                                        </x-table-cell>
                                        <x-table-cell>
                                            @php
                                                $statusStyles = [
                                                    'draft' => 'background: var(--google-grey-100); color: var(--google-grey-700);',
                                                    'submitted' => 'background: #fef7e0; color: #f57c00;',
                                                    'approved' => 'background: var(--google-green-50); color: var(--google-green);',
                                                    'rejected' => 'background: var(--google-red-50); color: var(--google-red);'
                                                ];
                                                $statusStyle = $statusStyles[$report->status] ?? 'background: var(--google-grey-100); color: var(--google-grey-700);';
                                            @endphp
                                            <span style="padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; {{ $statusStyle }}">
                                                {{ ucfirst($report->status) }}
                                            </span>
                                        </x-table-cell>
                                        <x-table-cell>
                                            <div style="font-weight: 500; font-size: 15px;">${{ number_format($report->gross_sales, 0) }}</div>
                                            @if($report->projected_sales)
                                                <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">Projected: ${{ number_format($report->projected_sales, 0) }}</div>
                                            @endif
                                        </x-table-cell>
                                        <x-table-cell>
                                            <div style="font-weight: 500; font-size: 15px;">${{ number_format($report->net_sales, 0) }}</div>
                                            @if($report->total_customers)
                                                <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">{{ $report->total_customers }} customers</div>
                                            @endif
                                        </x-table-cell>
                                        <x-table-cell>
                                            <div style="font-weight: 500;">{{ $report->creator->name ?? 'N/A' }}</div>
                                            <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">{{ $report->created_at->format('M j, g:i A') }}</div>
                                        </x-table-cell>
                                        <x-table-cell align="center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <x-button-view href="{{ route('daily-reports.show', $report) }}" iconOnly="true" />
                                                @if(auth()->user()->hasPermission('manage_reports') && in_array($report->status, ['draft', 'rejected']))
                                                    <x-button-edit href="{{ route('daily-reports.edit', $report) }}" iconOnly="true" />
                                                @endif
                                                <a href="{{ route('daily-reports.export-pdf', $report) }}"
                                                   class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center"
                                                   style="width: 32px; height: 32px;"
                                                   title="Export PDF">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                        <polyline points="14,2 14,8 20,8"/>
                                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        </x-table-cell>
                                    </x-table-row>
                                @endforeach
                            @endif
                        </x-table>

                        @if($reports->hasPages())
                            <div class="mt-3">
                                <x-pagination :paginator="$reports" />
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>

<script>
function updateEndDateMin() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDateInput = document.getElementById('end_date');
    if (startDate && endDateInput) {
        endDateInput.min = startDate;
        if (endDateInput.value < startDate) {
            endDateInput.value = startDate;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateEndDateMin();
});
</script>

@endsection
