@extends('layouts.tabler')
@section('title', 'Daily Reports')


@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124;">Daily Reports</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Manage and view your restaurant's daily performance</p>
        </div>
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

    @if(session('warning'))
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid #fbbc04;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fbbc04" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            {{ session('warning') }}
        </div>
    @endif

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
    @endif

    <!-- Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap" style="gap: 1rem;">
        <div class="d-flex" style="gap: 0.75rem; flex-wrap: wrap;">
            @if(auth()->user()->hasPermission('create_reports'))
                <a href="{{ route('daily-reports.create') }}" class="btn btn-primary d-flex align-items-center" style="gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Create Daily Report
                </a>
            @endif
        </div>
        <div class="d-flex" style="gap: 0.75rem;">
            @if($reports->count() > 0)
                <a href="{{ route('daily-reports.export-csv') }}" class="btn btn-outline-secondary d-flex align-items-center" style="gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="7,10 12,15 17,10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Export
                </a>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header border-0">
            <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Search & Filter</h3>
        </div>
        <div class="card-body">
        <form method="GET" action="{{ route('daily-reports.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reports, stores, creators..." class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">Store</label>
                <select name="store_id" class="form-select">
                    <option value="">All Stores</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_info }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center" style="gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                    Search
                </button>
            </div>
        </form>
        </div>
    </div>

    @if($reports->count() > 0)
        <div class="card">
            <div class="card-body p-0">
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
                                        <div style="display: flex; gap: 8px;">
                                            <a href="{{ route('daily-reports.show', $report) }}"
                                               style="padding: 8px; border-radius: 50%; background: var(--google-blue-50); color: var(--google-blue); transition: all 0.2s; text-decoration: none; display: flex; align-items: center; justify-content: center;"
                                               title="View Report"
                                               onmouseover="this.style.background='var(--google-blue-100)'"
                                               onmouseout="this.style.background='var(--google-blue-50)'">
                                                <span class="material-symbols-outlined" style="font-size: 18px;">visibility</span>
                                            </a>
                                            @if(auth()->user()->hasPermission('manage_reports') && in_array($report->status, ['draft', 'rejected']))
                                                <a href="{{ route('daily-reports.edit', $report) }}"
                                                   style="padding: 8px; border-radius: 50%; background: #fff3e0; color: #f57c00; transition: all 0.2s; text-decoration: none; display: flex; align-items: center; justify-content: center;"
                                                   title="Edit Report"
                                                   onmouseover="this.style.background='#ffe0b2'"
                                                   onmouseout="this.style.background='#fff3e0'">
                                                    <span class="material-symbols-outlined" style="font-size: 18px;">edit</span>
                                                </a>
                                            @endif
                                            <a href="{{ route('daily-reports.export-pdf', $report) }}"
                                               style="padding: 8px; border-radius: 50%; background: var(--google-grey-100); color: var(--google-grey-700); transition: all 0.2s; text-decoration: none; display: flex; align-items: center; justify-content: center;"
                                               title="Export PDF"
                                               onmouseover="this.style.background='var(--google-grey-200)'"
                                               onmouseout="this.style.background='var(--google-grey-100)'">
                                                <span class="material-symbols-outlined" style="font-size: 18px;">picture_as_pdf</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center" style="font-size: 0.875rem; color: #5f6368; font-family: 'Google Sans', sans-serif;">
                <div>Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }} of {{ $reports->total() }} reports</div>
                <div>{{ $reports->links() }}</div>
            </div>
        </div>
    @else
        <div style="text-align: center; padding: 80px 32px;">
            <div style="display: inline-flex; padding: 24px; background: var(--google-grey-50); border-radius: 50%; margin-bottom: 24px;">
                <span class="material-symbols-outlined" style="font-size: 48px; color: var(--google-grey-400);">description</span>
            </div>
            <h2 style="font-size: 22px; font-weight: 400; color: var(--google-grey-900); margin: 0 0 8px 0;">No Reports Found</h2>
            <p style="font-size: 16px; color: var(--google-grey-600); margin: 0 0 32px 0; max-width: 400px; margin-left: auto; margin-right: auto;">
                @if(request()->filled('search') || request()->filled('date_from') || request()->filled('store_id'))
                    No reports match your search criteria. Try adjusting your filters or create a new report.
                @else
                    You haven't created any daily reports yet. Get started by creating your first report to track your restaurant's performance.
                @endif
            </p>
            @if(auth()->user()->hasPermission('create_reports'))
                <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                    <a href="{{ route('daily-reports.create') }}" class="google-btn google-btn-primary">
                        <span class="material-symbols-outlined">add</span>
                        Create Your First Report
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

@endsection