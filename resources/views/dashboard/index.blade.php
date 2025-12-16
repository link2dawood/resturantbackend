@extends('layouts.tabler')
@section('title', 'Analytics Dashboard')

@section('styles')
<style>
    /* Minimalist Dashboard Styles */
    .dashboard-header {
        margin-bottom: 2rem;
    }
    
    .stat-card-simple {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.2s ease;
    }
    
    .stat-card-simple:hover {
        border-color: rgba(0, 0, 0, 0.12);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }
    
    .stat-value-simple {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 2rem;
        font-weight: 400;
        color: #202124;
        margin-bottom: 0.5rem;
    }
    
    .stat-label-simple {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        color: #5f6368;
        margin: 0;
    }
    
    .card-simple {
        background: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .card-title-simple {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1rem;
        font-weight: 500;
        color: #202124;
        margin: 0 0 1.5rem 0;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }
    
    .metric-simple {
        text-align: center;
        padding: 1rem;
    }
    
    .metric-value-simple {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.5rem;
        font-weight: 400;
        color: #202124;
        margin-bottom: 0.25rem;
    }
    
    .metric-label-simple {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        color: #5f6368;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-simple {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
        margin-top: 0.5rem;
    }
    
    .table-simple {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table-simple th {
        text-align: left;
        padding: 0.75rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: #5f6368;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }
    
    .table-simple td {
        padding: 0.75rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        color: #202124;
    }
    
    .table-simple tr:last-child td {
        border-bottom: none;
    }
    
    .insight-simple {
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        border-left: 3px solid;
        background: #fafafa;
        border-radius: 4px;
    }
    
    .insight-simple.success {
        border-left-color: #4caf50;
    }
    
    .insight-simple.info {
        border-left-color: #2196f3;
    }
    
    .insight-simple.warning {
        border-left-color: #ff9800;
    }
    
    .day-item-simple {
        padding: 0.75rem;
        margin-bottom: 0.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }
    
    .day-item-simple:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    @media (max-width: 768px) {
        .stat-value-simple {
            font-size: 1.5rem;
        }
        
        .card-simple {
            padding: 1rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <!-- Page Header -->
    <div class="dashboard-header">
        <h1 class="material-headline">Dashboard</h1>
        <p class="material-subtitle">Sales overview and analytics</p>
    </div>

    <!-- Overview Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="stat-card-simple">
                <div class="stat-value-simple">${{ number_format($analytics['overview']['today']['grossSales'], 0) }}</div>
                <div class="stat-label-simple">Today's Sales</div>
                <div class="stat-label-simple" style="font-size: 0.75rem; margin-top: 0.25rem;">{{ $analytics['overview']['today']['reports'] }} reports</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-simple">
                <div class="stat-value-simple">${{ number_format($analytics['overview']['thisWeek']['grossSales'], 0) }}</div>
                <div class="stat-label-simple">This Week</div>
                <div class="stat-label-simple" style="font-size: 0.75rem; margin-top: 0.25rem;">{{ $analytics['overview']['thisWeek']['reports'] }} reports</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card-simple">
                <div class="stat-value-simple">${{ number_format($analytics['overview']['thisMonth']['grossSales'], 0) }}</div>
                <div class="stat-label-simple">This Month</div>
                <div class="stat-label-simple" style="font-size: 0.75rem; margin-top: 0.25rem;">{{ $analytics['overview']['thisMonth']['reports'] }} reports</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Main Content -->
        <div class="col-12 col-lg-8">
            <!-- Daily Trends Chart -->
            <div class="card-simple">
                <h3 class="card-title-simple">Sales Trends</h3>
                <canvas id="dailyTrendsChart" style="max-height: 300px;"></canvas>
            </div>

            <!-- Monthly Comparison -->
            @if($analytics['monthlyComparison']['changes'])
            <div class="card-simple">
                <h3 class="card-title-simple">Monthly Comparison</h3>
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="metric-simple">
                            <div class="metric-value-simple">${{ number_format($analytics['monthlyComparison']['current']->gross_sales ?? 0, 0) }}</div>
                            <div class="metric-label-simple">Gross Sales</div>
                            @if($analytics['monthlyComparison']['changes']['gross_sales'])
                                <span class="badge-simple {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                                    {{ $analytics['monthlyComparison']['changes']['gross_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['gross_sales'] }}%
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="metric-simple">
                            <div class="metric-value-simple">${{ number_format($analytics['monthlyComparison']['current']->net_sales ?? 0, 0) }}</div>
                            <div class="metric-label-simple">Net Sales</div>
                            @if($analytics['monthlyComparison']['changes']['net_sales'])
                                <span class="badge-simple {{ $analytics['monthlyComparison']['changes']['net_sales'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                                    {{ $analytics['monthlyComparison']['changes']['net_sales'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['net_sales'] }}%
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="metric-simple">
                            <div class="metric-value-simple">{{ $analytics['monthlyComparison']['current']->reports ?? 0 }}</div>
                            <div class="metric-label-simple">Reports</div>
                            @if($analytics['monthlyComparison']['changes']['reports'])
                                <span class="badge-simple {{ $analytics['monthlyComparison']['changes']['reports'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                                    {{ $analytics['monthlyComparison']['changes']['reports'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['reports'] }}%
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="metric-simple">
                            <div class="metric-value-simple">{{ number_format($analytics['monthlyComparison']['current']->avg_customers ?? 0, 0) }}</div>
                            <div class="metric-label-simple">Avg Customers</div>
                            @if($analytics['monthlyComparison']['changes']['avg_customers'])
                                <span class="badge-simple {{ $analytics['monthlyComparison']['changes']['avg_customers'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                                    {{ $analytics['monthlyComparison']['changes']['avg_customers'] >= 0 ? '+' : '' }}{{ $analytics['monthlyComparison']['changes']['avg_customers'] }}%
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Store Performance -->
            @if($analytics['storePerformance']->count() > 0)
            <div class="card-simple">
                <h3 class="card-title-simple">Store Performance</h3>
                <div class="table-responsive">
                    <table class="table-simple">
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
                                    <td>{{ Str::limit($store->store_info, 30) }}</td>
                                    <td class="text-end">${{ number_format($store->total_gross, 0) }}</td>
                                    <td class="text-end">{{ $store->report_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Insights -->
            @if($analytics['insights']->count() > 0)
            <div class="card-simple">
                <h3 class="card-title-simple">Insights</h3>
                @foreach($analytics['insights'] as $insight)
                    <div class="insight-simple {{ $insight['type'] }}">
                        <div style="font-size: 0.875rem; font-weight: 500; color: #202124; margin-bottom: 0.25rem;">{{ $insight['title'] }}</div>
                        <div style="font-size: 0.8125rem; color: #5f6368;">{{ $insight['message'] }}</div>
                    </div>
                @endforeach
            </div>
            @endif

            <!-- Top Days -->
            @if($analytics['topDays']->count() > 0)
            <div class="card-simple">
                <h3 class="card-title-simple">Top Days</h3>
                @foreach($analytics['topDays']->take(5) as $day)
                    <div class="day-item-simple">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div style="font-size: 0.875rem; color: #202124;">{{ $day->report_date->format('M j, Y') }}</div>
                                <div style="font-size: 0.75rem; color: #5f6368;">{{ $day->store?->store_info ?? 'N/A' }}</div>
                            </div>
                            <div style="font-size: 0.875rem; font-weight: 500; color: #202124;">${{ number_format($day->gross_sales, 0) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif

            <!-- Actions -->
            @if(Auth::user()->hasPermission('create_reports'))
            <div class="card-simple">
                <h3 class="card-title-simple">Actions</h3>
                <div class="d-grid gap-2">
                    <a href="{{ route('daily-reports.create') }}" class="btn btn-material btn-material-primary">
                        <i class="bi bi-plus-circle"></i>
                        <span>Create Report</span>
                    </a>
                    <a href="{{ route('daily-reports.index') }}" class="btn btn-material btn-material-outlined">
                        <i class="bi bi-list-ul"></i>
                        <span>View Reports</span>
                    </a>
                </div>
            </div>
            @endif

            <!-- Admin User Impersonation -->
            @if(Auth::user()->isAdmin())
            <div class="card-simple">
                <h3 class="card-title-simple">User Management</h3>
                <button type="button" class="btn btn-material btn-material-primary w-100 mb-3" data-bs-toggle="modal" data-bs-target="#userSelectionModal">
                    <i class="bi bi-search"></i>
                    <span>Login as User</span>
                </button>
                <a href="{{ route('managers.index') }}" class="btn btn-material btn-material-outlined w-100">
                    <i class="bi bi-people"></i>
                    <span>Manage Users</span>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- User Selection Modal -->
@if(Auth::user()->isAdmin())
<div class="modal fade" id="userSelectionModal" tabindex="-1" aria-labelledby="userSelectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userSelectionModalLabel">Select User to Impersonate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="userSearch" class="form-control" placeholder="Search by name or email...">
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Owners</h6>
                        <div id="ownersList" style="max-height: 300px; overflow-y: auto;">
                            @php
                                $owners = \App\Models\User::where('role', \App\Enums\UserRole::OWNER)->orderBy('name')->get();
                            @endphp
                            @foreach($owners as $owner)
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                    <div>
                                        <div>{{ $owner->name }}</div>
                                        <small class="text-muted">{{ $owner->email }}</small>
                                    </div>
                                    <form method="POST" action="{{ route('impersonate.start', $owner) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-material btn-material-outlined">Login</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Managers</h6>
                        <div id="managersList" style="max-height: 300px; overflow-y: auto;">
                            @php
                                $managers = \App\Models\User::where('role', \App\Enums\UserRole::MANAGER)->orderBy('name')->get();
                            @endphp
                            @foreach($managers as $manager)
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                    <div>
                                        <div>{{ $manager->name }}</div>
                                        <small class="text-muted">{{ $manager->email }}</small>
                                        @if($manager->store)
                                            <div><small class="text-info">{{ $manager->store->store_info }}</small></div>
                                        @endif
                                    </div>
                                    <form method="POST" action="{{ route('impersonate.start', $manager) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-material btn-material-outlined">Login</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-material btn-material-outlined" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Load Chart.js -->
<script>
    function loadChartJS() {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.async = true;
        script.onload = initializeCharts;
        document.head.appendChild(script);
    }

    const hasData = @json($analytics['dailyTrends']->count() > 0 || $analytics['storePerformance']->count() > 0);
    if (hasData) {
        loadChartJS();
    }

    function initializeCharts() {
        const dailyTrends = @json($analytics['dailyTrends']);
        const storePerformance = @json($analytics['storePerformance']);

        // Daily Trends Chart
        if (document.getElementById('dailyTrendsChart') && dailyTrends.length > 0) {
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
