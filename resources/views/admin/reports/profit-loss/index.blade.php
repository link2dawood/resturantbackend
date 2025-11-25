@extends('layouts.tabler')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">
                Profit & Loss Statement
            </h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">
                Comprehensive financial report
            </p>
        </div>
        <div class="btn-group">
            @can('reports', 'export')
            <a href="{{ route('admin.reports.profit-loss.export.csv', request()->all()) }}" class="btn btn-outline-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Export CSV
            </a>
            <a href="{{ route('admin.reports.profit-loss.export.pdf', request()->all()) }}" class="btn btn-outline-primary" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                Export PDF
            </a>
            @endcan
            <button class="btn btn-outline-secondary" onclick="window.print()">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect width="12" height="8" x="6" y="14" rx="1"/>
                </svg>
                Print
            </button>
            @can('reports', 'export')
            <a href="{{ route('admin.reports.profit-loss.comparison') }}" class="btn btn-outline-secondary">
                Store Comparison
            </a>
            <a href="{{ route('admin.reports.profit-loss.snapshots') }}" class="btn btn-outline-secondary">
                Snapshots
            </a>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.reports.profit-loss.index') }}" method="GET" class="row g-3">
                @canViewAllStores
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
                @else
                <input type="hidden" name="store_id" value="{{ $storeId }}">
                @endcanViewAllStores
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date Preset</label>
                    <select class="form-select" id="datePreset" onchange="applyDatePreset(this.value)">
                        <option value="">Custom</option>
                        <option value="this_month">This Month</option>
                        <option value="last_month">Last Month</option>
                        <option value="this_quarter">This Quarter</option>
                        <option value="this_year">This Year</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Compare With</label>
                    <select class="form-select" name="comparison_period">
                        <option value="">None</option>
                        <option value="previous_period" {{ $comparisonPeriod == 'previous_period' ? 'selected' : '' }}>Previous Period</option>
                        <option value="previous_year" {{ $comparisonPeriod == 'previous_year' ? 'selected' : '' }}>Previous Year</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg> Generate Report
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveSnapshot()">
                        Save Snapshot
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(isset($data['pl']))
    <!-- P&L Report -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" style="font-size: 0.875rem;">
                    <thead style="background-color: var(--google-grey-50, #f8f9fa);">
                        <tr>
                            <th style="width: 40%; font-weight: 600;">Line Item</th>
                            @if($comparisonPeriod)
                            <th class="text-end" style="font-weight: 600;">Current Period</th>
                            <th class="text-end" style="font-weight: 600;">Comparison</th>
                            <th class="text-end" style="font-weight: 600;">Variance</th>
                            <th class="text-end" style="font-weight: 600;">Variance %</th>
                            @else
                            <th class="text-end" style="font-weight: 600;">Amount</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <!-- REVENUE SECTION -->
                        <tr style="background-color: #f8f9fa;">
                            <td colspan="{{ $comparisonPeriod ? 5 : 2 }}" style="font-weight: 600; font-size: 1rem;">REVENUE</td>
                        </tr>
                        @foreach($data['pl']['revenue']['items'] as $item)
                        <tr>
                            <td style="padding-left: 2rem;">{{ $item['name'] }}</td>
                            @if($comparisonPeriod)
                            <td class="text-end">${{ number_format($item['amount'] ?? 0, 2) }}</td>
                            <td class="text-end">${{ number_format($item['comparison_amount'] ?? 0, 2) }}</td>
                            <td class="text-end {{ ($item['variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($item['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($item['variance_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($item['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end">${{ number_format($item['amount'] ?? 0, 2) }}</td>
                            @endif
                        </tr>
                        @endforeach
                        <tr style="background-color: #e8f5e9; font-weight: 600;">
                            <td>TOTAL REVENUE</td>
                            @if($comparisonPeriod)
                            <td class="text-end">${{ number_format($data['pl']['revenue']['total'], 2) }}</td>
                            <td class="text-end">${{ number_format($data['pl']['revenue']['comparison_total'] ?? 0, 2) }}</td>
                            <td class="text-end {{ ($data['pl']['revenue']['variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['revenue']['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['revenue']['variance_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['revenue']['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end">${{ number_format($data['pl']['revenue']['total'], 2) }}</td>
                            @endif
                        </tr>

                        <!-- COGS SECTION -->
                        <tr style="background-color: #fff3e0;">
                            <td colspan="{{ $comparisonPeriod ? 5 : 2 }}" style="font-weight: 600; font-size: 1rem;">COST OF GOODS SOLD (COGS)</td>
                        </tr>
                        @foreach($data['pl']['cogs']['items'] as $item)
                        <tr>
                            <td style="padding-left: 2rem;">{{ $item['name'] }}</td>
                            @if($comparisonPeriod)
                            <td class="text-end text-danger">(${{ number_format($item['amount'] ?? 0, 2) }})</td>
                            <td class="text-end text-danger">(${{ number_format($item['comparison_amount'] ?? 0, 2) }})</td>
                            <td class="text-end {{ ($item['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($item['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($item['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($item['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-danger">(${{ number_format($item['amount'] ?? 0, 2) }})</td>
                            @endif
                        </tr>
                        @endforeach
                        <tr style="background-color: #ffe0b2; font-weight: 600;">
                            <td>TOTAL COGS</td>
                            @if($comparisonPeriod)
                            <td class="text-end text-danger">(${{ number_format($data['pl']['cogs']['total'], 2) }})</td>
                            <td class="text-end text-danger">(${{ number_format($data['pl']['cogs']['comparison_total'] ?? 0, 2) }})</td>
                            <td class="text-end {{ ($data['pl']['cogs']['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['cogs']['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['cogs']['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['cogs']['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-danger">(${{ number_format($data['pl']['cogs']['total'], 2) }})</td>
                            @endif
                        </tr>

                        <!-- GROSS PROFIT -->
                        <tr style="background-color: #e8f5e9; font-weight: 600; font-size: 1.05rem;">
                            <td>GROSS PROFIT</td>
                            @if($comparisonPeriod)
                            <td class="text-end text-success">${{ number_format($data['pl']['gross_profit'], 2) }}</td>
                            <td class="text-end text-success">${{ number_format($data['pl']['comparison_gross_profit'] ?? 0, 2) }}</td>
                            <td class="text-end {{ ($data['pl']['gross_profit_variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['gross_profit_variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['gross_profit_variance_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['gross_profit_variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-success">${{ number_format($data['pl']['gross_profit'], 2) }}</td>
                            @endif
                        </tr>
                        <tr>
                            <td style="padding-left: 2rem;">Gross Margin</td>
                            @if($comparisonPeriod)
                            <td class="text-end">{{ number_format($data['pl']['gross_margin'], 2) }}%</td>
                            <td class="text-end">{{ number_format($data['pl']['comparison_gross_margin'] ?? 0, 2) }}%</td>
                            <td colspan="2"></td>
                            @else
                            <td class="text-end">{{ number_format($data['pl']['gross_margin'], 2) }}%</td>
                            @endif
                        </tr>

                        <!-- OPERATING EXPENSES -->
                        <tr style="background-color: #fce4ec;">
                            <td colspan="{{ $comparisonPeriod ? 5 : 2 }}" style="font-weight: 600; font-size: 1rem;">OPERATING EXPENSES</td>
                        </tr>
                        @foreach($data['pl']['operating_expenses']['items'] as $item)
                        <tr>
                            <td style="padding-left: {{ isset($item['items']) ? '1rem' : '2rem' }};">
                                @if(isset($item['items']))
                                    <strong>{{ $item['name'] }}</strong>
                                    @foreach($item['items'] as $subItem)
                                    <div style="padding-left: 1rem; margin-top: 0.25rem;">
                                        {{ $subItem['name'] }}
                                        @if($subItem['coa_id'])
                                        <a href="{{ route('admin.reports.profit-loss.drill-down', ['coa_id' => $subItem['coa_id'], 'start_date' => $startDate, 'end_date' => $endDate, 'store_id' => $storeId]) }}" 
                                           class="text-decoration-none" title="View transactions">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </a>
                                        @endif
                                    </div>
                                    @endforeach
                                @else
                                    {{ $item['name'] }}
                                    @if($item['coa_id'])
                                    <a href="{{ route('admin.reports.profit-loss.drill-down', ['coa_id' => $item['coa_id'], 'start_date' => $startDate, 'end_date' => $endDate, 'store_id' => $storeId]) }}" 
                                       class="text-decoration-none" title="View transactions">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </a>
                                    @endif
                                @endif
                            </td>
                            @if($comparisonPeriod)
                            <td class="text-end text-danger">
                                @if(isset($item['items']))
                                    (${{ number_format($item['total'], 2) }})
                                @else
                                    (${{ number_format($item['amount'] ?? 0, 2) }})
                                @endif
                            </td>
                            <td class="text-end text-danger">(${{ number_format($item['comparison_amount'] ?? 0, 2) }})</td>
                            <td class="text-end {{ ($item['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($item['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($item['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($item['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-danger">
                                @if(isset($item['items']))
                                    (${{ number_format($item['total'], 2) }})
                                @else
                                    (${{ number_format($item['amount'] ?? 0, 2) }})
                                @endif
                            </td>
                            @endif
                        </tr>
                        @endforeach
                        <tr style="background-color: #f8bbd0; font-weight: 600;">
                            <td>TOTAL OPERATING EXPENSES</td>
                            @if($comparisonPeriod)
                            <td class="text-end text-danger">(${{ number_format($data['pl']['operating_expenses']['total'], 2) }})</td>
                            <td class="text-end text-danger">(${{ number_format($data['pl']['operating_expenses']['comparison_total'] ?? 0, 2) }})</td>
                            <td class="text-end {{ ($data['pl']['operating_expenses']['variance'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['operating_expenses']['variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['operating_expenses']['variance_percent'] ?? 0) <= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['operating_expenses']['variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end text-danger">(${{ number_format($data['pl']['operating_expenses']['total'], 2) }})</td>
                            @endif
                        </tr>

                        <!-- NET PROFIT -->
                        <tr style="background-color: #c8e6c9; font-weight: 700; font-size: 1.1rem;">
                            <td>NET PROFIT</td>
                            @if($comparisonPeriod)
                            <td class="text-end {{ $data['pl']['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['net_profit'], 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['comparison_net_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['comparison_net_profit'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['net_profit_variance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['net_profit_variance'] ?? 0, 2) }}
                            </td>
                            <td class="text-end {{ ($data['pl']['net_profit_variance_percent'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($data['pl']['net_profit_variance_percent'] ?? 0, 2) }}%
                            </td>
                            @else
                            <td class="text-end {{ $data['pl']['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($data['pl']['net_profit'], 2) }}
                            </td>
                            @endif
                        </tr>
                        <tr>
                            <td style="padding-left: 2rem;">Net Margin</td>
                            @if($comparisonPeriod)
                            <td class="text-end">{{ number_format($data['pl']['net_margin'], 2) }}%</td>
                            <td class="text-end">{{ number_format($data['pl']['comparison_net_margin'] ?? 0, 2) }}%</td>
                            <td colspan="2"></td>
                            @else
                            <td class="text-end">{{ number_format($data['pl']['net_margin'], 2) }}%</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body text-center py-5">
            <p class="text-muted">Select date range and click "Generate Report" to view P&L statement.</p>
        </div>
    </div>
    @endif
