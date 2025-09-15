@extends('layouts.tabler')
@section('title', 'Analytics Dashboard')

@section('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
@endsection

@section('content')

<div class="container-xl px-4">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">📊 Analytics Dashboard</h1>
                <p class="mb-0">Real-time insights and performance metrics for your daily reports</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="text-white-50">
                    Last updated: {{ date('M j, Y g:i A') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card today">
                <div class="stat-value">${{ number_format($analytics['overview']['today']['grossSales'], 0) }}</div>
                <div class="stat-label">Today's Sales</div>
                <small class="text-muted">{{ $analytics['overview']['today']['reports'] }} reports</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card week">
                <div class="stat-value">${{ number_format($analytics['overview']['thisWeek']['grossSales'], 0) }}</div>
                <div class="stat-label">This Week's Sales</div>
                <small class="text-muted">{{ $analytics['overview']['thisWeek']['reports'] }} reports</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card month">
                <div class="stat-value">${{ number_format($analytics['overview']['thisMonth']['grossSales'], 0) }}</div>
                <div class="stat-label">This Month's Sales</div>
                <small class="text-muted">{{ $analytics['overview']['thisMonth']['reports'] }} reports</small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Charts Column -->
        <div class="col-lg-8">
            <!-- Daily Trends Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📈 Daily Sales Trends (Last 30 Days)</h3>
                </div>
                <div class="chart-body">
                    <canvas id="dailyTrendsChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Monthly Comparison -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📊 Month-over-Month Comparison</h3>
                </div>
                <div class="chart-body">
                    @if($analytics['monthlyComparison']['changes'])
                        <div class="row">
                            <div class="col-md-3">
                                <div class="comparison-card">
                                    <div class="comparison-metric">
                                        <div class="comparison-value">${{ number_format($analytics['monthlyComparison']['current']->gross_sales ?? 0, 0) }}</div>
                                        <div class="text-muted">Gross Sales</div>
                                        @if($analytics['monthlyComparison']['changes']['gross_sales'])
                                            <div class="comparison-change {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? 'change-positive' : 'change-negative' }}">
                                                {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['gross_sales'] }}%
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="comparison-card">
                                    <div class="comparison-metric">
                                        <div class="comparison-value">${{ number_format($analytics['monthlyComparison']['current']->net_sales ?? 0, 0) }}</div>
                                        <div class="text-muted">Net Sales</div>
                                        @if($analytics['monthlyComparison']['changes']['net_sales'])
                                            <div class="comparison-change {{ $analytics['monthlyComparison']['changes']['net_sales'] >= 0 ? 'change-positive' : 'change-negative' }}">
                                                {{ $analytics['monthlyComparison']['changes']['net_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['net_sales'] }}%
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="comparison-card">
                                    <div class="comparison-metric">
                                        <div class="comparison-value">{{ $analytics['monthlyComparison']['current']->reports ?? 0 }}</div>
                                        <div class="text-muted">Reports</div>
                                        @if($analytics['monthlyComparison']['changes']['reports'])
                                            <div class="comparison-change {{ $analytics['monthlyComparison']['changes']['reports'] >= 0 ? 'change-positive' : 'change-negative' }}">
                                                {{ $analytics['monthlyComparison']['changes']['reports'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['reports'] }}%
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="comparison-card">
                                    <div class="comparison-metric">
                                        <div class="comparison-value">{{ number_format($analytics['monthlyComparison']['current']->avg_customers ?? 0, 0) }}</div>
                                        <div class="text-muted">Avg Customers</div>
                                        @if($analytics['monthlyComparison']['changes']['avg_customers'])
                                            <div class="comparison-change {{ $analytics['monthlyComparison']['changes']['avg_customers'] >= 0 ? 'change-positive' : 'change-negative' }}">
                                                {{ $analytics['monthlyComparison']['changes']['avg_customers'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['avg_customers'] }}%
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
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">🏪 Store Performance Distribution</h3>
                </div>
                <div class="chart-body">
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
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">💰 Financial Analysis (Last 30 Days)</h3>
                </div>
                <div class="chart-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-success">{{ $analytics['financialAnalysis']['profitMargin'] }}%</h4>
                                <small class="text-muted">Profit Margin</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-info">{{ $analytics['financialAnalysis']['taxRate'] }}%</h4>
                                <small class="text-muted">Tax Rate</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-warning">{{ $analytics['financialAnalysis']['creditCardRatio'] }}%</h4>
                                <small class="text-muted">Credit Card Usage</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 bg-light rounded">
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
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">👥 Customer Analytics (Last 30 Days)</h3>
                </div>
                <div class="chart-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-primary">{{ number_format($analytics['customerAnalytics']['totalCustomers']) }}</h4>
                                <small class="text-muted">Total Customers</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="text-success">${{ $analytics['customerAnalytics']['avgTicketAmount'] }}</h4>
                                <small class="text-muted">Avg Ticket Size</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
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
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">💡 Insights & Alerts</h3>
                </div>
                <div class="chart-body">
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
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">🏆 Top Performing Days</h3>
                </div>
                <div class="chart-body">
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

            <!-- Export & Actions -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📊 Export & Actions</h3>
                </div>
                <div class="chart-body">
                    <div class="d-grid gap-2 mb-3">
                        <div class="dropdown">
                            <button class="btn btn-success dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
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
                        <a href="{{ route('daily-reports.quick-entry') }}" class="btn btn-primary">
                            ⚡ Quick Entry
                        </a>
                        <a href="{{ route('daily-reports.create') }}" class="btn btn-outline-primary">
                            📝 Full Report
                        </a>
                        <a href="{{ route('daily-reports.index') }}" class="btn btn-outline-secondary">
                            📋 View All Reports
                        </a>
                        <button class="btn btn-outline-info" onclick="refreshDashboard()">
                            🔄 Refresh Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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
</script>

@endsection