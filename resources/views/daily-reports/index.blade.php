@extends('layouts.tabler')
@section('title', 'Daily Reports')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

<style>
:root {
  --google-blue: #4285f4;
  --google-blue-50: #e8f0fe;
  --google-blue-100: #d2e3fc;
  --google-blue-600: #1a73e8;
  --google-blue-700: #1967d2;
  --google-green: #34a853;
  --google-green-50: #e6f4ea;
  --google-yellow: #fbbc04;
  --google-red: #ea4335;
  --google-red-50: #fce8e6;
  --google-grey-50: #f8f9fa;
  --google-grey-100: #f1f3f4;
  --google-grey-200: #e8eaed;
  --google-grey-300: #dadce0;
  --google-grey-600: #5f6368;
  --google-grey-700: #3c4043;
  --google-grey-900: #202124;
  --surface: #ffffff;
  --on-surface: #1f1f1f;
  --surface-variant: #f8f9fa;
}

* {
  font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
}

.google-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

.google-header {
  margin-bottom: 32px;
}

.google-title {
  font-size: 28px;
  font-weight: 400;
  color: var(--google-grey-900);
  margin: 0 0 8px 0;
  letter-spacing: -0.25px;
}

.google-subtitle {
  font-size: 16px;
  font-weight: 400;
  color: var(--google-grey-600);
  margin: 0;
}

.google-card {
  background: var(--surface);
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(60, 64, 67, 0.08), 0 4px 8px 3px rgba(60, 64, 67, 0.04);
  border: none;
  transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
}

.google-card:hover {
  box-shadow: 0 2px 6px 2px rgba(60, 64, 67, 0.15), 0 8px 24px 4px rgba(60, 64, 67, 0.12);
}

.google-btn {
  font-family: 'Google Sans', sans-serif;
  font-weight: 500;
  font-size: 14px;
  line-height: 20px;
  letter-spacing: 0.25px;
  border-radius: 20px;
  padding: 10px 24px;
  border: none;
  transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.google-btn-primary {
  background: var(--google-blue);
  color: white;
}

.google-btn-primary:hover {
  background: var(--google-blue-700);
  box-shadow: 0 1px 3px 0 rgba(66, 133, 244, 0.3), 0 4px 8px 3px rgba(66, 133, 244, 0.15);
  color: white;
}

.google-btn-outlined {
  background: transparent;
  color: var(--google-blue);
  border: 1px solid var(--google-grey-300);
}

.google-btn-outlined:hover {
  background: var(--google-blue-50);
  border-color: var(--google-blue);
  color: var(--google-blue);
}

.google-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
  margin-bottom: 32px;
}

.google-stat-card {
  background: var(--surface);
  border-radius: 12px;
  padding: 20px;
  border: 1px solid var(--google-grey-200);
  transition: all 0.2s cubic-bezier(0.2, 0, 0, 1);
}

.google-stat-value {
  font-size: 32px;
  font-weight: 400;
  color: var(--google-grey-900);
  margin: 0 0 4px 0;
}

.google-stat-label {
  font-size: 14px;
  font-weight: 500;
  color: var(--google-grey-600);
  margin: 0;
}

.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20;
  font-size: 20px;
}
</style>
@endsection

@section('content')

