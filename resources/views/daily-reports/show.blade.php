@extends('layouts.tabler')
@section('title', 'Daily Report - ' . \App\Helpers\DateFormatter::toUSShort($dailyReport->report_date))

@section('styles')
<style>
    /* Material UI Card Styling */
    .card-material {
        background: #ffffff;
        border-radius: 4px;
        box-shadow: 0px 2px 1px -1px rgba(0, 0, 0, 0.2), 0px 1px 1px 0px rgba(0, 0, 0, 0.14), 0px 1px 3px 0px rgba(0, 0, 0, 0.12);
        transition: box-shadow 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    
    .card-header-material {
        background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
        color: #fff;
        padding: 1.5rem;
        text-align: center;
    }
    
    .card-title-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.5rem;
        font-weight: 400;
        margin: 0 0 0.5rem 0;
    }
    
    .card-subtitle-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .section-header-material {
        background: #fafafa;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1rem;
        font-weight: 500;
        color: #202124;
    }
    
    .section-body-material {
        padding: 1.5rem;
    }
    
    /* Material UI Table */
    .table-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    
    .table-material thead th {
        font-size: 0.75rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.03333em;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        padding: 1rem;
        text-align: left;
        background: #fafafa;
    }
    
    .table-material tbody td {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        color: rgba(0, 0, 0, 0.87);
    }
    
    .table-material tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.04);
    }
    
    .table-material tbody tr.total-row {
        background: #e3f2fd;
        font-weight: 500;
    }
    
    .number-input {
        text-align: right;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
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
        text-decoration: none;
    }
    
    .btn-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .btn-material-danger {
        background-color: #d32f2f;
        color: #fff;
    }
    
    .btn-material-warning {
        background-color: #f57c00;
        color: #fff;
    }
    
    .btn-material-secondary {
        background-color: #757575;
        color: #fff;
    }
    
    /* Info Cards */
    .info-card-material {
        background: #fafafa;
        border-radius: 4px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(0, 0, 0, 0.12);
    }
    
    .info-card-material.success {
        background: #e8f5e9;
        border-color: #4caf50;
    }
    
    .info-card-material.info {
        background: #e3f2fd;
        border-color: #2196f3;
    }
    
    .info-label {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.6);
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 1.25rem;
        font-weight: 400;
        color: #202124;
    }
    
    /* Side Panel */
    .side-panel-material {
        background: #fafafa;
        border-radius: 4px;
        padding: 1.5rem;
        border: 1px solid rgba(0, 0, 0, 0.12);
    }
    
    .form-group-material {
        margin-bottom: 1rem;
    }
    
    .form-group-material:last-child {
        margin-bottom: 0;
    }
    
    .form-label-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.6);
        text-transform: uppercase;
        letter-spacing: 0.03333em;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .form-control-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        width: 100%;
        padding: 0.75rem;
        border: 1px solid rgba(0, 0, 0, 0.12);
        border-radius: 4px;
        background: #fff;
        color: rgba(0, 0, 0, 0.87);
    }
    
    .form-control-material:read-only {
        background: #f5f5f5;
        color: rgba(0, 0, 0, 0.6);
    }
    
    /* Badge Material */
    .badge-material {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.75rem;
        font-weight: 500;
        padding: 0.25rem 0.75rem;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .card-title-material {
            font-size: 1.25rem;
        }
        
        .section-body-material {
            padding: 1rem;
        }
        
        .table-material thead th,
        .table-material tbody td {
            padding: 0.75rem 0.5rem;
            font-size: 0.8125rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <!-- Action Buttons -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('daily-reports.export-pdf', $dailyReport) }}" class="btn btn-material btn-material-danger" target="_blank">
            <i class="bi bi-file-pdf"></i>
            <span>Export PDF</span>
        </a>
        <a href="{{ route('daily-reports.edit', $dailyReport) }}" class="btn btn-material btn-material-warning">
            <i class="bi bi-pencil"></i>
            <span>Edit Report</span>
        </a>
        <a href="{{ route('daily-reports.index') }}" class="btn btn-material btn-material-secondary">
            <i class="bi bi-arrow-left"></i>
            <span>Back to List</span>
        </a>
    </div>

    <!-- Main Report Card -->
    <div class="card-material">
        <!-- Header Section -->
        <div class="card-header-material">
            <div class="card-title-material">{{ $dailyReport->store->store_info ?? 'N/A' }}</div>
            <div class="card-subtitle-material">
                <div>{{ $dailyReport->store->address ?? 'N/A' }}</div>
                <div>Phone: {{ $dailyReport->store->phone ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Transaction Expenses Section -->
        <div class="section-header-material">
            <i class="bi bi-receipt me-2"></i>Transaction Expenses
        </div>
        <div class="section-body-material">
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    @if($dailyReport->transactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table-material">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;">Company</th>
                                        <th style="width: 35%;">Transaction Type</th>
                                        <th style="width: 25%;">Amount ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyReport->transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->company }}</td>
                                            <td>{{ $transaction->transactionType->name ?? 'N/A' }}</td>
                                            <td class="number-input">${{ number_format($transaction->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="total-row">
                                        <td colspan="2"><strong>Total Transaction Expenses:</strong></td>
                                        <td class="number-input"><strong>${{ number_format($dailyReport->total_paid_outs, 2) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
                            <p class="mb-0">No transactions recorded.</p>
                        </div>
                    @endif
                    <div class="info-card-material success mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="info-label">Total Transaction Expenses:</span>
                            <span class="info-value" style="color: #4caf50;">${{ number_format($dailyReport->total_paid_outs, 2) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-lg-4">
                    <div class="side-panel-material">
                        <div class="form-group-material">
                            <label class="form-label-material">Date</label>
                            <input type="text" class="form-control-material" value="{{ \App\Helpers\DateFormatter::toUSDisplay($dailyReport->report_date) }}" readonly>
                        </div>
                        <div class="form-group-material">
                            <label class="form-label-material">Weather</label>
                            <input type="text" class="form-control-material" value="{{ $dailyReport->weather ?? 'N/A' }}" readonly>
                        </div>
                        <div class="form-group-material">
                            <label class="form-label-material">Holiday/Special Event</label>
                            <input type="text" class="form-control-material" value="{{ $dailyReport->holiday_event ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Types Section -->
        <div class="section-header-material">
            <i class="bi bi-cash-coin me-2"></i>Revenue Income Tracking
        </div>
        <div class="section-body-material">
            <div class="row">
                <div class="col-12">
                    @if($dailyReport->revenues->count() > 0)
                        <div class="table-responsive">
                            <table class="table-material">
                                <thead>
                                    <tr>
                                        <th style="width: 50%;">Revenue Type</th>
                                        <th style="width: 50%;">Amount ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyReport->revenues as $revenue)
                                        <tr>
                                            <td>
                                                <span class="badge-material bg-{{ $revenue->revenueIncomeType->category == 'online' ? 'info' : ($revenue->revenueIncomeType->category == 'cash' ? 'success' : 'secondary') }} text-white">
                                                    {{ $revenue->revenueIncomeType->name }}
                                                </span>
                                            </td>
                                            <td class="number-input">${{ number_format($revenue->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.5rem;"></i>
                            <p class="mb-0">No revenue entries recorded.</p>
                        </div>
                    @endif
                    <div class="row g-3 mt-3">
                        <div class="col-12 col-md-6">
                            <div class="info-card-material success">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="info-label">Total Revenue Income:</span>
                                    <span class="info-value" style="color: #4caf50;">${{ number_format($dailyReport->total_revenue_entries, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="info-card-material info">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="info-label">Online Platform Revenue:</span>
                                    <span class="info-value" style="color: #2196f3;">${{ number_format($dailyReport->online_platform_revenue, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Section -->
        <div class="section-header-material">
            <i class="bi bi-graph-up me-2"></i>Sales Information
        </div>
        <div class="section-body-material">
            <div class="row">
                <div class="col-12 col-md-6">
                    <table class="table-material">
                        <tbody>
                            <tr>
                                <td style="width: 60%;"><strong>Projected Sales</strong></td>
                                <td class="number-input">${{ number_format($dailyReport->projected_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Amount of Cancels</strong></td>
                                <td class="number-input">${{ number_format($dailyReport->amount_of_cancels, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Amount of Voids</strong></td>
                                <td class="number-input">${{ number_format($dailyReport->amount_of_voids, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Number of No Sales</strong></td>
                                <td class="number-input">{{ $dailyReport->number_of_no_sales }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Summary Section -->
        <div class="section-header-material">
            <i class="bi bi-calculator me-2"></i>Financial Summary
        </div>
        <div class="section-body-material">
            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <table class="table-material">
                        <tbody>
                            <tr>
                                <td style="width: 40%;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Total # of Coupons</span>
                                        <span class="number-input">{{ $dailyReport->total_coupons }}</span>
                                    </div>
                                </td>
                                <td style="width: 30%;"><strong>Gross Sales:</strong></td>
                                <td class="number-input">${{ number_format($dailyReport->gross_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><strong>Total Amount of Coupons Received:</strong></span>
                                        <span class="number-input">${{ number_format($dailyReport->coupons_received, 2) }}</span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Adjustments: Overrings/Returns:</strong></td>
                                <td class="number-input">${{ number_format($dailyReport->adjustments_overrings, 2) }}</td>
                            </tr>
                            <tr>
                                <td rowspan="2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Total # of Customers</span>
                                        <span class="number-input">{{ $dailyReport->total_customers }}</span>
                                    </div>
                                </td>
                                <td><strong>Net Sales:</strong></td>
                                <td class="number-input" style="background: #e3f2fd; font-weight: 500;">${{ number_format($dailyReport->net_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Tax:</strong></td>
                                <td class="number-input" style="background: #e3f2fd; font-weight: 500;">${{ number_format($dailyReport->tax, 2) }}</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Average Ticket</span>
                                        <span class="number-input">${{ number_format($dailyReport->average_ticket, 2) }}</span>
                                    </div>
                                </td>
                                <td><strong>Sales (Pre-tax):</strong></td>
                                <td class="number-input" style="background: #e3f2fd; font-weight: 500;">${{ number_format($dailyReport->sales_pre_tax, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="col-12 col-lg-4">
                    <table class="table-material">
                        <tbody>
                            <tr>
                                <td style="width: 60%;"><strong>Net Sales:</strong></td>
                                <td class="number-input" style="background: #e3f2fd; font-weight: 500;">${{ number_format($dailyReport->net_sales, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Total Transaction Expenses:</strong></td>
                                <td class="number-input" style="background: #e3f2fd; font-weight: 500;">${{ number_format($dailyReport->total_paid_outs, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Online Platform Revenue:</strong></td>
                                <td class="number-input" style="background: #e3f2fd; font-weight: 500;">${{ number_format($dailyReport->online_platform_revenue, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Credit Cards:</strong></td>
                                <td class="number-input">${{ number_format($dailyReport->credit_cards, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Cash To Account For:</strong></td>
                                <td class="number-input" style="background: #e3f2fd; font-weight: 500;">${{ number_format($dailyReport->cash_to_account_for, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Actual Deposit:</strong></td>
                                <td class="number-input">${{ number_format($dailyReport->actual_deposit, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Short:</strong></td>
                                <td class="number-input" style="color: {{ $dailyReport->short < 0 ? '#d32f2f' : '#495057' }}; font-weight: 500;">${{ number_format($dailyReport->short, 2) }}</td>
                            </tr>
                            <tr>
                                <td><strong>Over:</strong></td>
                                <td class="number-input" style="color: {{ $dailyReport->over > 0 ? '#4caf50' : '#495057' }}; font-weight: 500;">${{ number_format($dailyReport->over, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
