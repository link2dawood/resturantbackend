@extends('layouts.tabler')
@section('title', 'Analytics Dashboard')

@section('styles')
<style>
    /* Material UI Typography */
    .material-headline {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 2rem;
        font-weight: 400;
        line-height: 2.5rem;
        letter-spacing: 0;
        color: #202124;
        margin: 0;
    }
    
    .material-subtitle {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 400;
        line-height: 1.25rem;
        color: #5f6368;
        margin: 0.5rem 0 0 0;
    }
    
    /* Material UI Cards */
    .card-material {
        background: #ffffff;
        border-radius: 4px;
        box-shadow: 0px 2px 1px -1px rgba(0, 0, 0, 0.2), 0px 1px 1px 0px rgba(0, 0, 0, 0.14), 0px 1px 3px 0px rgba(0, 0, 0, 0.12);
        transition: box-shadow 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        height: 100%;
    }
    
    .card-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
    }
    
    /* Stat Card Styling */
    .stat-card {
        padding: 1.5rem;
        height: 100%;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .stat-value {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 2rem;
        font-weight: 400;
        line-height: 2.5rem;
        color: #202124;
        margin: 0.5rem 0 0.25rem 0;
    }
    
    .stat-label {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        color: #202124;
        margin: 0;
    }
    
    .stat-meta {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        color: #5f6368;
        margin-top: 0.25rem;
    }
    
    /* Chart Card Header */
    .chart-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        background: #fafafa;
    }
    
    .chart-card-title {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1rem;
        font-weight: 500;
        color: #202124;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .chart-card-body {
        padding: 1.5rem;
    }
    
    /* Material UI Buttons */
    .btn-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 0.0892857143em;
        text-transform: uppercase;
        padding: 0.625rem 1.5rem;
        border-radius: 4px;
        border: none;
        box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 0px 2px 2px 0px rgba(0, 0, 0, 0.14), 0px 1px 5px 0px rgba(0, 0, 0, 0.12);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 64px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .btn-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .btn-material-primary {
        background-color: #1976d2;
        color: #fff;
    }
    
    .btn-material-primary:hover {
        background-color: #1565c0;
        color: #fff;
    }
    
    .btn-material-outlined {
        background-color: transparent;
        border: 1px solid rgba(0, 0, 0, 0.12);
        color: #1976d2;
        box-shadow: none;
    }
    
    .btn-material-outlined:hover {
        background-color: rgba(25, 118, 210, 0.04);
        border-color: #1976d2;
        box-shadow: none;
    }
    
    /* Insight Cards */
    .insight-card {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 0.75rem;
        border-left: 4px solid;
    }
    
    .insight-card.success {
        background-color: #e8f5e9;
        border-left-color: #4caf50;
    }
    
    .insight-card.warning {
        background-color: #fff3e0;
        border-left-color: #ff9800;
    }
    
    .insight-card.alert {
        background-color: #ffebee;
        border-left-color: #f44336;
    }
    
    .insight-card.info {
        background-color: #e3f2fd;
        border-left-color: #2196f3;
    }
    
    .insight-title {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        color: #202124;
        margin-bottom: 0.25rem;
    }
    
    .insight-message {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.8125rem;
        color: #5f6368;
        margin: 0;
    }
    
    /* Top Days List */
    .top-day-item {
        padding: 0.75rem;
        border-radius: 4px;
        margin-bottom: 0.5rem;
        background: #fafafa;
        border: 1px solid rgba(0, 0, 0, 0.12);
        transition: all 0.2s ease;
    }
    
    .top-day-item:hover {
        background: #f5f5f5;
        box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .top-day-item.best {
        background: #fff3e0;
        border-color: #ff9800;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .material-headline {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        
        .chart-card-body {
            padding: 1rem;
        }
    }
    
    @media (max-width: 576px) {
        .material-headline {
            font-size: 1.25rem;
            line-height: 1.75rem;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            font-size: 1.25rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="material-headline">Analytics Dashboard</h1>
            <p class="material-subtitle">Real-time insights and performance metrics for your daily reports</p>
        </div>
        <div class="text-muted" style="font-family: 'Google Sans', sans-serif; font-size: 0.875rem;">
            <i class="bi bi-clock me-1"></i>Last updated: {{ date('M j, Y g:i A') }}
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="row g-3 g-md-4 mb-4">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card-material" style="background: white;">
                <div class="stat-card">
                    <div class="d-flex align-items-start">
                        <div class="stat-icon" style="background: #e6f4ea; color: #34a853;">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-value">${{ number_format($analytics['overview']['today']['grossSales'], 0) }}</div>
                            <div class="stat-label">Today's Sales</div>
                            <div class="stat-meta">{{ $analytics['overview']['today']['reports'] }} reports</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card-material" style="background: white;">
                <div class="stat-card">
                    <div class="d-flex align-items-start">
                        <div class="stat-icon" style="background: #e8f0fe; color: #4285f4;">
                            <i class="bi bi-calendar-week"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-value">${{ number_format($analytics['overview']['thisWeek']['grossSales'], 0) }}</div>
                            <div class="stat-label">This Week's Sales</div>
                            <div class="stat-meta">{{ $analytics['overview']['thisWeek']['reports'] }} reports</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card-material" style="background: white;">
                <div class="stat-card">
                    <div class="d-flex align-items-start">
                        <div class="stat-icon" style="background: #fff3e0; color: #f57c00;">
                            <i class="bi bi-calendar-month"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="stat-value">${{ number_format($analytics['overview']['thisMonth']['grossSales'], 0) }}</div>
                            <div class="stat-label">This Month's Sales</div>
                            <div class="stat-meta">{{ $analytics['overview']['thisMonth']['reports'] }} reports</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 g-md-4">
        <!-- Charts Column -->
        <div class="col-12 col-lg-8">
            <!-- Daily Trends Chart -->
            <div class="card-material mb-3 mb-md-4" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-graph-up"></i>
                        Daily Sales Trends (Last 30 Days)
                    </h3>
                </div>
                <div class="chart-card-body">
                    <canvas id="dailyTrendsChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Monthly Comparison -->
            <div class="card-material mb-3 mb-md-4" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-bar-chart"></i>
                        Month-over-Month Comparison
                    </h3>
                </div>
                <div class="chart-card-body">
                    @if($analytics['monthlyComparison']['changes'])
                        <div class="row g-3">
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3" style="background: #fafafa; border-radius: 4px;">
                                    <div class="stat-value" style="font-size: 1.5rem; margin-bottom: 0.5rem;">${{ number_format($analytics['monthlyComparison']['current']->gross_sales ?? 0, 0) }}</div>
                                    <div class="stat-meta">Gross Sales</div>
                                    @if($analytics['monthlyComparison']['changes']['gross_sales'])
                                        <span class="badge {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? 'bg-success' : 'bg-danger' }} mt-2">
                                            {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['gross_sales'] }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3" style="background: #fafafa; border-radius: 4px;">
                                    <div class="stat-value" style="font-size: 1.5rem; margin-bottom: 0.5rem;">${{ number_format($analytics['monthlyComparison']['current']->net_sales ?? 0, 0) }}</div>
                                    <div class="stat-meta">Net Sales</div>
                                    @if($analytics['monthlyComparison']['changes']['net_sales'])
                                        <span class="badge {{ $analytics['monthlyComparison']['changes']['net_sales'] >= 0 ? 'bg-success' : 'bg-danger' }} mt-2">
                                            {{ $analytics['monthlyComparison']['changes']['net_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['net_sales'] }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3" style="background: #fafafa; border-radius: 4px;">
                                    <div class="stat-value" style="font-size: 1.5rem; margin-bottom: 0.5rem;">{{ $analytics['monthlyComparison']['current']->reports ?? 0 }}</div>
                                    <div class="stat-meta">Reports</div>
                                    @if($analytics['monthlyComparison']['changes']['reports'])
                                        <span class="badge {{ $analytics['monthlyComparison']['changes']['reports'] >= 0 ? 'bg-success' : 'bg-danger' }} mt-2">
                                            {{ $analytics['monthlyComparison']['changes']['reports'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['reports'] }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-center p-3" style="background: #fafafa; border-radius: 4px;">
                                    <div class="stat-value" style="font-size: 1.5rem; margin-bottom: 0.5rem;">{{ number_format($analytics['monthlyComparison']['current']->avg_customers ?? 0, 0) }}</div>
                                    <div class="stat-meta">Avg Customers</div>
                                    @if($analytics['monthlyComparison']['changes']['avg_customers'])
                                        <span class="badge {{ $analytics['monthlyComparison']['changes']['avg_customers'] >= 0 ? 'bg-success' : 'bg-danger' }} mt-2">
                                            {{ $analytics['monthlyComparison']['changes']['avg_customers'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['avg_customers'] }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-info-circle" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
                            <p class="mb-0">Not enough data for comparison yet. Add more reports to see trends!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Store Performance Chart -->
            @if($analytics['storePerformance']->count() > 0)
            <div class="card-material mb-3 mb-md-4" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-shop"></i>
                        Store Performance Distribution
                    </h3>
                </div>
                <div class="chart-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <canvas id="storePerformanceChart" style="max-height: 300px;"></canvas>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Store</th>
                                            <th class="text-end">Sales</th>
                                            <th class="text-end">Reports</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($analytics['storePerformance']->take(5) as $store)
                                            <tr>
                                                <td><strong>{{ Str::limit($store->store_info, 25) }}</strong></td>
                                                <td class="text-end">${{ number_format($store->total_gross, 0) }}</td>
                                                <td class="text-end">{{ $store->report_count }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Financial Analysis -->
            @if(!empty($analytics['financialAnalysis']))
            <div class="card-material mb-3 mb-md-4" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-cash-coin"></i>
                        Financial Analysis (Last 30 Days)
                    </h3>
                </div>
                <div class="chart-card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3" style="background: #e8f5e9; border-radius: 4px;">
                                <div class="stat-value" style="font-size: 1.5rem; color: #4caf50;">{{ $analytics['financialAnalysis']['profitMargin'] }}%</div>
                                <div class="stat-meta">Profit Margin</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3" style="background: #e3f2fd; border-radius: 4px;">
                                <div class="stat-value" style="font-size: 1.5rem; color: #2196f3;">{{ $analytics['financialAnalysis']['taxRate'] }}%</div>
                                <div class="stat-meta">Tax Rate</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3" style="background: #fff3e0; border-radius: 4px;">
                                <div class="stat-value" style="font-size: 1.5rem; color: #ff9800;">{{ $analytics['financialAnalysis']['creditCardRatio'] }}%</div>
                                <div class="stat-meta">Credit Card Usage</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3" style="background: #e8f0fe; border-radius: 4px;">
                                <div class="stat-value" style="font-size: 1.5rem; color: #1976d2;">${{ number_format($analytics['financialAnalysis']['avgDailySales'], 0) }}</div>
                                <div class="stat-meta">Avg Daily Sales</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <canvas id="financialBreakdownChart" style="max-height: 250px;"></canvas>
                        </div>
                        <div class="col-12 col-md-6">
                            <h6 class="mb-3" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">Key Metrics</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-x-circle me-2 text-danger"></i><strong>Cancel Rate:</strong> {{ $analytics['financialAnalysis']['cancelRate'] }}%</li>
                                <li class="mb-2"><i class="bi bi-x-circle me-2 text-warning"></i><strong>Void Rate:</strong> {{ $analytics['financialAnalysis']['voidRate'] }}%</li>
                                <li class="mb-2"><i class="bi bi-ticket-perforated me-2 text-info"></i><strong>Coupon Usage:</strong> {{ $analytics['financialAnalysis']['couponUsage'] }}%</li>
                                <li class="mb-2"><i class="bi bi-file-text me-2 text-primary"></i><strong>Total Reports:</strong> {{ $analytics['financialAnalysis']['reportCount'] }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Customer Analytics -->
            @if(!empty($analytics['customerAnalytics']))
            <div class="card-material mb-3 mb-md-4" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-people"></i>
                        Customer Analytics (Last 30 Days)
                    </h3>
                </div>
                <div class="chart-card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <div class="text-center p-3" style="background: #e3f2fd; border-radius: 4px;">
                                <div class="stat-value" style="font-size: 1.5rem; color: #2196f3;">{{ number_format($analytics['customerAnalytics']['totalCustomers']) }}</div>
                                <div class="stat-meta">Total Customers</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="text-center p-3" style="background: #e8f5e9; border-radius: 4px;">
                                <div class="stat-value" style="font-size: 1.5rem; color: #4caf50;">${{ $analytics['customerAnalytics']['avgTicketAmount'] }}</div>
                                <div class="stat-meta">Avg Ticket Size</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="text-center p-3" style="background: #fff3e0; border-radius: 4px;">
                                <div class="stat-value" style="font-size: 1.5rem; color: #ff9800;">{{ $analytics['customerAnalytics']['avgDailyCustomers'] }}</div>
                                <div class="stat-meta">Avg Daily Customers</div>
                            </div>
                        </div>
                    </div>
                    <canvas id="customerTrendsChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar Column -->
        <div class="col-12 col-lg-4">
            <!-- Insights and Alerts -->
            <div class="card-material mb-3 mb-md-4" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-lightbulb"></i>
                        Insights & Alerts
                    </h3>
                </div>
                <div class="chart-card-body">
                    @if($analytics['insights']->count() > 0)
                        @foreach($analytics['insights'] as $insight)
                            <div class="insight-card {{ $insight['type'] }}">
                                <div class="insight-title">
                                    <i class="bi {{ $insight['type'] === 'success' ? 'bi-check-circle' : ($insight['type'] === 'warning' ? 'bi-exclamation-triangle' : ($insight['type'] === 'alert' ? 'bi-exclamation-circle' : 'bi-info-circle')) }} me-2"></i>
                                    {{ $insight['title'] }}
                                </div>
                                <div class="insight-message">{{ $insight['message'] }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-check-circle" style="font-size: 2rem; color: #4caf50; opacity: 0.5; display: block; margin-bottom: 0.5rem;"></i>
                            <p class="mb-0">All looks good! No critical insights at the moment.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Top Performing Days -->
            <div class="card-material mb-3 mb-md-4" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-trophy"></i>
                        Top Performing Days
                    </h3>
                </div>
                <div class="chart-card-body">
                    @if($analytics['topDays']->count() > 0)
                        @foreach($analytics['topDays'] as $index => $day)
                            <div class="top-day-item {{ $index === 0 ? 'best' : '' }}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-label">{{ $day->report_date->format('M j, Y') }}</div>
                                        <div class="stat-meta">{{ $day->store?->store_info ?? 'N/A' }}</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-value" style="font-size: 1.25rem; color: #4caf50;">${{ number_format($day->gross_sales, 0) }}</div>
                                        @if($index === 0)
                                            <span class="badge bg-warning text-dark mt-1">
                                                <i class="bi bi-trophy-fill me-1"></i>Best Day
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-calendar-x" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
                            <p class="mb-0">Add more reports to see top performing days!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Admin User Management -->
            @if(Auth::user()->isAdmin())
            <div class="card-material mb-3 mb-md-4" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-people"></i>
                        User Impersonation
                    </h3>
                </div>
                <div class="chart-card-body">
                    @php
                        $owners = \App\Models\User::where('role', \App\Enums\UserRole::OWNER)->orderBy('name')->take(3)->get();
                        $managers = \App\Models\User::where('role', \App\Enums\UserRole::MANAGER)->orderBy('name')->take(3)->get();
                        $totalOwners = \App\Models\User::where('role', \App\Enums\UserRole::OWNER)->count();
                        $totalManagers = \App\Models\User::where('role', \App\Enums\UserRole::MANAGER)->count();
                    @endphp

                    <button type="button" class="btn btn-material btn-material-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#userSelectionModal">
                        <i class="bi bi-search"></i>
                        <span>Search & Login as User</span>
                    </button>

                    @if($owners->count() > 0)
                    <h6 class="mb-2" style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: #202124;">
                        <i class="bi bi-person-badge me-1"></i>Owners ({{ $totalOwners }} total)
                    </h6>
                    @foreach($owners as $owner)
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: #fafafa; border: 1px solid rgba(0, 0, 0, 0.12);">
                            <div class="d-flex align-items-center">
                                <img src="{{ $owner->avatar_url }}" alt="{{ $owner->name }}" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                <div>
                                    <div class="stat-label" style="font-size: 0.8125rem; margin: 0;">{{ $owner->name }}</div>
                                    <div class="stat-meta" style="font-size: 0.75rem;">{{ $owner->email }}</div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('impersonate.start', $owner) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-material btn-material-outlined" title="Login as {{ $owner->name }}" style="min-width: auto; padding: 0.375rem 0.75rem;">
                                    <i class="bi bi-box-arrow-in-right"></i>
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
                    <h6 class="mb-2 mt-3" style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: #202124;">
                        <i class="bi bi-person-gear me-1"></i>Managers ({{ $totalManagers }} total)
                    </h6>
                    @foreach($managers as $manager)
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background: #fafafa; border: 1px solid rgba(0, 0, 0, 0.12);">
                            <div class="d-flex align-items-center">
                                <img src="{{ $manager->avatar_url }}" alt="{{ $manager->name }}" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                <div>
                                    <div class="stat-label" style="font-size: 0.8125rem; margin: 0;">{{ $manager->name }}</div>
                                    <div class="stat-meta" style="font-size: 0.75rem;">{{ $manager->email }}</div>
                                    @if($manager->store)
                                        <div class="stat-meta" style="font-size: 0.75rem; color: #1976d2;">
                                            <i class="bi bi-geo-alt me-1"></i>{{ Str::limit($manager->store->store_info, 20) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('impersonate.start', $manager) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-material btn-material-outlined" title="Login as {{ $manager->name }}" style="min-width: auto; padding: 0.375rem 0.75rem;">
                                    <i class="bi bi-box-arrow-in-right"></i>
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
                            <i class="bi bi-person-x" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
                            <p class="mb-0">No owners or managers found in the system.</p>
                        </div>
                    @endif

                    <a href="{{ route('managers.index') }}" class="btn btn-material btn-material-outlined w-100 mt-3">
                        <i class="bi bi-list-ul"></i>
                        <span>Manage All Users</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- Export & Actions -->
            <div class="card-material" style="background: white;">
                <div class="chart-card-header">
                    <h3 class="chart-card-title">
                        <i class="bi bi-download"></i>
                        Export & Actions
                    </h3>
                </div>
                <div class="chart-card-body">
                    <div class="dropdown mb-3">
                        <button class="btn btn-material btn-material-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i>
                            <span>Export Data</span>
                        </button>
                        <ul class="dropdown-menu w-100">
                            <li><a class="dropdown-item" href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'overview']) }}">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Overview (CSV)
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'store_performance']) }}">
                                <i class="bi bi-shop me-2"></i>Store Performance (CSV)
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'daily_trends']) }}">
                                <i class="bi bi-graph-up me-2"></i>Daily Trends (CSV)
                            </a></li>
                        </ul>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="{{ route('daily-reports.create') }}" class="btn btn-material btn-material-primary">
                            <i class="bi bi-plus-circle"></i>
                            <span>Create Report</span>
                        </a>
                        <a href="{{ route('daily-reports.index') }}" class="btn btn-material btn-material-outlined">
                            <i class="bi bi-list-ul"></i>
                            <span>View All Reports</span>
                        </a>
                        <button class="btn btn-material btn-material-outlined" onclick="refreshDashboard()">
                            <i class="bi bi-arrow-clockwise"></i>
                            <span>Refresh Data</span>
                        </button>
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
        primary: '#1976d2',
        success: '#4caf50',
        warning: '#ff9800',
        danger: '#f44336',
        info: '#2196f3',
        secondary: '#757575',
        light: '#f8f9fa',
        dark: '#212121'
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
        const chartColors = ['#1976d2', '#4caf50', '#ff9800', '#f44336', '#2196f3', '#9c27b0', '#00bcd4', '#795548'];

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
}

// Refresh Dashboard Function
function refreshDashboard() {
    const refreshBtn = document.querySelector('button[onclick="refreshDashboard()"]');
    const originalText = refreshBtn.innerHTML;

    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i><span>Refreshing...</span>';
    refreshBtn.disabled = true;

    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Fallback: if no Chart.js needed, just initialize basic interactions
if (!hasData) {
    document.addEventListener('DOMContentLoaded', function() {
        initializeBasicInteractions();
    });
}

function initializeBasicInteractions() {
    // Export functionality with loading state
    document.querySelectorAll('.dropdown-item[href*="export"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Exporting...';
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
        <div class="modal-content" style="border-radius: 4px; border: none; box-shadow: 0px 8px 10px 1px rgba(0, 0, 0, 0.14), 0px 3px 14px 2px rgba(0, 0, 0, 0.12);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(0, 0, 0, 0.12); padding: 1.5rem;">
                <h5 class="modal-title" id="userSelectionModalLabel" style="font-family: 'Google Sans', sans-serif; font-weight: 500; color: #202124;">
                    <i class="bi bi-search me-2"></i>Select User to Impersonate
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <!-- Search Input -->
                <div class="mb-3">
                    <input type="text" id="userSearch" class="form-control" placeholder="Search by name or email..." style="border-radius: 4px; border: 1px solid rgba(0, 0, 0, 0.12); padding: 0.75rem 1rem; font-family: 'Google Sans', sans-serif;">
                </div>

                <!-- User Tabs -->
                <ul class="nav nav-tabs mb-3" id="userTabs" role="tablist" style="border-bottom: 2px solid #e8f0fe;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="owners-tab" data-bs-toggle="tab" data-bs-target="#owners" type="button" role="tab" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">
                            <i class="bi bi-person-badge me-1"></i>Owners (<span id="ownersCount">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="managers-tab" data-bs-toggle="tab" data-bs-target="#managers" type="button" role="tab" style="font-family: 'Google Sans', sans-serif; font-weight: 500;">
                            <i class="bi bi-person-gear me-1"></i>Managers (<span id="managersCount">0</span>)
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="userTabContent">
                    <div class="tab-pane fade show active" id="owners" role="tabpanel" aria-labelledby="owners-tab">
                        <div id="ownersList" style="max-height: 300px; overflow-y: auto;">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
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
                    <i class="bi bi-search" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
                    <p class="text-muted mb-0">No users found matching your search.</p>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(0, 0, 0, 0.12); padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-material btn-material-outlined" data-bs-dismiss="modal">Close</button>
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

    const csrfToken = '{{ csrf_token() }}';
    const impersonateBaseUrl = '{{ rtrim(url('/'), '/') }}/impersonate';

    let allUsers = {
        owners: [],
        managers: []
    };

    userSelectionModal.addEventListener('shown.bs.modal', function() {
        loadUsers();
    });

    userSearch.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        filterUsers(query);
    });

    function loadUsers() {
        loadingState.style.display = 'block';
        ownersList.innerHTML = '';
        managersList.innerHTML = '';

        setTimeout(() => {
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

        ownersCount.textContent = filteredOwners.length;
        managersCount.textContent = filteredManagers.length;

        ownersList.innerHTML = filteredOwners.length > 0
            ? filteredOwners.map(user => createUserCard(user, 'owner')).join('')
            : '<div class="text-center py-3 text-muted">No owners found</div>';

        managersList.innerHTML = filteredManagers.length > 0
            ? filteredManagers.map(user => createUserCard(user, 'manager')).join('')
            : '<div class="text-center py-3 text-muted">No managers found</div>';

        const hasResults = filteredOwners.length > 0 || filteredManagers.length > 0;
        noResults.style.display = hasResults ? 'none' : 'block';
    }

    function createUserCard(user, type) {
        const storeInfo = user.store_name
            ? `<br><small class="text-info"><i class="bi bi-geo-alt me-1"></i>${user.store_name}</small>`
            : '';

        return `
            <div class="d-flex justify-content-between align-items-center mb-2 p-3 rounded border" style="background: #fafafa;">
                <div class="d-flex align-items-center">
                    <img src="${user.avatar_url}" alt="${user.name}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                    <div>
                        <div><strong>${user.name}</strong></div>
                        <small class="text-muted">${user.email}</small>
                        ${storeInfo}
                    </div>
                </div>
                <form method="POST" action="${impersonateBaseUrl}/${user.id}" class="d-inline">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <button type="submit" class="btn btn-material btn-material-primary btn-sm" title="Login as ${user.name}" style="min-width: auto; padding: 0.5rem 1rem;">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login As
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