<div class="google-container">
    <div class="google-header">
        <h1 class="google-title">Daily Reports</h1>
        <p class="google-subtitle">Manage and view your restaurant's daily performance</p>
    </div>

    @if(session('success'))
        <div class="google-card" style="background: var(--google-green-50); border-left: 4px solid var(--google-green); margin-bottom: 24px; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-outlined" style="color: var(--google-green);">check_circle</span>
                <span style="color: var(--google-green); font-weight: 500;">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="google-card" style="background: var(--google-red-50); border-left: 4px solid var(--google-red); margin-bottom: 24px; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-outlined" style="color: var(--google-red);">error</span>
                <span style="color: var(--google-red); font-weight: 500;">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="google-card" style="background: #fef7e0; border-left: 4px solid var(--google-yellow); margin-bottom: 24px; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="material-symbols-outlined" style="color: var(--google-yellow);">warning</span>
                <span style="color: #f57c00; font-weight: 500;">{{ session('warning') }}</span>
            </div>
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
        <div class="google-stats-grid">
            <div class="google-stat-card">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="background: var(--google-green-50); padding: 12px; border-radius: 12px;">
                        <span class="material-symbols-outlined" style="color: var(--google-green); font-size: 24px;">trending_up</span>
                    </div>
                    <div>
                        <p class="google-stat-value">${{ number_format($totalGross, 0) }}</p>
                        <p class="google-stat-label">Total Gross Sales</p>
                    </div>
                </div>
            </div>
            <div class="google-stat-card">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="background: var(--google-blue-50); padding: 12px; border-radius: 12px;">
                        <span class="material-symbols-outlined" style="color: var(--google-blue); font-size: 24px;">account_balance_wallet</span>
                    </div>
                    <div>
                        <p class="google-stat-value">${{ number_format($totalNet, 0) }}</p>
                        <p class="google-stat-label">Total Net Sales</p>
                    </div>
                </div>
            </div>
            <div class="google-stat-card">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="background: #fff3e0; padding: 12px; border-radius: 12px;">
                        <span class="material-symbols-outlined" style="color: #f57c00; font-size: 24px;">analytics</span>
                    </div>
                    <div>
                        <p class="google-stat-value">${{ number_format($avgGross, 0) }}</p>
                        <p class="google-stat-label">Average Daily Sales</p>
                    </div>
                </div>
            </div>
            <div class="google-stat-card">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="background: #f3e5f5; padding: 12px; border-radius: 12px;">
                        <span class="material-symbols-outlined" style="color: #7b1fa2; font-size: 24px;">group</span>
                    </div>
                    <div>
                        <p class="google-stat-value">{{ number_format($totalCustomers) }}</p>
                        <p class="google-stat-label">Total Customers</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            @if(auth()->user()->hasPermission('create_reports'))
                <a href="{{ route('daily-reports.create') }}" class="google-btn google-btn-primary">
                    <span class="material-symbols-outlined">add</span>
                    Create Daily Report
                </a>
                <a href="{{ route('daily-reports.quick-entry') }}" class="google-btn google-btn-outlined">
                    <span class="material-symbols-outlined">flash_on</span>
                    Quick Entry
                </a>
            @endif
        </div>
        <div style="display: flex; gap: 12px;">
            @if($reports->count() > 0)
                <a href="{{ route('daily-reports.export-csv') }}" class="google-btn google-btn-outlined">
                    <span class="material-symbols-outlined">download</span>
                    Export
                </a>
            @endif
        </div>
    </div>

    <div class="google-card" style="margin-bottom: 24px; padding: 24px;">
        <h3 style="font-size: 16px; font-weight: 500; color: var(--google-grey-900); margin: 0 0 20px 0;">Search & Filter</h3>
        <form method="GET" action="{{ route('daily-reports.index') }}" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1.5fr auto; gap: 16px; align-items: end;">
            <div>
                <label style="font-size: 14px; font-weight: 500; color: var(--google-grey-700); margin-bottom: 6px; display: block;">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reports, stores, creators..."
                       style="width: 100%; padding: 12px 16px; border: 1px solid var(--google-grey-300); border-radius: 8px; font-size: 14px; font-family: 'Google Sans', sans-serif; transition: border-color 0.2s;">
            </div>
            <div>
                <label style="font-size: 14px; font-weight: 500; color: var(--google-grey-700); margin-bottom: 6px; display: block;">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       style="width: 100%; padding: 12px 16px; border: 1px solid var(--google-grey-300); border-radius: 8px; font-size: 14px; font-family: 'Google Sans', sans-serif;">
            </div>
            <div>
                <label style="font-size: 14px; font-weight: 500; color: var(--google-grey-700); margin-bottom: 6px; display: block;">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       style="width: 100%; padding: 12px 16px; border: 1px solid var(--google-grey-300); border-radius: 8px; font-size: 14px; font-family: 'Google Sans', sans-serif;">
            </div>
            <div>
                <label style="font-size: 14px; font-weight: 500; color: var(--google-grey-700); margin-bottom: 6px; display: block;">Store</label>
                <select name="store_id" style="width: 100%; padding: 12px 16px; border: 1px solid var(--google-grey-300); border-radius: 8px; font-size: 14px; font-family: 'Google Sans', sans-serif; background: white;">
                    <option value="">All Stores</option>
                    @foreach($stores as $store)
                        <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_info }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="google-btn google-btn-primary" style="height: 44px;">
                <span class="material-symbols-outlined">search</span>
                Search
            </button>
        </form>
    </div>

    @if($reports->count() > 0)
        <div class="google-card">
            <div style="padding: 0; overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: var(--google-grey-50); border-bottom: 1px solid var(--google-grey-200);">
                                <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: var(--google-grey-700); font-size: 13px; letter-spacing: 0.3px;">Date</th>
                                <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: var(--google-grey-700); font-size: 13px; letter-spacing: 0.3px;">Store</th>
                                <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: var(--google-grey-700); font-size: 13px; letter-spacing: 0.3px;">Status</th>
                                <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: var(--google-grey-700); font-size: 13px; letter-spacing: 0.3px;">Gross Sales</th>
                                <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: var(--google-grey-700); font-size: 13px; letter-spacing: 0.3px;">Net Sales</th>
                                <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: var(--google-grey-700); font-size: 13px; letter-spacing: 0.3px;">Created By</th>
                                <th style="padding: 16px 24px; text-align: left; font-weight: 500; color: var(--google-grey-700); font-size: 13px; letter-spacing: 0.3px;">Actions</th>
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
            <div style="padding: 16px 24px; border-top: 1px solid var(--google-grey-200); background: var(--google-grey-50); display: flex; justify-content: space-between; align-items: center; font-size: 14px; color: var(--google-grey-600);">
                <div>Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }} of {{ $reports->total() }} reports</div>
                <div style="color: var(--google-blue);">{{ $reports->links() }}</div>
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
                    <a href="{{ route('daily-reports.quick-entry') }}" class="google-btn google-btn-outlined">
                        <span class="material-symbols-outlined">flash_on</span>
                        Quick Entry
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

@endsection