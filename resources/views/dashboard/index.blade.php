@extends('layouts.tabler')
@section('title', 'Analytics Dashboard')

@section('page-header')
@section('page-title', 'Analytics Dashboard')
@section('page-subtitle', 'Real-time insights and performance metrics for your daily reports')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124;">Analytics Dashboard</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Real-time insights and performance metrics for your daily reports</p>
        </div>
        <div class="text-muted" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">
            Last updated: {{ date('M j, Y g:i A') }}
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="d-inline-flex p-3 rounded-circle" style="background: #e6f4ea; color: #34a853;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124; line-height: 1.2;">${{ number_format($analytics['overview']['today']['grossSales'], 0) }}</div>
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 1rem; font-weight: 500; color: #202124; margin-top: 0.25rem;">Today's Sales</div>
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem; color: #5f6368;">{{ $analytics['overview']['today']['reports'] }} reports</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="d-inline-flex p-3 rounded-circle" style="background: #e8f0fe; color: #4285f4;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124; line-height: 1.2;">${{ number_format($analytics['overview']['thisWeek']['grossSales'], 0) }}</div>
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 1rem; font-weight: 500; color: #202124; margin-top: 0.25rem;">This Week's Sales</div>
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem; color: #5f6368;">{{ $analytics['overview']['thisWeek']['reports'] }} reports</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="d-inline-flex p-3 rounded-circle" style="background: #fff3e0; color: #f57c00;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                    <path d="M17 14h-6l-2 4h-2l2-4H3l4-7h7l-2 3h6l-1 4z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: #202124; line-height: 1.2;">${{ number_format($analytics['overview']['thisMonth']['grossSales'], 0) }}</div>
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 1rem; font-weight: 500; color: #202124; margin-top: 0.25rem;">This Month's Sales</div>
                            <div style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem; color: #5f6368;">{{ $analytics['overview']['thisMonth']['reports'] }} reports</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Charts Column -->
        <div class="col-lg-8">
            <!-- Daily Trends Chart -->
            <div class="card mb-4">
                <div class="card-header border-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500; color: #202124;">📈 Daily Sales Trends (Last 30 Days)</h3>
                </div>
                <div class="card-body">
                    <canvas id="dailyTrendsChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Monthly Comparison -->
            <div class="card mb-4">
                <div class="card-header border-0">
                    <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500; color: #202124;">📊 Month-over-Month Comparison</h3>
                </div>
                <div class="card-body">
                    @if($analytics['monthlyComparison']['changes'])
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card" style="border: 1px solid #e0e0e0;">
                                    <div class="card-body text-center">
                                        <h4 style="font-family: 'Google Sans', sans-serif; font-size: 1.5rem; font-weight: 400; color: #202124; margin-bottom: 0.5rem;">${{ number_format($analytics['monthlyComparison']['current']->gross_sales ?? 0, 0) }}</h4>
                                        <div class="text-muted" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">Gross Sales</div>
                                        @if($analytics['monthlyComparison']['changes']['gross_sales'])
                                            <div class="mt-2">
                                                <span class="badge {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['gross_sales'] }}%
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card" style="border: 1px solid #e0e0e0;">
                                    <div class="card-body text-center">
                                        <h4 style="font-family: 'Google Sans', sans-serif; font-size: 1.5rem; font-weight: 400; color: #202124; margin-bottom: 0.5rem;">${{ number_format($analytics['monthlyComparison']['current']->net_sales ?? 0, 0) }}</h4>
                                        <div class="text-muted" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">Net Sales</div>
                                        @if($analytics['monthlyComparison']['changes']['net_sales'])
                                            <div class="mt-2">
                                                <span class="badge {{ $analytics['monthlyComparison']['changes']['net_sales'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $analytics['monthlyComparison']['changes']['net_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['net_sales'] }}%
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card" style="border: 1px solid #e0e0e0;">
                                    <div class="card-body text-center">
                                        <h4 style="font-family: 'Google Sans', sans-serif; font-size: 1.5rem; font-weight: 400; color: #202124; margin-bottom: 0.5rem;">{{ $analytics['monthlyComparison']['current']->reports ?? 0 }}</h4>
                                        <div class="text-muted" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">Reports</div>
                                        @if($analytics['monthlyComparison']['changes']['reports'])
                                            <div class="mt-2">
                                                <span class="badge {{ $analytics['monthlyComparison']['changes']['reports'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $analytics['monthlyComparison']['changes']['reports'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['reports'] }}%
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card" style="border: 1px solid #e0e0e0;">
                                    <div class="card-body text-center">
                                        <h4 style="font-family: 'Google Sans', sans-serif; font-size: 1.5rem; font-weight: 400; color: #202124; margin-bottom: 0.5rem;">{{ number_format($analytics['monthlyComparison']['current']->avg_customers ?? 0, 0) }}</h4>
                                        <div class="text-muted" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">Avg Customers</div>
                                        @if($analytics['monthlyComparison']['changes']['avg_customers'])
                                            <div class="mt-2">
                                                <span class="badge {{ $analytics['monthlyComparison']['changes']['avg_customers'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $analytics['monthlyComparison']['changes']['avg_customers'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['avg_customers'] }}%
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <p>Not enough data for comparison yet. Add more reports to see trends!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Store Performance Chart -->
            <div class="google-card chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">🏪 Store Performance Distribution</h3>
                </div>
                <div class="card-body">
                    @if($analytics['storePerformance']->count() > 0)
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="storePerformanceChart" style="max-height: 300px;"></canvas>
                            </div>
                            <div class="col-md-6">
                                <table class="store-performance-table">
                                    <thead>
                                        <tr>
                                            <th>Store</th>
                                            <th>Sales</th>
                                            <th>Reports</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($analytics['storePerformance']->take(5) as $store)
                                            <tr>
                                                <td><strong>{{ Str::limit($store->store_info, 20) }}</strong></td>
                                                <td>${{ number_format($store->total_gross, 0) }}</td>
                                                <td>{{ $store->report_count }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <p>No store performance data available for the last 30 days.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Financial Analysis -->
            @if(!empty($analytics['financialAnalysis']))
            <div class="google-card chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">💰 Financial Analysis (Last 30 Days)</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <h4 class="text-success">{{ $analytics['financialAnalysis']['profitMargin'] }}%</h4>
                                <small class="text-muted">Profit Margin</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <h4 class="text-info">{{ $analytics['financialAnalysis']['taxRate'] }}%</h4>
                                <small class="text-muted">Tax Rate</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <h4 class="text-warning">{{ $analytics['financialAnalysis']['creditCardRatio'] }}%</h4>
                                <small class="text-muted">Credit Card Usage</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <h4 class="text-primary">${{ number_format($analytics['financialAnalysis']['avgDailySales'], 0) }}</h4>
                                <small class="text-muted">Avg Daily Sales</small>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <canvas id="financialBreakdownChart" style="max-height: 250px;"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Key Metrics</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><strong>Cancel Rate:</strong> {{ $analytics['financialAnalysis']['cancelRate'] }}%</li>
                                <li class="mb-2"><strong>Void Rate:</strong> {{ $analytics['financialAnalysis']['voidRate'] }}%</li>
                                <li class="mb-2"><strong>Coupon Usage:</strong> {{ $analytics['financialAnalysis']['couponUsage'] }}%</li>
                                <li class="mb-2"><strong>Total Reports:</strong> {{ $analytics['financialAnalysis']['reportCount'] }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Customer Analytics -->
            @if(!empty($analytics['customerAnalytics']))
            <div class="google-card chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">👥 Customer Analytics (Last 30 Days)</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card text-center">
                                <h4 class="text-primary">{{ number_format($analytics['customerAnalytics']['totalCustomers']) }}</h4>
                                <small class="text-muted">Total Customers</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <h4 class="text-success">${{ $analytics['customerAnalytics']['avgTicketAmount'] }}</h4>
                                <small class="text-muted">Avg Ticket Size</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-center">
                                <h4 class="text-info">{{ $analytics['customerAnalytics']['avgDailyCustomers'] }}</h4>
                                <small class="text-muted">Avg Daily Customers</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <canvas id="customerTrendsChart" style="max-height: 250px;"></canvas>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Insights Column -->
        <div class="col-lg-4">
            <!-- Insights and Alerts -->
            <div class="google-card chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">💡 Insights & Alerts</h3>
                </div>
                <div class="card-body">
                    @if($analytics['insights']->count() > 0)
                        @foreach($analytics['insights'] as $insight)
                            <div class="insight-card {{ $insight['type'] }}">
                                <div class="insight-header">
                                    <span class="insight-icon">{{ $insight['icon'] }}</span>
                                    <span class="insight-title">{{ $insight['title'] }}</span>
                                </div>
                                <div class="insight-message">{{ $insight['message'] }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted">
                            <p>🎉 All looks good! No critical insights at the moment.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Top Performing Days -->
            <div class="google-card chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">🏆 Top Performing Days</h3>
                </div>
                <div class="card-body">
                    @if($analytics['topDays']->count() > 0)
                        @foreach($analytics['topDays'] as $index => $day)
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 rounded" style="background: {{ $index === 0 ? '#fff3cd' : '#f8f9fa' }}">
                                <div>
                                    <strong>{{ $day->report_date->format('M j, Y') }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $day->store->store_info }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success">${{ number_format($day->gross_sales, 0) }}</div>
                                    @if($index === 0)
                                        <small class="text-warning">🥇 Best Day</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted">
                            <p>Add more reports to see top performing days!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Admin User Management -->
            @if(Auth::user()->isAdmin())
            <div class="google-card chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">👥 User Impersonation</h3>
                </div>
                <div class="card-body">
                    @php
                        $owners = \App\Models\User::where('role', \App\Enums\UserRole::OWNER)->orderBy('name')->take(3)->get();
                        $managers = \App\Models\User::where('role', \App\Enums\UserRole::MANAGER)->orderBy('name')->take(3)->get();
                        $totalOwners = \App\Models\User::where('role', \App\Enums\UserRole::OWNER)->count();
                        $totalManagers = \App\Models\User::where('role', \App\Enums\UserRole::MANAGER)->count();
                    @endphp

                    <!-- Quick Actions -->
                    <div class="d-grid gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#userSelectionModal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="M21 21l-4.35-4.35"/>
                            </svg>
                            🔍 Search & Login as User
                        </button>
                    </div>

                    <!-- Quick Access - Recent Users -->
                    @if($owners->count() > 0)
                    <h6 class="mb-2" style="font-family: 'Google Sans', sans-serif; color: #202124;">👨‍💼 Owners ({{ $totalOwners }} total)</h6>
                    @foreach($owners as $owner)
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: #f8f9fa;">
                            <div class="d-flex align-items-center">
                                <span class="avatar me-2" style="background-image: url({{ $owner->avatar_url }}); width: 24px; height: 24px;"></span>
                                <div>
                                    <small><strong>{{ $owner->name }}</strong></small>
                                    <br>
                                    <small class="text-muted">{{ $owner->email }}</small>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('impersonate.start', $owner) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Login as {{ $owner->name }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                        <polyline points="10,17 15,12 10,7"/>
                                        <line x1="15" y1="12" x2="3" y2="12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                    @if($totalOwners > 3)
                        <div class="text-center mb-2">
                            <small class="text-muted">+{{ $totalOwners - 3 }} more owners available</small>
                        </div>
                    @endif
                    @endif

                    @if($managers->count() > 0)
                    <h6 class="mb-2 mt-3" style="font-family: 'Google Sans', sans-serif; color: #202124;">👨‍💻 Managers ({{ $totalManagers }} total)</h6>
                    @foreach($managers as $manager)
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: #f8f9fa;">
                            <div class="d-flex align-items-center">
                                <span class="avatar me-2" style="background-image: url({{ $manager->avatar_url }}); width: 24px; height: 24px;"></span>
                                <div>
                                    <small><strong>{{ $manager->name }}</strong></small>
                                    <br>
                                    <small class="text-muted">{{ $manager->email }}</small>
                                    @if($manager->store)
                                        <br>
                                        <small class="text-info">📍 {{ Str::limit($manager->store->store_info, 20) }}</small>
                                    @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('impersonate.start', $manager) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Login as {{ $manager->name }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                        <polyline points="10,17 15,12 10,7"/>
                                        <line x1="15" y1="12" x2="3" y2="12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endforeach
                    @if($totalManagers > 3)
                        <div class="text-center mb-2">
                            <small class="text-muted">+{{ $totalManagers - 3 }} more managers available</small>
                        </div>
                    @endif
                    @endif

                    @if($owners->count() == 0 && $managers->count() == 0)
                        <div class="text-center text-muted py-3">
                            <p>No owners or managers found in the system.</p>
                        </div>
                    @endif

                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ route('managers.index') }}" class="btn btn-sm btn-outline-secondary">
                            📋 Manage All Users
                        </a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Export & Actions -->
            <div class="google-card chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📊 Export & Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2 mb-3">
                        <div class="dropdown">
                            <button class="google-btn google-btn-success dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" style="justify-content: space-between;">
                                📥 Export Data
                            </button>
                            <ul class="dropdown-menu w-100">
                                <li><a class="dropdown-item" href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'overview']) }}">📄 Overview (CSV)</a></li>
                                <li><a class="dropdown-item" href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'store_performance']) }}">🏪 Store Performance (CSV)</a></li>
                                <li><a class="dropdown-item" href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'daily_trends']) }}">📈 Daily Trends (CSV)</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('daily-reports.create') }}" class="google-btn google-btn-primary">
                            📝 Create Report
                        </a>
                        <a href="{{ route('daily-reports.index') }}" class="google-btn google-btn-outlined">
                            📋 View All Reports
                        </a>
                        <button class="google-btn google-btn-outlined" onclick="refreshDashboard()" style="color: var(--google-blue); border-color: var(--google-blue);">
                            🔄 Refresh Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Load Chart.js asynchronously for better performance -->
