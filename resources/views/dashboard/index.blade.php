@extends('layouts.tabler')
@section('title', 'Analytics Dashboard')

@section('styles')
<style>
    /* Modern Dashboard Styles */
    .dashboard-welcome {
        margin-bottom: 2rem;
    }
    
    .welcome-title {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.75rem;
        font-weight: 500;
        color: #202124;
        margin: 0 0 0.5rem 0;
    }
    
    .welcome-subtitle {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        color: #5f6368;
        margin: 0;
    }
    
    .breadcrumb-custom {
        background: transparent;
        padding: 0;
        margin: 0.5rem 0 0 0;
        font-size: 0.8125rem;
    }
    
    .breadcrumb-custom a {
        color: #5f6368;
        text-decoration: none;
    }
    
    .breadcrumb-custom .active {
        color: #1976d2;
        font-weight: 500;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1px solid rgba(0, 0, 0, 0.12);
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #5f6368;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .action-btn:hover {
        background: #f5f5f5;
        color: #202124;
    }
    
    /* Tabs */
    .nav-tabs-custom {
        border-bottom: 2px solid rgba(0, 0, 0, 0.08);
        margin-bottom: 1.5rem;
    }
    
    .nav-tabs-custom .nav-link {
        border: none;
        border-bottom: 2px solid transparent;
        color: #5f6368;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        margin-bottom: -2px;
        background: transparent;
    }
    
    .nav-tabs-custom .nav-link:hover {
        border-bottom-color: rgba(0, 0, 0, 0.12);
        color: #202124;
    }
    
    .nav-tabs-custom .nav-link.active {
        color: #1976d2;
        border-bottom-color: #1976d2;
        background: transparent;
    }
    
    /* KPI Cards */
    .kpi-card {
        background: #f8f9fa;
        border: 1px solid rgba(0, 0, 0, 0.06);
        border-radius: 8px;
        padding: 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    
    .kpi-content {
        flex: 1;
        min-width: 0;
    }
    
    .kpi-label {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        color: #5f6368;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }
    
    .kpi-value {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.125rem;
        font-weight: 500;
        color: #202124;
        margin: 0;
    }
    
    /* Data Cards */
    .data-card {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        padding: 1.5rem;
        position: relative;
        height: 100%;
    }
    
    .data-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }
    
    .data-card-title {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 600;
        color: #202124;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }
    
    .data-card-nav {
        display: flex;
        gap: 0.25rem;
    }
    
    .data-card-nav-btn {
        width: 24px;
        height: 24px;
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 4px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #5f6368;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .data-card-nav-btn:hover {
        background: #f5f5f5;
        color: #202124;
    }
    
    .data-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .data-card-chart {
        height: 60px;
        margin: 1rem 0;
    }
    
    .data-card-percentage {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.5rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .data-card-metric {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        color: #5f6368;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }
    
    .data-card-value {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.5rem;
        font-weight: 500;
        color: #202124;
        margin-bottom: 0.5rem;
    }
    
    .data-card-change {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.8125rem;
        color: #5f6368;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .data-card-description {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.8125rem;
        color: #5f6368;
        line-height: 1.5;
        margin-top: 0.75rem;
    }
    
    .arrow-up {
        color: #4caf50;
    }
    
    .arrow-down {
        color: #f44336;
    }
    
    @media (max-width: 768px) {
        .welcome-title {
            font-size: 1.5rem;
        }
        
        .data-card {
            padding: 1rem;
        }
        
        .kpi-card {
            padding: 0.75rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <!-- Welcome Section -->
    <div class="dashboard-welcome">
        <div class="row align-items-center">
            <div class="col-12 col-md-6">
                <h1 class="welcome-title">Welcome back,</h1>
                <p class="welcome-subtitle">Your analytics dashboard template.</p>
                <nav aria-label="breadcrumb" class="breadcrumb-custom">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Analytics</li>
                    </ol>
                </nav>
            </div>
            <div class="col-12 col-md-6 text-md-end mt-3 mt-md-0">
                <div class="action-buttons justify-content-md-end">
                    <a href="{{ route('dashboard.export', ['format' => 'csv']) }}" class="action-btn" title="Download">
                        <i class="bi bi-download"></i>
                    </a>
                    <button class="action-btn" title="Schedule" onclick="alert('Schedule feature coming soon')">
                        <i class="bi bi-clock"></i>
                    </button>
                    <a href="{{ route('daily-reports.create') }}" class="action-btn" title="Create Report">
                        <i class="bi bi-plus-lg"></i>
                    </a>
                    <a href="{{ route('dashboard.export', ['format' => 'pdf']) }}" class="btn btn-material btn-material-primary">
                        <i class="bi bi-file-pdf me-1"></i>
                        <span>Download report</span>
                    </a>
                </div>
        </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs-custom" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button" role="tab">Sales</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">Reports</button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <!-- KPI Row -->
    <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-lg">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: #e3f2fd; color: #1976d2;">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-label">Start date</div>
                            <div class="kpi-value">{{ now()->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: #e8f5e9; color: #4caf50;">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-label">Revenue</div>
                            <div class="kpi-value">${{ number_format($analytics['overview']['thisMonth']['grossSales'], 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: #fff3e0; color: #ff9800;">
                            <i class="bi bi-eye"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-label">Total views</div>
                            <div class="kpi-value">{{ number_format($analytics['overview']['thisMonth']['reports'] * 100, 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: #e1f5fe; color: #00bcd4;">
                            <i class="bi bi-download"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-label">Downloads</div>
                            <div class="kpi-value">{{ number_format($analytics['overview']['thisMonth']['reports'], 0) }}</div>
                </div>
            </div>
                </div>
                <div class="col-6 col-md-4 col-lg">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: #fce4ec; color: #e91e63;">
                            <i class="bi bi-flag"></i>
                        </div>
                        <div class="kpi-content">
                            <div class="kpi-label">Reports</div>
                            <div class="kpi-value">{{ $analytics['overview']['thisMonth']['reports'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Cards Row -->
            <div class="row g-3">
                <!-- Cash Sales Card -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="data-card">
                        <div class="data-card-header">
                            <h3 class="data-card-title">Cash Sales</h3>
                            <div class="data-card-nav">
                                <button class="data-card-nav-btn"><i class="bi bi-chevron-left"></i></button>
                                <button class="data-card-nav-btn"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                        <div class="data-card-icon" style="background: #e3f2fd; color: #1976d2;">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="data-card-chart">
                            <canvas id="cashSalesChart" height="60"></canvas>
                        </div>
                        @php
                            $cashSales = $analytics['overview']['thisMonth']['grossSales'] ?? 0;
                            $cashSalesChange = $analytics['monthlyComparison']['changes']['gross_sales'] ?? 0;
                        @endphp
                        <div class="data-card-percentage {{ $cashSalesChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                            {{ abs($cashSalesChange) }}%
                            <i class="bi bi-arrow-{{ $cashSalesChange >= 0 ? 'up' : 'down' }}"></i>
                        </div>
                        <div class="data-card-metric">Sales last month</div>
                        <div class="data-card-value">${{ number_format($cashSales, 0) }}</div>
                        <div class="data-card-change {{ $cashSalesChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                            <i class="bi bi-arrow-{{ $cashSalesChange >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($cashSalesChange) }}%
                        </div>
                        <div class="data-card-description">
                            Gross sales of {{ now()->format('F') }}. Track your cash sales performance and monitor daily revenue trends.
                        </div>
                    </div>
                </div>

                <!-- Monthly Income Card -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="data-card">
                        <div class="data-card-header">
                            <h3 class="data-card-title">Monthly Income</h3>
                            <div class="data-card-nav">
                                <button class="data-card-nav-btn"><i class="bi bi-chevron-left"></i></button>
                                <button class="data-card-nav-btn"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                        <div class="data-card-icon" style="background: #fff3e0; color: #ff9800;">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="data-card-chart">
                            <canvas id="monthlyIncomeChart" height="60"></canvas>
                            </div>
                        @php
                            $monthlyIncome = $analytics['overview']['thisMonth']['grossSales'] ?? 0;
                            $incomeChange = $analytics['monthlyComparison']['changes']['gross_sales'] ?? 0;
                        @endphp
                        <div class="data-card-percentage {{ $incomeChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                            {{ abs($incomeChange) }}%
                            <i class="bi bi-arrow-{{ $incomeChange >= 0 ? 'up' : 'down' }}"></i>
                        </div>
                        <div class="data-card-metric">Sales income</div>
                        <div class="data-card-value">${{ number_format($monthlyIncome, 0) }}</div>
                        <div class="data-card-change {{ $incomeChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                            <i class="bi bi-arrow-{{ $incomeChange >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($incomeChange) }}%
                            </div>
                        <div class="data-card-description">
                            Gross sales of {{ now()->format('F') }}. Monitor your monthly income and revenue streams.
                        </div>
                    </div>
        </div>

                <!-- Yearly Sales Card -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="data-card">
                        <div class="data-card-header">
                            <h3 class="data-card-title">Yearly Sales</h3>
                            <div class="data-card-nav">
                                <button class="data-card-nav-btn"><i class="bi bi-chevron-left"></i></button>
                                <button class="data-card-nav-btn"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                        <div class="data-card-icon" style="background: #f3e5f5; color: #9c27b0;">
                            <i class="bi bi-cart3"></i>
                </div>
                        <div class="data-card-chart">
                            <canvas id="yearlySalesChart" height="60"></canvas>
            </div>
                        @php
                            $yearlySales = $analytics['overview']['thisMonth']['grossSales'] * 12 ?? 0;
                            $yearlyChange = $analytics['monthlyComparison']['changes']['gross_sales'] ?? 0;
                        @endphp
                        <div class="data-card-percentage {{ $yearlyChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                            {{ abs($yearlyChange) }}%
                            <i class="bi bi-arrow-{{ $yearlyChange >= 0 ? 'up' : 'down' }}"></i>
                </div>
                        <div class="data-card-metric">Purchases</div>
                        <div class="data-card-value">${{ number_format($yearlySales, 0) }}</div>
                        <div class="data-card-change {{ $yearlyChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                            <i class="bi bi-arrow-{{ $yearlyChange >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs($yearlyChange) }}%
                        </div>
                        <div class="data-card-description">
                            Projected yearly sales based on current month. Track annual performance and growth trends.
                </div>
            </div>
                </div>

                <!-- Daily Deposits Card -->
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="data-card">
                        <div class="data-card-header">
                            <h3 class="data-card-title">Daily Deposits</h3>
                            <div class="data-card-nav">
                                <button class="data-card-nav-btn"><i class="bi bi-chevron-left"></i></button>
                                <button class="data-card-nav-btn"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                        <div class="data-card-icon" style="background: #e8f5e9; color: #4caf50;">
                            <i class="bi bi-bank"></i>
                        </div>
                        <div class="data-card-chart">
                            <canvas id="dailyDepositsChart" height="60"></canvas>
                                </div>
                        @php
                            $dailyDeposits = $analytics['overview']['today']['grossSales'] ?? 0;
                            $depositsChange = 0;
                            if($analytics['overview']['today']['grossSales'] > 0 && isset($analytics['overview']['yesterday']['grossSales'])) {
                                $depositsChange = (($analytics['overview']['today']['grossSales'] - $analytics['overview']['yesterday']['grossSales']) / $analytics['overview']['yesterday']['grossSales']) * 100;
                            }
                        @endphp
                        <div class="data-card-percentage {{ $depositsChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                            {{ abs(round($depositsChange)) }}%
                            <i class="bi bi-arrow-{{ $depositsChange >= 0 ? 'up' : 'down' }}"></i>
                        </div>
                        <div class="data-card-metric">Security deposits</div>
                        <div class="data-card-value">${{ number_format($dailyDeposits, 0) }}</div>
                        <div class="data-card-change {{ $depositsChange >= 0 ? 'arrow-up' : 'arrow-down' }}">
                            <i class="bi bi-arrow-{{ $depositsChange >= 0 ? 'up' : 'down' }}"></i>
                            {{ abs(round($depositsChange)) }}%
                        </div>
                        <div class="data-card-description">
                            Daily deposit tracking. Monitor cash flow and daily revenue collection.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Tab -->
        <div class="tab-pane fade" id="sales" role="tabpanel">
            <div class="row g-3">
                <div class="col-12">
                    <div class="data-card">
                        <h3 class="data-card-title mb-3">Sales Trends</h3>
                        <canvas id="salesTrendsChart" style="max-height: 400px;"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div class="tab-pane fade" id="reports" role="tabpanel">
            <div class="row g-3">
                @if($analytics['storePerformance']->count() > 0)
                <div class="col-12">
                    <div class="data-card">
                        <h3 class="data-card-title mb-3">Store Performance</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Store</th>
                                        <th class="text-end">Sales</th>
                                        <th class="text-end">Reports</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($analytics['storePerformance'] as $store)
                                        <tr>
                                            <td>{{ $store->store_info }}</td>
                                            <td class="text-end">${{ number_format($store->total_gross, 0) }}</td>
                                            <td class="text-end">{{ $store->report_count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
        </div>
    </div>
</div>
</div>

<!-- Load Chart.js -->
<script>
    function loadChartJS() {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.async = true;
        script.onload = initializeCharts;
        document.head.appendChild(script);
    }

    const hasData = @json($analytics['dailyTrends']->count() > 0);
    if (hasData) {
        loadChartJS();
    }

function initializeCharts() {
    const dailyTrends = @json($analytics['dailyTrends']);
        
        // Mini charts for data cards
        const createMiniChart = (canvasId, color, trend) => {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            
            const data = dailyTrends.length > 0 
                ? dailyTrends.slice(-7).map(item => parseFloat(item.total_gross || 0))
                : [0, 0, 0, 0, 0, 0, 0];
            
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['', '', '', '', '', '', ''],
                    datasets: [{
                        data: data,
                        borderColor: color,
                        backgroundColor: color + '20',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    },
                    scales: {
                        x: { display: false },
                        y: { display: false }
                    }
                }
            });
        };

        // Create mini charts
        createMiniChart('cashSalesChart', '#1976d2', 'up');
        createMiniChart('monthlyIncomeChart', '#ff9800', 'up');
        createMiniChart('yearlySalesChart', '#9c27b0', 'down');
        createMiniChart('dailyDepositsChart', '#4caf50', 'up');

        // Main sales trends chart
        if (document.getElementById('salesTrendsChart') && dailyTrends.length > 0) {
            const ctx = document.getElementById('salesTrendsChart').getContext('2d');
            new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyTrends.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Gross Sales',
                    data: dailyTrends.map(item => parseFloat(item.total_gross || 0)),
                        borderColor: '#1976d2',
                        backgroundColor: 'rgba(25, 118, 210, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Net Sales',
                    data: dailyTrends.map(item => parseFloat(item.total_net || 0)),
                        borderColor: '#4caf50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
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
    }
</script>
@endsection