</div>

<!-- Save Snapshot Modal -->
<div class="modal fade" id="snapshotModal" tabindex="-1" aria-labelledby="snapshotModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="snapshotModalLabel">Save P&L Snapshot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="snapshotForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="snapshotName" class="form-label">Snapshot Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="snapshotName" name="name" required>
                        <small class="text-muted">e.g., "Q1 2024", "January 2024"</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function applyDatePreset(preset) {
    const today = new Date();
    let start, end;
    
    switch(preset) {
        case 'this_month':
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'last_month':
            start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            end = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'this_quarter':
            const quarter = Math.floor(today.getMonth() / 3);
            start = new Date(today.getFullYear(), quarter * 3, 1);
            end = new Date(today.getFullYear(), (quarter + 1) * 3, 0);
            break;
        case 'this_year':
            start = new Date(today.getFullYear(), 0, 1);
            end = new Date(today.getFullYear(), 11, 31);
            break;
        default:
            return;
    }
    
    document.querySelector('input[name="start_date"]').value = start.toISOString().split('T')[0];
    document.querySelector('input[name="end_date"]').value = end.toISOString().split('T')[0];
}

function saveSnapshot() {
    @if(isset($data['pl']))
    new bootstrap.Modal(document.getElementById('snapshotModal')).show();
    @else
    alert('Please generate a report first before saving a snapshot.');
    @endif
}

document.getElementById('snapshotForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('snapshotName').value,
        store_id: document.querySelector('select[name="store_id"]').value || null,
        start_date: document.querySelector('input[name="start_date"]').value,
        end_date: document.querySelector('input[name="end_date"]').value,
    };
    
    fetch('/api/reports/pl/snapshot', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        credentials: 'same-origin',
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.message) {
            alert('Snapshot saved successfully!');
            bootstrap.Modal.getInstance(document.getElementById('snapshotModal')).hide();
        } else if (result.error) {
            alert('Error: ' + result.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving snapshot');
    });
});
</script>
@endpush

