@extends('layouts.tabler')
@section('title', 'Daily Report - ' . \App\Helpers\DateFormatter::toUSShort($dailyReport->report_date))
@section('content')

<style>
    .sales-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* force equal widths */
}

.sales-table td {
    width: 33.33%;   /* 3 equal columns */
    padding: 8px;
    vertical-align: middle;
}
    .report-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .report-header {
        background: #f8f9fa;
        color: #202124;
        padding: 12px 15px;
        text-align: center;
        border: 1px solid #e0e0e0;
    }
    
    .company-name {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .company-info {
        opacity: 0.9;
        font-size: 0.85rem;
    }
    
    .section-title {
        background: #f8f9fa;
        padding: 10px 15px;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
        color: #495057;
        font-size: 1rem;
    }
    
    .form-section {
        padding: 12px 15px;
    }
    
    .transaction-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }
    
    .transaction-table th {
        background: #f8f9fa;
        padding: 8px 6px;
        border: 1px solid #dee2e6;
        font-weight: 600;
        text-align: center;
        font-size: 0.875rem;
    }
    
    .transaction-table td {
        border: 1px solid #dee2e6;
        padding: 6px;
    }
    
    .form-input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 8px;
        font-size: 14px;
    }
    
    .readonly-input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 8px;
        font-size: 14px;
        color: #495057;
    }
    
    .number-input {
        text-align: right;
    }
    
    /* Remove number input spinners */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    
    input[type="number"] {
        -moz-appearance: textfield;
    }
    
    /* Negative amounts in red */
    .number-input.negative,
    .calculated-field.negative {
        color: #dc3545 !important;
    }
    
    .total-row {
        background: #e7f3ff;
        font-weight: 600;
    }
    
    .side-panel {
        background: #f8f9fa;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 12px;
    }
    
    .side-panel .form-group {
        margin-bottom: 10px;
    }
    
    .side-panel label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 4px;
        display: block;
        font-size: 0.875rem;
    }
    
    .side-panel input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        background: white;
    }
    
    .category-labels {
        background: #e9ecef;
        padding: 10px 12px;
        border-radius: 8px;
        margin-top: 12px;
    }
    
    .category-labels .category {
        display: inline-block;
        background: #6c757d;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        margin: 3px;
        font-weight: 500;
    }
    
    .sales-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 12px;
    }
    
    .sales-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    
    .sales-table td {
        padding: 8px 10px;
        border: 1px solid #dee2e6;
    }
    
    .sales-table td:first-child {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        width: 40%;
    }
    
    .sales-table td:nth-child(2) {
        width: 25%;
    }
    
    .sales-table td:last-child {
        width: 35%;
    }
    
    .sales-table td:empty {
        background: transparent;
    }
    
    .calculated-field {
        background: #e7f3ff !important;
        color: #004085;
        font-weight: 600;
    }
    
    .export-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .export-btn {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .export-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .export-btn-pdf {
        background: #dc3545;
        color: white;
    }
    
    .export-btn-csv {
        background: #28a745;
        color: white;
    }
    
    .export-btn-edit {
        background: #ffc107;
        color: #000;
    }
    
    .export-btn-back {
        background: #6c757d;
        color: white;
    }
</style>

<div class="container-fluid mt-4" style="max-width: 95%; margin-left: auto; margin-right: auto;">
    <div class="export-buttons">
        <a href="{{ route('daily-reports.export-pdf', $dailyReport) }}" class="export-btn export-btn-pdf" target="_blank">
            📄 Export PDF
        </a>
        <a href="{{ route('daily-reports.edit', $dailyReport) }}" class="export-btn export-btn-edit">
            ✏️ Edit Report
        </a>
        <a href="{{ route('daily-reports.index') }}" class="export-btn export-btn-back">
            ← Back to List
        </a>
    </div>

    <div class="report-container">
        <!-- Header Section -->
        <div class="report-header">
            <div class="company-name">{{ $dailyReport->store->store_info ?? 'N/A' }}</div>
            <div class="company-info">
                <div>{{ $dailyReport->store->address ?? 'N/A' }}</div>
                <div>Phone: {{ $dailyReport->store->phone ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Transaction Expenses Section -->
        <div class="section-title">Transaction Expenses</div>
        <div class="form-section">
            <div class="row">
                <div class="col-lg-8">
                    @if($dailyReport->transactions->count() > 0)
                        <table class="transaction-table">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Transaction ID</th>
                                    <th style="width: 30%;">Company</th>
                                    <th style="width: 25%;">Transaction Type</th>
                                    <th style="width: 20%;">Amount ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyReport->transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->transaction_id }}</td>
                                        <td>{{ $transaction->company }}</td>
                                        <td>{{ $transaction->transactionType->name ?? 'N/A' }}</td>
                                        <td class="number-input{{ $transaction->amount < 0 ? ' negative' : '' }}">${{ number_format($transaction->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="total-row">
                                    <td colspan="3"><strong>Total Transaction Expenses:</strong></td>
                                    <td class="number-input{{ $dailyReport->total_paid_outs < 0 ? ' negative' : '' }}"><strong>${{ number_format($dailyReport->total_paid_outs, 2) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">No transactions recorded.</p>
                    @endif
                    <div style="margin-top: 8px; padding: 8px; background: #fff3cd; border-radius: 4px; border: 1px solid #ffeaa7;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; color: #856404;">Total Transaction Expenses:</span>
                            <span style="font-weight: 600; color: {{ $dailyReport->total_paid_outs < 0 ? '#dc3545' : '#856404' }}; font-size: 1.1rem;">${{ number_format($dailyReport->total_paid_outs, 2) }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="side-panel">
                        <div class="form-group">
                            <label>Date:</label>
                            <input type="text" class="form-control" value="{{ \App\Helpers\DateFormatter::toUSDisplay($dailyReport->report_date) }}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Weather:</label>
                            <input type="text" class="form-control" value="{{ $dailyReport->weather ?? 'N/A' }}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Holiday/Special Event:</label>
                            <input type="text" class="form-control" value="{{ $dailyReport->holiday_event ?? 'N/A' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Types Section -->
        <div class="section-title">Revenue Income Tracking</div>
        <div class="form-section">
            <div class="row">
                <div class="col-lg-12">
                    @if($dailyReport->revenues->count() > 0)
                        <table class="transaction-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Revenue Type</th>
                                    <th style="width: 20%;">Amount ($)</th>
                                    <th style="width: 50%;">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyReport->revenues as $revenue)
                                    <tr>
                                        <td>
                                            <span class="badge badge-outline text-{{ $revenue->revenueIncomeType->category == 'online' ? 'info' : ($revenue->revenueIncomeType->category == 'cash' ? 'success' : 'secondary') }}">
                                                {{ $revenue->revenueIncomeType->name }}
                                            </span>
                                        </td>
                                        <td class="number-input{{ $revenue->amount < 0 ? ' negative' : '' }}">${{ number_format($revenue->amount, 2) }}</td>
                                        <td>{{ $revenue->notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">No revenue entries recorded.</p>
                    @endif
                    <div style="margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; color: #495057;">Total Revenue Income:</span>
                            <span style="font-weight: 600; color: {{ $dailyReport->total_revenue_entries < 0 ? '#dc3545' : '#28a745' }}; font-size: 1.1rem;">${{ number_format($dailyReport->total_revenue_entries, 2) }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                            <span style="font-weight: 600; color: #495057;">Online Platform Revenue:</span>
                            <span style="font-weight: 600; color: {{ $dailyReport->online_platform_revenue < 0 ? '#dc3545' : '#17a2b8' }}; font-size: 1.1rem;">${{ number_format($dailyReport->online_platform_revenue, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Section -->
        <div class="section-title">Sales Information</div>
        <div class="form-section">
            <div class="row">
                <div class="col-md-6">
                    <table class="sales-table">
                        <tr>
                            <td>Projected Sales</td>
                            <td class="number-input{{ $dailyReport->projected_sales < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->projected_sales, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Amount of Cancels</td>
                            <td class="number-input{{ $dailyReport->amount_of_cancels < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->amount_of_cancels, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Amount of Voids</td>
                            <td class="number-input{{ $dailyReport->amount_of_voids < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->amount_of_voids, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Number of No Sales</td>
                            <td class="number-input">{{ $dailyReport->number_of_no_sales }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Financial Summary Section -->
        <div class="section-title">Financial Summary</div>
        <div class="form-section">
            <div class="row">
                <div class="col-8">
                    <table class="sales-table">
                        <tr>
                            <td><strong>Gross Sales:</strong></td>
                            <td></td>
                            <td class="number-input{{ $dailyReport->gross_sales < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->gross_sales, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Amount of Coupons Received:</strong></td>
                            <td></td>
                            <td class="number-input{{ $dailyReport->coupons_received < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->coupons_received, 2) }}</td>
                        </tr>
                        <tr>
                            <td>
                                <div style="display:flex;justify-content: space-between;align-items: center;">
                                    <span>Total # of Coupons</span>
                                    <span style="width:30%;" class="number-input">{{ $dailyReport->total_coupons }}</span>
                                </div>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td><strong>Adjustments: Overrings/Returns:</strong></td>
                            <td></td>
                            <td class="number-input{{ $dailyReport->adjustments_overrings < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->adjustments_overrings, 2) }}</td>
                        </tr>
                        <tr>
                            <td rowspan="2">
                                <div style="display:flex;justify-content: space-between;align-items: center;">
                                    <span>Total # of Customers</span>
                                    <span style="width:30%;" class="number-input">{{ $dailyReport->total_customers }}</span>
                                </div>
                            </td>
                            <td><strong>Net Sales:</strong></td>
                            <td id="netSales" class="calculated-field number-input{{ $dailyReport->net_sales < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->net_sales, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tax:</strong></td>
                            <td id="tax" class="calculated-field number-input{{ $dailyReport->tax < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->tax, 2) }}</td>
                        </tr>
                        <tr>
                            <td>
                                <div style="display:flex;justify-content: space-between;align-items: center;">
                                    <span>Average Ticket</span>
                                    <span style="width:30%;" class="number-input">${{ number_format($dailyReport->average_ticket, 2) }}</span>
                                </div>
                            </td>
                            <td><strong>Sales (Pre-tax):</strong></td>
                            <td id="salesPreTax" class="calculated-field number-input{{ $dailyReport->sales_pre_tax < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->sales_pre_tax, 2) }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-4">
                    <table class="sales-table">
                        <tr>
                            <td><strong>Net Sales:</strong></td>
                            <td id="netSales2" class="calculated-field number-input{{ $dailyReport->net_sales < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->net_sales, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Transaction Expenses:</strong></td>
                            <td id="totalPaidOuts2" class="calculated-field number-input{{ $dailyReport->total_paid_outs < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->total_paid_outs, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Online Platform Revenue:</strong></td>
                            <td id="onlineRevenue2" class="calculated-field number-input{{ $dailyReport->online_platform_revenue < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->online_platform_revenue, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Credit Cards:</strong></td>
                            <td class="number-input{{ $dailyReport->credit_cards < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->credit_cards, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Cash To Account For:</strong></td>
                            <td id="cashToAccountFor" class="calculated-field number-input{{ $dailyReport->cash_to_account_for < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->cash_to_account_for, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Actual Deposit:</strong></td>
                            <td class="number-input{{ $dailyReport->actual_deposit < 0 ? ' negative' : '' }}">${{ number_format($dailyReport->actual_deposit, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Short:</strong></td>
                            <td id="short" class="calculated-field number-input" style="color: {{ $dailyReport->short < 0 ? '#dc3545' : '#495057' }};">${{ number_format($dailyReport->short, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Over:</strong></td>
                            <td id="over" class="calculated-field number-input" style="color: {{ $dailyReport->over > 0 ? '#28a745' : '#495057' }};">${{ number_format($dailyReport->over, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