<script>
    // Load Chart.js asynchronously to avoid blocking page load
    function loadChartJS() {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.async = true;
        script.onload = initializeCharts;
        document.head.appendChild(script);
    }

    // Only load charts if there's actual data
    const hasData = @json($analytics['dailyTrends']->count() > 0 || $analytics['storePerformance']->count() > 0);
    if (hasData) {
        loadChartJS();
    }
</script>
<script>
// Initialize charts only after Chart.js is loaded
function initializeCharts() {
    // Data preparation
    const dailyTrends = @json($analytics['dailyTrends']);
    const storePerformance = @json($analytics['storePerformance']);
    const financialAnalysis = @json($analytics['financialAnalysis'] ?? []);
    const customerAnalytics = @json($analytics['customerAnalytics'] ?? []);

    // Color palette
    const colors = {
        primary: '#007bff',
        success: '#28a745',
        warning: '#ffc107',
        danger: '#dc3545',
        info: '#17a2b8',
        secondary: '#6c757d',
        light: '#f8f9fa',
        dark: '#343a40'
    };

    // 1. Daily Trends Chart
    if (document.getElementById('dailyTrendsChart') && dailyTrends.length > 0) {
        const dailyCtx = document.getElementById('dailyTrendsChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyTrends.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Gross Sales',
                    data: dailyTrends.map(item => parseFloat(item.total_gross || 0)),
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Net Sales',
                    data: dailyTrends.map(item => parseFloat(item.total_net || 0)),
                    borderColor: colors.success,
                    backgroundColor: colors.success + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Store Performance Doughnut Chart
    if (document.getElementById('storePerformanceChart') && storePerformance.length > 0) {
        const storeCtx = document.getElementById('storePerformanceChart').getContext('2d');
        const chartColors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'];

        new Chart(storeCtx, {
            type: 'doughnut',
            data: {
                labels: storePerformance.map(store => store.store_info.substring(0, 20) + '...'),
                datasets: [{
                    data: storePerformance.map(store => parseFloat(store.total_gross)),
                    backgroundColor: chartColors.slice(0, storePerformance.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20 } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': $' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Financial Breakdown Chart
    if (document.getElementById('financialBreakdownChart') && Object.keys(financialAnalysis).length > 0) {
        const financialCtx = document.getElementById('financialBreakdownChart').getContext('2d');
        new Chart(financialCtx, {
            type: 'bar',
            data: {
                labels: ['Profit Margin', 'Tax Rate', 'Cancel Rate', 'Void Rate', 'Coupon Usage'],
                datasets: [{
                    label: 'Percentage',
                    data: [
                        financialAnalysis.profitMargin || 0,
                        financialAnalysis.taxRate || 0,
                        financialAnalysis.cancelRate || 0,
                        financialAnalysis.voidRate || 0,
                        financialAnalysis.couponUsage || 0
                    ],
                    backgroundColor: [
                        colors.success + '80',
                        colors.info + '80',
                        colors.warning + '80',
                        colors.danger + '80',
                        colors.secondary + '80'
                    ],
                    borderColor: [
                        colors.success,
                        colors.info,
                        colors.warning,
                        colors.danger,
                        colors.secondary
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // 4. Customer Trends Chart
    if (document.getElementById('customerTrendsChart') && customerAnalytics.customerTrends) {
        const customerCtx = document.getElementById('customerTrendsChart').getContext('2d');
        new Chart(customerCtx, {
            type: 'line',
            data: {
                labels: customerAnalytics.customerTrends.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Customers',
                    data: customerAnalytics.customerTrends.map(item => parseInt(item.customers || 0)),
                    borderColor: colors.info,
                    backgroundColor: colors.info + '20',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Avg Ticket ($)',
                    data: customerAnalytics.customerTrends.map(item => parseFloat(item.avg_ticket || 0)),
                    borderColor: colors.warning,
                    backgroundColor: colors.warning + '20',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Customers' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Avg Ticket ($)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });
    }
});

// Refresh Dashboard Function
function refreshDashboard() {
    const refreshBtn = document.querySelector('button[onclick="refreshDashboard()"]');
    const originalText = refreshBtn.innerHTML;

    refreshBtn.innerHTML = '⏳ Refreshing...';
    refreshBtn.disabled = true;

    // Simulate refresh - in real implementation, you'd call an AJAX endpoint
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

    // Export functionality with loading state
    document.querySelectorAll('.dropdown-item[href*="export"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const originalText = this.innerHTML;
            this.innerHTML = '⏳ Exporting...';
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
}

// Fallback: if no Chart.js needed, just initialize basic interactions
if (!hasData) {
    document.addEventListener('DOMContentLoaded', function() {
        initializeBasicInteractions();
    });
}

function initializeBasicInteractions() {
    // Refresh Dashboard Function
    const refreshBtn = document.querySelector('button[onclick="refreshDashboard()"]');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshDashboard);
    }

    // Export functionality with loading state
    document.querySelectorAll('.dropdown-item[href*="export"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const originalText = this.innerHTML;
            this.innerHTML = '⏳ Exporting...';
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
}
</script>

<!-- User Selection Modal -->
@if(Auth::user()->isAdmin())
<div class="modal fade" id="userSelectionModal" tabindex="-1" aria-labelledby="userSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 8px 32px rgba(0,0,0,0.1);">
            <div class="modal-header" style="border-bottom: 1px solid #e0e0e0; padding: 20px 24px;">
                <h5 class="modal-title" id="userSelectionModalLabel" style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: #202124;">🔍 Select User to Impersonate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 20px 24px;">
                <!-- Search Input -->
                <div class="mb-3">
                    <input type="text" id="userSearch" class="form-control" placeholder="Search by name or email..." style="border-radius: 20px; border: 1px solid #dadce0; padding: 12px 16px; font-family: 'Google Sans', sans-serif;">
                </div>

                <!-- User Tabs -->
                <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist" style="border-bottom: 2px solid #e8f0fe;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="owners-tab" data-bs-toggle="tab" data-bs-target="#owners" type="button" role="tab" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">
                            👨‍💼 Owners (<span id="ownersCount">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="managers-tab" data-bs-toggle="tab" data-bs-target="#managers" type="button" role="tab" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">
                            👨‍💻 Managers (<span id="managersCount">0</span>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="userTabContent">
                    <!-- Owners Tab -->
                    <div class="tab-pane fade show active" id="owners" role="tabpanel" aria-labelledby="owners-tab">
                        <div id="ownersList" style="max-height: 300px; overflow-y: auto;">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Managers Tab -->
                    <div class="tab-pane fade" id="managers" role="tabpanel" aria-labelledby="managers-tab">
                        <div id="managersList" style="max-height: 300px; overflow-y: auto;">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="loadingState" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading users...</p>
                </div>

                <!-- No Results -->
                <div id="noResults" class="text-center py-4" style="display: none;">
                    <p class="text-muted">No users found matching your search.</p>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #e0e0e0; padding: 16px 24px;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 20px; padding: 8px 20px; font-family: 'Google Sans', sans-serif;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// User Selection Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    const userSelectionModal = document.getElementById('userSelectionModal');
    const userSearch = document.getElementById('userSearch');
    const ownersList = document.getElementById('ownersList');
    const managersList = document.getElementById('managersList');
    const ownersCount = document.getElementById('ownersCount');
    const managersCount = document.getElementById('managersCount');
    const loadingState = document.getElementById('loadingState');
    const noResults = document.getElementById('noResults');

    // Get CSRF token and base URL for forms
    const csrfToken = '{{ csrf_token() }}';
    const impersonateBaseUrl = '{{ rtrim(url('/'), '/') }}/impersonate';

    let allUsers = {
        owners: [],
        managers: []
    };

    // Load users when modal is shown
    userSelectionModal.addEventListener('shown.bs.modal', function() {
        loadUsers();
    });

    // Search functionality
    userSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        filterUsers(query);
    });

    function loadUsers() {
        loadingState.style.display = 'block';
        ownersList.innerHTML = '';
        managersList.innerHTML = '';

        // Load pre-processed data
        setTimeout(() => {
            // Use data passed from the controller
            allUsers.owners = {!! json_encode($modalOwnersData ?? []) !!};
            allUsers.managers = {!! json_encode($modalManagersData ?? []) !!};

            renderUsers();
            loadingState.style.display = 'none';
        }, 500);
    }

    function renderUsers(query = '') {
        const filteredOwners = allUsers.owners.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query)
        );

        const filteredManagers = allUsers.managers.filter(user =>
            user.name.toLowerCase().includes(query) ||
            user.email.toLowerCase().includes(query) ||
            (user.store_name && user.store_name.toLowerCase().includes(query))
        );

        // Update counts
        ownersCount.textContent = filteredOwners.length;
        managersCount.textContent = filteredManagers.length;

        // Render owners
        ownersList.innerHTML = filteredOwners.length > 0
            ? filteredOwners.map(user => createUserCard(user, 'owner')).join('')
            : '<div class="text-center py-3 text-muted">No owners found</div>';

        // Render managers
        managersList.innerHTML = filteredManagers.length > 0
            ? filteredManagers.map(user => createUserCard(user, 'manager')).join('')
            : '<div class="text-center py-3 text-muted">No managers found</div>';

        // Show/hide no results
        const hasResults = filteredOwners.length > 0 || filteredManagers.length > 0;
        noResults.style.display = hasResults ? 'none' : 'block';
    }

    function createUserCard(user, type) {
        const storeInfo = user.store_name
            ? `<br><small class="text-info">📍 ${user.store_name}</small>`
            : '';

        return `
            <div class="d-flex justify-content-between align-items-center mb-2 p-3 rounded border" style="background: #f8f9fa;">
                <div class="d-flex align-items-center">
                    <span class="avatar me-3" style="background-image: url(${user.avatar_url}); width: 32px; height: 32px; border-radius: 50%; background-size: cover;"></span>
                    <div>
                        <div><strong>${user.name}</strong></div>
                        <small class="text-muted">${user.email}</small>
                        ${storeInfo}
                    </div>
                </div>
                <form method="POST" action="${impersonateBaseUrl}/${user.id}" class="d-inline">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <button type="submit" class="btn btn-primary btn-sm" title="Login as ${user.name}" style="border-radius: 20px; padding: 6px 16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                            <polyline points="10,17 15,12 10,7"/>
                            <line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Login As
                    </button>
                </form>
            </div>
        `;
    }

    function filterUsers(query) {
        renderUsers(query);
    }
});
</script>
@endif

@endsection