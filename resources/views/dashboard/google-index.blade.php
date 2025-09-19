@extends('layouts.tabler')

@section('title', 'Analytics Dashboard')
@section('page-title', 'Restaurant Analytics')
@section('page-subtitle', 'Real-time insights and performance metrics for your daily operations')

@section('page-actions')
<div class="gd-flex gd-gap-sm">
    <a href="{{ route('daily-reports.create') }}" class="gd-button gd-button-primary">
        <span class="material-symbols-outlined">add</span>
        New Report
    </a>
</div>
@endsection

@section('content')

<!-- Key Performance Indicators - Progressive Disclosure Level 1 -->
<section aria-labelledby="kpi-heading" class="gd-mb-xl">
    <h2 id="kpi-heading" class="gd-sr-only">Key Performance Indicators</h2>

    <div class="gd-grid gd-grid-cols-1 md:gd-grid-cols-2 lg:gd-grid-cols-4 gd-gap-lg">
        <!-- Today's Sales - Success Emotion -->
        <div class="gd-stat-card success" role="img" aria-label="Today's sales: ${{ number_format($analytics['overview']['today']['grossSales'], 0) }}" title="Total gross sales recorded today across all stores">
            <div class="gd-stat-icon success">
                <span class="material-symbols-outlined">trending_up</span>
            </div>
            <div class="gd-stat-value">${{ number_format($analytics['overview']['today']['grossSales'], 0) }}</div>
            <div class="gd-stat-label">Today's Sales</div>
            <div class="gd-stat-change positive">
                <span class="material-symbols-outlined" style="font-size: 16px;">description</span>
                <span>{{ $analytics['overview']['today']['reports'] }} reports submitted</span>
            </div>
        </div>

        <!-- Weekly Sales - Trust Building -->
        <div class="gd-stat-card trust" role="img" aria-label="This week's sales: ${{ number_format($analytics['overview']['thisWeek']['grossSales'], 0) }}">
            <div class="gd-stat-icon trust">
                <span class="material-symbols-outlined">calendar_view_week</span>
            </div>
            <div class="gd-stat-value">${{ number_format($analytics['overview']['thisWeek']['grossSales'], 0) }}</div>
            <div class="gd-stat-label">This Week</div>
            <div class="gd-stat-change neutral">
                <span class="material-symbols-outlined" style="font-size: 16px;">description</span>
                <span>{{ $analytics['overview']['thisWeek']['reports'] }} reports</span>
            </div>
        </div>

        <!-- Monthly Performance -->
        <div class="gd-stat-card trust" role="img" aria-label="This month's sales: ${{ number_format($analytics['overview']['thisMonth']['grossSales'], 0) }}">
            <div class="gd-stat-icon trust">
                <span class="material-symbols-outlined">calendar_month</span>
            </div>
            <div class="gd-stat-value">${{ number_format($analytics['overview']['thisMonth']['grossSales'], 0) }}</div>
            <div class="gd-stat-label">This Month</div>
            <div class="gd-stat-change neutral">
                <span class="material-symbols-outlined" style="font-size: 16px;">assessment</span>
                <span>{{ $analytics['overview']['thisMonth']['reports'] }} reports</span>
            </div>
        </div>

        <!-- Growth Indicator -->
        @if($analytics['monthlyComparison']['changes'] && $analytics['monthlyComparison']['changes']['gross_sales'])
        <div class="gd-stat-card {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? 'success' : 'warning' }}"
             role="img"
             aria-label="Growth: {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['gross_sales'] }}%">
            <div class="gd-stat-icon {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? 'success' : 'warning' }}">
                <span class="material-symbols-outlined">{{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? 'trending_up' : 'trending_down' }}</span>
            </div>
            <div class="gd-stat-value">{{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['gross_sales'] }}%</div>
            <div class="gd-stat-label">Growth Rate</div>
            <div class="gd-stat-change {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? 'positive' : 'negative' }}">
                <span class="material-symbols-outlined" style="font-size: 16px;">compare_arrows</span>
                <span>vs last month</span>
            </div>
        </div>
        @else
        <div class="gd-stat-card trust">
            <div class="gd-stat-icon trust">
                <span class="material-symbols-outlined">insights</span>
            </div>
            <div class="gd-stat-value">--</div>
            <div class="gd-stat-label">Growth Rate</div>
            <div class="gd-stat-change neutral">
                <span class="gd-body-small">Need more data</span>
            </div>
        </div>
        @endif
    </div>
