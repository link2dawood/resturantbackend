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
            <a href="{{ route('daily-reports.create') }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Create Daily Report
            </a>
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
                                Export
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <!-- Store Filter -->
                    <form method="GET" action="{{ route('daily-reports.index') }}" class="mb-3">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">Filter by Store</label>
                                <select name="store_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Stores</option>
                                    @foreach($stores as $store)
                                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_info }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>

                    <!-- Summary Stats -->
                    @if($reports->count() > 0)
                        @php
                            $totalGross = $reports->sum('gross_sales');
                            $totalNet = $reports->sum('net_sales');
                            $avgGross = $reports->avg('gross_sales');
                            $totalCustomers = $reports->sum('total_customers');
                        @endphp
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <div class="d-inline-flex p-3 rounded-circle" style="background: #e6f4ea; color: #34a853;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="mb-1" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124;">${{ number_format($totalGross, 0) }}</h3>
                                        <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">Total Gross Sales</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <div class="d-inline-flex p-3 rounded-circle" style="background: #e8f0fe; color: #4285f4;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="mb-1" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124;">${{ number_format($totalNet, 0) }}</h3>
                                        <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">Total Net Sales</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <div class="d-inline-flex p-3 rounded-circle" style="background: #fff3e0; color: #f57c00;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 3v18h18"/>
                                                    <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="mb-1" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124;">${{ number_format($avgGross, 0) }}</h3>
                                        <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">Average Daily Sales</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="mb-2">
                                            <div class="d-inline-flex p-3 rounded-circle" style="background: #f3e5f5; color: #7b1fa2;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                                    <circle cx="9" cy="7" r="4"/>
                                                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                                    <path d="M16 3.13a4 4 0 010 7.75"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="mb-1" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124;">{{ number_format($totalCustomers) }}</h3>
                                        <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">Total Customers</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reports Table -->
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="font-size: 0.875rem;">
                                <thead style="background-color: #f8f9fa; border-bottom: 2px solid #e0e0e0;">
                                    <tr>
                                        <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Date</th>
                                        <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Store</th>
                                        <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Status</th>
                                        <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Gross Sales</th>
                                        <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Net Sales</th>
                                        <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif;">Created By</th>
                                        <th style="font-weight: 500; color: #3c4043; padding: 1rem; border: none; font-size: 0.813rem; letter-spacing: 0.3px; font-family: 'Google Sans', sans-serif; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reports as $report)
                                        <tr style="border-bottom: 1px solid var(--google-grey-100); transition: background-color 0.2s;"
                                            onmouseover="this.style.backgroundColor='var(--google-grey-50)'"
                                            onmouseout="this.style.backgroundColor='transparent'">
                                            <td style="padding: 16px 24px; color: var(--google-grey-900);">
                                                <div style="font-weight: 500;">{{ $report->report_date->format('M j, Y') }}</div>
                                                <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">{{ $report->report_date->format('l') }}</div>
                                            </td>
                                            <td style="padding: 16px 24px; color: var(--google-grey-900);">
                                                <div style="font-weight: 500;">{{ $report->store->store_info ?? 'N/A' }}</div>
                                                @if($report->page_number)
                                                    <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">Page #{{ $report->page_number }}</div>
                                                @endif
                                            </td>
                                            <td style="padding: 16px 24px;">
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
                                            </td>
                                            <td style="padding: 16px 24px; color: var(--google-grey-900);">
                                                <div style="font-weight: 500; font-size: 15px;">${{ number_format($report->gross_sales, 0) }}</div>
                                                @if($report->projected_sales)
                                                    <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">Projected: ${{ number_format($report->projected_sales, 0) }}</div>
                                                @endif
                                            </td>
                                            <td style="padding: 16px 24px; color: var(--google-grey-900);">
                                                <div style="font-weight: 500; font-size: 15px;">${{ number_format($report->net_sales, 0) }}</div>
                                                @if($report->total_customers)
                                                    <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">{{ $report->total_customers }} customers</div>
                                                @endif
                                            </td>
                                            <td style="padding: 16px 24px; color: var(--google-grey-900);">
                                                <div style="font-weight: 500;">{{ $report->creator->name ?? 'N/A' }}</div>
                                                <div style="font-size: 12px; color: var(--google-grey-600); margin-top: 2px;">{{ $report->created_at->format('M j, g:i A') }}</div>
                                            </td>
                                            <td style="padding: 16px 24px;">
                                                <div style="display: flex; gap: 8px; justify-content: center;">
                                                    <a href="{{ route('daily-reports.show', $report) }}"
                                                       style="padding: 8px; border-radius: 50%; background: var(--google-blue-50); color: var(--google-blue); transition: all 0.2s; text-decoration: none; display: flex; align-items: center; justify-content: center;"
                                                       title="View Report"
                                                       onmouseover="this.style.background='var(--google-blue-100)'"
                                                       onmouseout="this.style.background='var(--google-blue-50)'">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                            <circle cx="12" cy="12" r="3"/>
                                                        </svg>
                                                    </a>
                                                    @if(auth()->user()->hasPermission('manage_reports') && in_array($report->status, ['draft', 'rejected']))
                                                        <a href="{{ route('daily-reports.edit', $report) }}"
                                                           style="padding: 8px; border-radius: 50%; background: #fff3e0; color: #f57c00; transition: all 0.2s; text-decoration: none; display: flex; align-items: center; justify-content: center;"
                                                           title="Edit Report"
                                                           onmouseover="this.style.background='#ffe0b2'"
                                                           onmouseout="this.style.background='#fff3e0'">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                                            </svg>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('daily-reports.export-pdf', $report) }}"
                                                       style="padding: 8px; border-radius: 50%; background: var(--google-grey-100); color: var(--google-grey-700); transition: all 0.2s; text-decoration: none; display: flex; align-items: center; justify-content: center;"
                                                       title="Export PDF"
                                                       onmouseover="this.style.background='var(--google-grey-200)'"
                                                       onmouseout="this.style.background='var(--google-grey-100)'">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                            <polyline points="14,2 14,8 20,8"/>
                                                            <line x1="16" y1="13" x2="8" y2="13"/>
                                                            <line x1="16" y1="17" x2="8" y2="17"/>
                                                        </svg>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <x-pagination :paginator="$reports" />
                    @else
                        <div style="text-align: center; padding: 60px 32px;">
                            <div style="display: inline-flex; padding: 24px; background: var(--google-grey-50); border-radius: 50%; margin-bottom: 24px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--google-grey-400);">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                            </div>
                            <h2 style="font-size: 22px; font-weight: 400; color: var(--google-grey-900); margin: 0 0 8px 0;">No Reports Found</h2>
                            <p style="font-size: 16px; color: var(--google-grey-600); margin: 0;">
                                No daily reports found for {{ $months[$selectedMonth] }} {{ $selectedYear }}.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>

@endsection
