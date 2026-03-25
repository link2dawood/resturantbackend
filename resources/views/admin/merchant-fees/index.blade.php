@extends('layouts.tabler')

@section('title', 'Merchant Fee Analytics')

@push('styles')
<style>
    .merchant-fee-loading {
        color: #5f6368;
        font-size: 0.875rem;
    }

    .merchant-fee-empty {
        color: #5f6368;
        text-align: center;
        padding: 2rem 1rem;
    }

    .merchant-fee-muted {
        color: #5f6368;
    }
</style>
@endpush

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Merchant Fee Analytics</h1>
            <p class="text-muted mb-0" id="merchantFeeSummaryText" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">
                Merchant fee analytics is {{ number_format($merchantProcessing['average_fee_percentage'], 2) }}% of all credit card sales received. This rate applies to all online and in-store platform transactions.
            </p>
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
            <form action="{{ route('admin.merchant-fees.index') }}" method="GET" class="row g-3" id="merchant-fee-filters">
                <div class="col-md-3">
                    <label class="form-label">Store</label>
                    <select class="form-select" name="store_id" id="merchant-fee-store">
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
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}" id="merchant-fee-start-date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}" id="merchant-fee-end-date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100" id="merchant-fee-apply">
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
                    <div class="h1 mb-3 text-danger" id="merchantProcessingTotalFees">
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
                    <div class="h1 mb-3 text-primary" id="merchantProcessingAverageFee">
                        {{ number_format($merchantProcessing['average_fee_percentage'], 2) }}%
                    </div>
                    <div class="d-flex align-items-center text-muted" id="merchantProcessingAverageFeeLabel">of all credit card sales received</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Total Sales</div>
                    <div class="h1 mb-3 text-success" id="merchantProcessingTotalSales">
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
                    <div class="h1 mb-3 text-warning" id="thirdPartyTotalFees">
                        ${{ number_format($thirdPartyPlatforms['total_fees'], 2) }}
                    </div>
                    <div class="d-flex align-items-center text-muted" id="thirdPartyAverageFeeLabel">{{ number_format($merchantProcessing['average_fee_percentage'], 2) }}% merchant processing fee</div>
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
                    <div id="trendsChartState" class="merchant-fee-loading mb-2">Loading chart data...</div>
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
                <div class="card-body" id="byProcessorList">
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
                    <tbody id="merchantFeeTransactionsBody">
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

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('merchant-fee-filters');
    const applyButton = document.getElementById('merchant-fee-apply');
    const summaryText = document.getElementById('merchantFeeSummaryText');
    const merchantProcessingTotalFees = document.getElementById('merchantProcessingTotalFees');
    const merchantProcessingAverageFee = document.getElementById('merchantProcessingAverageFee');
    const merchantProcessingTotalSales = document.getElementById('merchantProcessingTotalSales');
    const thirdPartyTotalFees = document.getElementById('thirdPartyTotalFees');
    const thirdPartyAverageFeeLabel = document.getElementById('thirdPartyAverageFeeLabel');
    const processorList = document.getElementById('byProcessorList');
    const transactionsBody = document.getElementById('merchantFeeTransactionsBody');
    const trendsChartState = document.getElementById('trendsChartState');
    const chartCanvas = document.getElementById('trendsChart');

    const fallbackTrends = @json($trends);
    const fallbackProcessors = @json($byProcessor);
    const fallbackTransactions = @json($recentTransactions);

    renderTrendsChart(fallbackTrends);

    filterForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const params = currentParams();
        const queryString = params.toString();
        history.pushState({}, '', queryString ? `?${queryString}` : window.location.pathname);
        refreshMerchantFeeSections();
    });

    refreshMerchantFeeSections();

    function currentParams() {
        const params = new URLSearchParams();
        const storeId = document.getElementById('merchant-fee-store').value;
        const startDate = document.getElementById('merchant-fee-start-date').value;
        const endDate = document.getElementById('merchant-fee-end-date').value;

        if (storeId) params.set('store_id', storeId);
        if (startDate) params.set('start_date', startDate);
        if (endDate) params.set('end_date', endDate);

        return params;
    }

    async function refreshMerchantFeeSections() {
        const params = currentParams();
        const queryString = params.toString();
        const suffix = queryString ? `?${queryString}` : '';

        setLoadingState(true);

        try {
            const [summaryResponse, trendsResponse, processorsResponse, transactionsResponse] = await Promise.all([
                fetch(`/api/merchant-fees/summary${suffix}`, { headers: { 'Accept': 'application/json' } }),
                fetch(`/api/merchant-fees/trends${suffix}`, { headers: { 'Accept': 'application/json' } }),
                fetch(`/api/merchant-fees/by-processor${suffix}`, { headers: { 'Accept': 'application/json' } }),
                fetch(`/api/merchant-fees/transactions${suffix}`, { headers: { 'Accept': 'application/json' } }),
            ]);

            if (!summaryResponse.ok || !trendsResponse.ok || !processorsResponse.ok || !transactionsResponse.ok) {
                throw new Error('Failed to load merchant fee analytics');
            }

            const summaryPayload = await summaryResponse.json();
            const trendsPayload = await trendsResponse.json();
            const processorsPayload = await processorsResponse.json();
            const transactionsPayload = await transactionsResponse.json();

            renderSummary(summaryPayload);
            const trendsData = trendsPayload.trends || [];
            const processorData = Array.isArray(processorsPayload) ? processorsPayload : [];
            const transactionData = Array.isArray(transactionsPayload.data) ? transactionsPayload.data : [];

            renderTrendsChart(trendsData);
            renderProcessors(processorData);
            renderTransactions(transactionData);
        } catch (error) {
            console.error(error);
            renderTrendsChart(fallbackTrends);
            renderProcessors(fallbackProcessors);
            renderTransactions(fallbackTransactions);
            trendsChartState.textContent = 'Unable to refresh live data. Showing the latest page data.';
        } finally {
            setLoadingState(false);
        }
    }

    function setLoadingState(isLoading) {
        applyButton.disabled = isLoading;
        applyButton.innerHTML = isLoading
            ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading...'
            : `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg> Apply Filters
            `;

        if (isLoading) {
            trendsChartState.textContent = 'Loading chart data...';
            processorList.innerHTML = '<p class="merchant-fee-loading text-center py-4 mb-0">Loading processors...</p>';
            transactionsBody.innerHTML = '<tr><td colspan="5" class="merchant-fee-loading text-center py-4">Loading transactions...</td></tr>';
        }
    }

    function renderSummary(summary) {
        const merchantProcessing = summary?.merchant_processing || {};
        const thirdParty = summary?.third_party_platforms || {};

        const merchantFeePct = Number(merchantProcessing.average_fee_percentage || 0);

        merchantProcessingTotalFees.textContent = formatCurrency(merchantProcessing.total_fees || 0);
        merchantProcessingAverageFee.textContent = `${merchantFeePct.toFixed(2)}%`;
        merchantProcessingTotalSales.textContent = formatCurrency(merchantProcessing.total_sales || 0);
        thirdPartyTotalFees.textContent = formatCurrency(thirdParty.total_fees || 0);
        thirdPartyAverageFeeLabel.textContent = `${merchantFeePct.toFixed(2)}% merchant processing fee`;
        summaryText.textContent = `Merchant fee analytics is ${merchantFeePct.toFixed(2)}% of all credit card sales received. This rate applies to all online and in-store platform transactions.`;
    }

    function renderTrendsChart(trendsData) {
        if (trendsChart) {
            trendsChart.destroy();
        }

        if (!Array.isArray(trendsData) || trendsData.length === 0) {
            trendsChartState.textContent = 'No fee trend data for the selected filters.';
            const ctx = chartCanvas.getContext('2d');
            ctx.clearRect(0, 0, chartCanvas.width, chartCanvas.height);
            return;
        }

        trendsChartState.textContent = '';
        const ctx = chartCanvas.getContext('2d');

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

    function renderProcessors(processors) {
        if (!Array.isArray(processors) || processors.length === 0) {
            processorList.innerHTML = '<p class="merchant-fee-empty mb-0">No processor data for the selected filters.</p>';
            return;
        }

        processorList.innerHTML = processors.map((processor) => `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="fw-semibold">${escapeHtml(processor.processor || 'Unknown')}</div>
                    <div class="merchant-fee-muted small">${Number(processor.transaction_count || 0).toLocaleString()} transactions</div>
                </div>
                <div class="text-end">
                    <div class="fw-bold text-danger">$${Number(processor.total_fees || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                </div>
            </div>
        `).join('');
    }

    function renderTransactions(transactions) {
        if (!Array.isArray(transactions) || transactions.length === 0) {
            transactionsBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No transactions found</td></tr>';
            return;
        }

        transactionsBody.innerHTML = transactions.map((transaction) => {
            const transactionDate = transaction.transaction_date
                ? new Date(transaction.transaction_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
                : '-';

            const storeName = transaction.store?.store_info || 'N/A';
            const processorName = transaction.vendor?.vendor_name || 'Unknown';
            const amount = Number(transaction.amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const reportCell = transaction.daily_report_id
                ? `<a href="/daily-reports/${transaction.daily_report_id}" class="btn btn-sm btn-outline-primary">View</a>`
                : '<span class="text-muted">-</span>';

            return `
                <tr>
                    <td>${transactionDate}</td>
                    <td>${escapeHtml(storeName)}</td>
                    <td>${escapeHtml(processorName)}</td>
                    <td class="text-end"><strong class="text-danger">$${amount}</strong></td>
                    <td>${reportCell}</td>
                </tr>
            `;
        }).join('');
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatCurrency(value) {
        return '$' + Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }
});
</script>
@endpush