</section>

<!-- Main Analytics Grid - Progressive Disclosure Level 2 -->
<div class="gd-grid gd-grid-cols-1 lg:gd-grid-cols-3 gd-gap-xl">

    <!-- Primary Analytics Column -->
    <div class="lg:gd-col-span-2 gd-flex gd-flex-col gd-gap-xl">

        <!-- Daily Trends Chart - Primary Focus -->
        <div class="gd-card">
            <div class="gd-card-header">
                <h3 class="gd-card-title">Daily Sales Trends</h3>
                <p class="gd-card-subtitle">Last 30 days performance overview</p>
            </div>
            <div class="gd-card-body">
                @if(count($analytics['dailyTrends']) > 0)
                <div class="gd-relative" style="height: 300px;">
                    <canvas id="daily-trends-chart" class="gd-w-full gd-h-full"></canvas>
                </div>
                @else
                <div class="gd-flex gd-flex-col gd-items-center gd-justify-center gd-p-xl gd-text-center">
                    <div class="gd-bg-trust gd-rounded-lg gd-p-lg gd-mb-md">
                        <span class="material-symbols-outlined gd-text-trust" style="font-size: 48px;">trending_up</span>
                    </div>
                    <h4 class="gd-title-medium gd-mb-xs">Start Building Your Analytics</h4>
                    <p class="gd-body-medium gd-text-secondary gd-mb-lg">Create your first daily report to see trends and insights.</p>
                    <a href="{{ route('daily-reports.create') }}" class="gd-button gd-button-primary">
                        <span class="material-symbols-outlined">add</span>
                        Create First Report
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Financial Breakdown - Secondary Priority -->
        @if(!empty($analytics['financialAnalysis']))
        <div class="gd-card">
            <div class="gd-card-header">
                <h3 class="gd-card-title">Financial Breakdown</h3>
                <p class="gd-card-subtitle">Key financial metrics from the last 30 days</p>
            </div>
            <div class="gd-card-body">
                <div class="gd-grid gd-grid-cols-2 lg:gd-grid-cols-4 gd-gap-lg gd-mb-lg">
                    <div class="gd-text-center gd-p-md gd-bg-surface-1 gd-rounded-lg">
                        <div class="gd-title-large gd-text-success">{{ $analytics['financialAnalysis']['profitMargin'] }}%</div>
                        <div class="gd-body-small gd-text-secondary">Profit Margin</div>
                    </div>
                    <div class="gd-text-center gd-p-md gd-bg-surface-1 gd-rounded-lg">
                        <div class="gd-title-large gd-text-trust">{{ $analytics['financialAnalysis']['taxRate'] }}%</div>
                        <div class="gd-body-small gd-text-secondary">Tax Rate</div>
                    </div>
                    <div class="gd-text-center gd-p-md gd-bg-surface-1 gd-rounded-lg">
                        <div class="gd-title-large gd-text-warning">{{ $analytics['financialAnalysis']['creditCardRatio'] }}%</div>
                        <div class="gd-body-small gd-text-secondary">Credit Card Usage</div>
                    </div>
                    <div class="gd-text-center gd-p-md gd-bg-surface-1 gd-rounded-lg">
                        <div class="gd-title-large gd-text-primary">${{ number_format($analytics['financialAnalysis']['avgDailySales'], 0) }}</div>
                        <div class="gd-body-small gd-text-secondary">Avg Daily Sales</div>
                    </div>
                </div>

                <div class="gd-grid gd-grid-cols-1 lg:gd-grid-cols-2 gd-gap-lg">
                    <div class="gd-relative" style="height: 200px;">
                        <canvas id="financial-chart" class="gd-w-full gd-h-full"></canvas>
                    </div>
                    <div class="gd-flex gd-flex-col gd-justify-center gd-gap-sm">
                        <div class="gd-flex gd-justify-between gd-items-center">
                            <span class="gd-body-medium">Cancel Rate</span>
                            <span class="gd-label-medium gd-text-error">{{ $analytics['financialAnalysis']['cancelRate'] }}%</span>
                        </div>
                        <div class="gd-flex gd-justify-between gd-items-center">
                            <span class="gd-body-medium">Void Rate</span>
                            <span class="gd-label-medium gd-text-warning">{{ $analytics['financialAnalysis']['voidRate'] }}%</span>
                        </div>
                        <div class="gd-flex gd-justify-between gd-items-center">
                            <span class="gd-body-medium">Coupon Usage</span>
                            <span class="gd-label-medium gd-text-trust">{{ $analytics['financialAnalysis']['couponUsage'] }}%</span>
                        </div>
                        <div class="gd-flex gd-justify-between gd-items-center">
                            <span class="gd-body-medium">Total Reports</span>
                            <span class="gd-label-medium gd-text-primary">{{ $analytics['financialAnalysis']['reportCount'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Store Performance -->
        @if($analytics['storePerformance']->count() > 0)
        <div class="gd-card">
            <div class="gd-card-header">
                <h3 class="gd-card-title">Store Performance</h3>
                <p class="gd-card-subtitle">Revenue distribution across locations</p>
            </div>
            <div class="gd-card-body">
                <div class="gd-grid gd-grid-cols-1 lg:gd-grid-cols-2 gd-gap-lg">
                    <div class="gd-relative" style="height: 250px;">
                        <canvas id="store-performance-chart" class="gd-w-full gd-h-full"></canvas>
                    </div>
                    <div class="gd-flex gd-flex-col gd-gap-sm">
                        @foreach($analytics['storePerformance']->take(5) as $index => $store)
                        <div class="gd-flex gd-items-center gd-justify-between gd-p-sm gd-bg-surface-1 gd-rounded-md">
                            <div class="gd-flex gd-items-center gd-gap-sm">
                                <div class="w-3 h-3 gd-rounded-full" style="background-color: {{ ['#4285f4', '#34a853', '#fbbc04', '#ea4335', '#9aa0a6'][$index % 5] }};"></div>
                                <div>
                                    <div class="gd-label-medium">{{ Str::limit($store->store_info, 20) }}</div>
                                    <div class="gd-body-small gd-text-secondary">{{ $store->report_count }} reports</div>
                                </div>
                            </div>
                            <div class="gd-text-right">
                                <div class="gd-label-large">${{ number_format($store->total_gross, 0) }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar - Insights & Actions -->
    <div class="gd-flex gd-flex-col gd-gap-xl">

        <!-- Quick Actions -->
        <div class="gd-card">
            <div class="gd-card-header">
                <h3 class="gd-card-title">Quick Actions</h3>
                <p class="gd-card-subtitle">Common tasks and shortcuts</p>
            </div>
            <div class="gd-card-body gd-flex gd-flex-col gd-gap-sm">
                <a href="{{ route('daily-reports.create') }}" class="gd-button gd-button-primary gd-justify-start">
                    <span class="material-symbols-outlined">description</span>
                    Create Report
                </a>
                <a href="{{ route('daily-reports.index') }}" class="gd-button gd-button-outlined gd-justify-start">
                    <span class="material-symbols-outlined">list</span>
                    View All Reports
                </a>
                <hr class="gd-border-surface-3 gd-my-sm">
                <details class="gd-relative">
                    <summary class="gd-button gd-button-outlined gd-justify-between gd-pointer">
                        <span class="gd-flex gd-items-center gd-gap-sm">
                            <span class="material-symbols-outlined">file_download</span>
                            Export Data
                        </span>
                        <span class="material-symbols-outlined">expand_more</span>
                    </summary>
                    <div class="gd-mt-sm gd-flex gd-flex-col gd-gap-xs">
                        <a href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'overview']) }}" class="gd-button gd-button-text gd-justify-start">
                            <span class="material-symbols-outlined">table_view</span>
                            Overview (CSV)
                        </a>
                        <a href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'store_performance']) }}" class="gd-button gd-button-text gd-justify-start">
                            <span class="material-symbols-outlined">store</span>
                            Store Performance
                        </a>
                        <a href="{{ route('dashboard.export', ['format' => 'csv', 'type' => 'daily_trends']) }}" class="gd-button gd-button-text gd-justify-start">
                            <span class="material-symbols-outlined">trending_up</span>
                            Daily Trends
                        </a>
                    </div>
                </details>
                <button class="gd-button gd-button-text gd-justify-start" onclick="refreshDashboard()">
                    <span class="material-symbols-outlined">refresh</span>
                    Refresh Data
                </button>
            </div>
        </div>

        <!-- Insights & Alerts -->
        @if($analytics['insights']->count() > 0)
        <div class="gd-card">
            <div class="gd-card-header">
                <h3 class="gd-card-title">Insights & Alerts</h3>
                <p class="gd-card-subtitle">AI-powered business insights</p>
            </div>
            <div class="gd-card-body gd-flex gd-flex-col gd-gap-sm">
                @foreach($analytics['insights'] as $insight)
                <div class="gd-p-sm gd-rounded-md gd-bg-{{ $insight['type'] === 'success' ? 'success' : ($insight['type'] === 'warning' ? 'warning' : 'trust') }}">
                    <div class="gd-flex gd-items-start gd-gap-sm">
                        <span class="material-symbols-outlined" style="font-size: 20px;">{{ $insight['icon'] ?? 'info' }}</span>
                        <div>
                            <div class="gd-label-medium">{{ $insight['title'] }}</div>
                            <div class="gd-body-small gd-text-secondary gd-mt-xs">{{ $insight['message'] }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Top Performing Days -->
        @if($analytics['topDays']->count() > 0)
        <div class="gd-card">
            <div class="gd-card-header">
                <h3 class="gd-card-title">Top Performing Days</h3>
                <p class="gd-card-subtitle">Best sales days this month</p>
            </div>
            <div class="gd-card-body gd-flex gd-flex-col gd-gap-sm">
                @foreach($analytics['topDays']->take(3) as $index => $day)
                <div class="gd-p-sm gd-rounded-md {{ $index === 0 ? 'gd-bg-warning' : 'gd-bg-surface-1' }}">
                    <div class="gd-flex gd-items-center gd-justify-between">
                        <div class="gd-flex gd-items-center gd-gap-sm">
                            @if($index === 0)
                            <span class="material-symbols-outlined gd-text-warning" style="font-size: 20px;">trophy</span>
                            @endif
                            <div>
                                <div class="gd-label-medium">{{ $day->report_date->format('M j, Y') }}</div>
                                <div class="gd-body-small gd-text-secondary">{{ $day->store->store_info }}</div>
                            </div>
                        </div>
                        <div class="gd-text-right">
                            <div class="gd-label-large gd-text-success">${{ number_format($day->gross_sales, 0) }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Floating Action Button for Mobile -->
<a href="{{ route('daily-reports.create') }}" class="gd-fab lg:gd-hidden" aria-label="Create new report">
    <span class="material-symbols-outlined">add</span>
</a>

@endsection

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Color palette following Google's Material Design
    const colors = {
        primary: '#4285f4',
        success: '#34a853',
        warning: '#fbbc04',
        error: '#ea4335',
        secondary: '#9aa0a6',
        surface: '#f8f9fa'
    };

    // Data preparation
    const dailyTrends = @json($analytics['dailyTrends']);
    const storePerformance = @json($analytics['storePerformance']);
    const financialAnalysis = @json($analytics['financialAnalysis'] ?? []);

    // Daily Trends Chart
    if (document.getElementById('daily-trends-chart') && dailyTrends.length > 0) {
        const ctx = document.getElementById('daily-trends-chart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyTrends.map(item => new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                datasets: [{
                    label: 'Gross Sales',
                    data: dailyTrends.map(item => parseFloat(item.total_gross || 0)),
                    borderColor: colors.primary,
                    backgroundColor: colors.primary + '20',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: colors.primary,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }, {
                    label: 'Net Sales',
                    data: dailyTrends.map(item => parseFloat(item.total_net || 0)),
                    borderColor: colors.success,
                    backgroundColor: colors.success + '20',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: colors.success,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { family: 'Google Sans', size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { family: 'Google Sans', size: 14 },
                        bodyFont: { family: 'Google Sans', size: 13 },
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
                        grid: { color: colors.surface },
                        ticks: {
                            font: { family: 'Google Sans', size: 12 },
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Google Sans', size: 12 } }
                    }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    // Store Performance Chart
    if (document.getElementById('store-performance-chart') && storePerformance.length > 0) {
        const ctx = document.getElementById('store-performance-chart').getContext('2d');
        const chartColors = [colors.primary, colors.success, colors.warning, colors.error, colors.secondary];

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: storePerformance.map(store => store.store_info.substring(0, 20) + '...'),
                datasets: [{
                    data: storePerformance.map(store => parseFloat(store.total_gross)),
                    backgroundColor: chartColors.slice(0, storePerformance.length),
                    borderColor: '#ffffff',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { family: 'Google Sans', size: 14 },
                        bodyFont: { family: 'Google Sans', size: 13 },
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': $' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Financial Breakdown Chart
    if (document.getElementById('financial-chart') && Object.keys(financialAnalysis).length > 0) {
        const ctx = document.getElementById('financial-chart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Profit %', 'Tax %', 'Cancel %', 'Void %'],
                datasets: [{
                    data: [
                        financialAnalysis.profitMargin || 0,
                        financialAnalysis.taxRate || 0,
                        financialAnalysis.cancelRate || 0,
                        financialAnalysis.voidRate || 0
                    ],
                    backgroundColor: [
                        colors.success + '80',
                        colors.primary + '80',
                        colors.warning + '80',
                        colors.error + '80'
                    ],
                    borderColor: [colors.success, colors.primary, colors.warning, colors.error],
                    borderWidth: 2,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { family: 'Google Sans', size: 14 },
                        bodyFont: { family: 'Google Sans', size: 13 },
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
                        grid: { color: colors.surface },
                        ticks: {
                            font: { family: 'Google Sans', size: 12 },
                            callback: function(value) { return value + '%'; }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Google Sans', size: 12 } }
                    }
                }
            }
        });
    }
});

// Refresh Dashboard Function with Loading State
function refreshDashboard() {
    const refreshBtn = event.target;
    const originalContent = refreshBtn.innerHTML;

    // Show loading state
    refreshBtn.innerHTML = '<span class="material-symbols-outlined">refresh</span> Refreshing...';
    refreshBtn.disabled = true;
    refreshBtn.classList.add('gd-loading');

    // Show loading toast
    showToast('Refreshing dashboard data...', 'info', 2000);

    // Simulate refresh - in production, you'd make an AJAX call
    setTimeout(() => {
        window.location.reload();
    }, 1500);
}

// Add loading states to export links
document.querySelectorAll('a[href*="export"]').forEach(link => {
    link.addEventListener('click', function() {
        showToast('Preparing export file...', 'info', 3000);
    });
});
</script>
@endpush