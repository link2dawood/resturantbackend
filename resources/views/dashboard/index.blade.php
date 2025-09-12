@extends('layouts.tabler')
@section('title', 'Analytics Dashboard')
@section('content')

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        text-align: center;
        transition: transform 0.3s ease;
        border-left: 4px solid transparent;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-card.today { border-left-color: #28a745; }
    .stat-card.week { border-left-color: #007bff; }
    .stat-card.month { border-left-color: #fd7e14; }
    
    .stat-value {
        font-size: 2.5rem;
        font-weight: bold;
        color: #495057;
        margin-bottom: 5px;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .chart-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
        overflow: hidden;
    }
    
    .chart-header {
        background: #f8f9fa;
        padding: 20px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .chart-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #495057;
        margin: 0;
    }
    
    .chart-body {
        padding: 20px;
    }
    
    .insight-card {
        background: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
    }
    
    .insight-card.warning { border-left-color: #ffc107; background: #fffbf0; }
    .insight-card.alert { border-left-color: #dc3545; background: #fdf2f2; }
    .insight-card.success { border-left-color: #28a745; background: #f0f8f0; }
    
    .insight-header {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .insight-icon {
        font-size: 1.5rem;
        margin-right: 10px;
    }
    
    .insight-title {
        font-weight: 600;
        color: #495057;
    }
    
    .insight-message {
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .store-performance-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .store-performance-table th {
        background: #f8f9fa;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
    }
    
    .store-performance-table td {
        padding: 12px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .performance-bar {
        height: 6px;
        background: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 5px;
    }
    
    .performance-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        transition: width 0.3s ease;
    }
    
    .comparison-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 15px;
    }
    
    .comparison-metric {
        text-align: center;
    }
    
    .comparison-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #495057;
    }
    
    .comparison-change {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .change-positive { color: #28a745; }
    .change-negative { color: #dc3545; }
    .change-neutral { color: #6c757d; }
</style>

<div class="container-fluid px-4">
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

            <!-- Store Performance -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">🏪 Store Performance (Last 30 Days)</h3>
                </div>
                <div class="chart-body">
                    @if($analytics['storePerformance']->count() > 0)
                        <table class="store-performance-table">
                            <thead>
                                <tr>
                                    <th>Store</th>
                                    <th>Total Sales</th>
                                    <th>Reports</th>
                                    <th>Avg Daily</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $maxSales = $analytics['storePerformance']->max('total_gross'); @endphp
                                @foreach($analytics['storePerformance'] as $store)
                                    <tr>
                                        <td><strong>{{ $store->store_info }}</strong></td>
                                        <td>${{ number_format($store->total_gross, 0) }}</td>
                                        <td>{{ $store->report_count }}</td>
                                        <td>${{ number_format($store->avg_gross, 0) }}</td>
                                        <td>
                                            <div class="performance-bar">
                                                <div class="performance-fill" style="width: {{ $maxSales ? ($store->total_gross / $maxSales * 100) : 0 }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center text-muted">
                            <p>No store performance data available for the last 30 days.</p>
                        </div>
                    @endif
                </div>
            </div>
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
                                    <strong>{{ \App\Helpers\DateFormatter::toUSShort($day->report_date) }}</strong>
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

            <!-- Quick Actions -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">⚡ Quick Actions</h3>
                </div>
                <div class="chart-body">
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
    // Prepare data for daily trends chart
    const dailyTrends = @json($analytics['dailyTrends']);
    
    const ctx = document.getElementById('dailyTrendsChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyTrends.map(item => {
                const date = new Date(item.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }),
            datasets: [{
                label: 'Gross Sales',
                data: dailyTrends.map(item => parseFloat(item.total_gross)),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Net Sales',
                data: dailyTrends.map(item => parseFloat(item.total_net)),
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
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
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
});
</script>

@endsection