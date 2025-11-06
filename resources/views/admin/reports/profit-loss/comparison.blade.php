@extends('layouts.tabler')

@section('title', 'Store Comparison')

@section('content')
<div class="container-xl mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">Multi-Store Comparison</h1>
            <p class="text-muted mb-0">Compare P&L across stores</p>
        </div>
        <a href="{{ route('admin.reports.profit-loss.index') }}" class="btn btn-outline-secondary">Back to P&L</a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.reports.profit-loss.comparison') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Stores (Select Multiple)</label>
                    <select class="form-select" name="store_ids[]" multiple size="5">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ in_array($store->id, $storeIds ?? []) ? 'selected' : '' }}>
                                {{ $store->store_info }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Metric</label>
                    <select class="form-select" name="metric">
                        <option value="revenue" {{ $metric == 'revenue' ? 'selected' : '' }}>Revenue</option>
                        <option value="profit" {{ $metric == 'profit' ? 'selected' : '' }}>Profit</option>
                        <option value="margin" {{ $metric == 'margin' ? 'selected' : '' }}>Margin</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100">Compare</button>
                </div>
            </form>
        </div>
    </div>

    @if(isset($comparisonData['comparison']))
    <!-- Comparison Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Store Ranking by {{ ucfirst($comparisonData['metric']) }}</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                        <tr>
                            <th>Rank</th>
                            <th>Store</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Net Profit</th>
                            <th class="text-end">Net Margin</th>
                            <th class="text-end">{{ ucfirst($comparisonData['metric']) }} Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparisonData['comparison'] as $index => $store)
                        <tr>
                            <td>
                                @if($index == 0)
                                    <span class="badge bg-success">#1</span>
                                @elseif($index == count($comparisonData['comparison']) - 1)
                                    <span class="badge bg-warning">#{{ $index + 1 }}</span>
                                @else
                                    <span class="badge bg-secondary">#{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td><strong>{{ $store['store_name'] }}</strong></td>
                            <td class="text-end">${{ number_format($store['revenue'], 2) }}</td>
                            <td class="text-end {{ $store['profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($store['profit'], 2) }}
                            </td>
                            <td class="text-end">{{ number_format($store['margin'], 2) }}%</td>
                            <td class="text-end">
                                @if($comparisonData['metric'] == 'revenue')
                                    ${{ number_format($store['revenue'], 2) }}
                                @elseif($comparisonData['metric'] == 'profit')
                                    ${{ number_format($store['profit'], 2) }}
                                @else
                                    {{ number_format($store['margin'], 2) }}%
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body text-center py-5">
            <p class="text-muted">Select stores and date range to view comparison.</p>
        </div>
    </div>
    @endif
</div>
@endsection




