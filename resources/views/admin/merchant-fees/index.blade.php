@extends('layouts.tabler')

@section('title', 'Merchant Fee Analytics')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Merchant Fee Analytics</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Track credit card processing and third-party platform fees</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.merchant-fees.third-party') }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Third-Party Platforms
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.merchant-fees.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Store</label>
                    <select class="form-select" name="store_id">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Total Fees This Period</div>
                    <div class="h1 mb-3 text-danger">
                        ${{ number_format($merchantProcessing['total_fees'], 2) }}
                    </div>
                    <div class="d-flex align-items-center text-muted">Merchant Processing</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Average Fee %</div>
                    <div class="h1 mb-3 text-primary">
                        {{ number_format($merchantProcessing['average_fee_percentage'], 2) }}%
                    </div>
                    <div class="d-flex align-items-center text-muted">of gross sales</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Total Sales</div>
                    <div class="h1 mb-3 text-success">
                        ${{ number_format($merchantProcessing['total_sales'], 2) }}
                    </div>
                    <div class="d-flex align-items-center text-muted">Credit card sales</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Third-Party Fees</div>
                    <div class="h1 mb-3 text-warning">
                        ${{ number_format($thirdPartyPlatforms['total_fees'], 2) }}
                    </div>
                    <div class="d-flex align-items-center text-muted">Platform costs</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Fees Over Time -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Fees Over Time</h3>
                </div>
                <div class="card-body">
                    <canvas id="trendsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Fees by Processor -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">By Processor</h3>
                </div>
                <div class="card-body">
                    @if($byProcessor->count() > 0)
                        @foreach($byProcessor as $processor)
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="fw-semibold">{{ $processor->processor }}</div>
                                <div class="text-muted small">{{ $processor->transaction_count }} transactions</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-danger">${{ number_format($processor->total_fees, 2) }}</div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center py-4">No data</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Merchant Fee Transactions</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                        <tr>
                            <th>Date</th>
                            <th>Store</th>
                            <th>Processor</th>
                            <th class="text-end">Amount</th>
                            <th>Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
                            <td>{{ $transaction->store->store_info ?? 'N/A' }}</td>
                            <td>{{ $transaction->vendor->vendor_name ?? 'Unknown' }}</td>
                            <td class="text-end"><strong class="text-danger">${{ number_format($transaction->amount, 2) }}</strong></td>
                            <td>
                                @if($transaction->daily_report_id)
                                    <a href="/daily-reports/{{ $transaction->daily_report_id }}" class="btn btn-sm btn-outline-primary">View</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No transactions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let trendsChart;

// Initialize chart on page load
document.addEventListener('DOMContentLoaded', function() {
    // Chart data from server
    const trendsData = @json($trends);
    
    if (trendsData && trendsData.length > 0) {
        const ctx = document.getElementById('trendsChart').getContext('2d');
        trendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendsData.map(t => t.period),
                datasets: [{
                    label: 'Merchant Fees',
                    data: trendsData.map(t => parseFloat(t.total_fees)),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
