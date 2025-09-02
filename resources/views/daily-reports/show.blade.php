@extends('layouts.tabler')
@section('title', 'Daily Report - ' . $dailyReport->report_date->format('M d, Y'))
@section('content')

<style>
    .report-view {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .report-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .company-name {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .report-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        padding: 0 20px;
    }
    
    .section-title {
        background: #f8f9fa;
        padding: 15px 20px;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
        color: #495057;
        font-size: 1.1rem;
    }
    
    .content-section {
        padding: 20px;
    }
    
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }
    
    .info-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #007bff;
    }
    
    .info-card h5 {
        color: #495057;
        margin-bottom: 15px;
        font-weight: 600;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #dee2e6;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-item .label {
        font-weight: 500;
        color: #6c757d;
    }
    
    .info-item .value {
        font-weight: 600;
        color: #495057;
    }
    
    .transaction-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    
    .transaction-table th {
        background: #007bff;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 600;
    }
    
    .transaction-table td {
        padding: 12px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .transaction-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .calculated-values {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .calculated-values h5 {
        color: #1976d2;
        margin-bottom: 20px;
        font-weight: 600;
    }
    
    .calc-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .calc-item {
        background: white;
        padding: 15px;
        border-radius: 6px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .calc-item .label {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .calc-item .value {
        font-size: 1.2rem;
        font-weight: bold;
        color: #495057;
    }
    
    .print-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
    }
    
    @media (max-width: 768px) {
        .info-grid,
        .calc-grid {
            grid-template-columns: 1fr;
        }
        
        .report-meta {
            flex-direction: column;
            gap: 10px;
        }
        
        .company-name {
            font-size: 1.5rem;
        }
    }
    
    @media print {
        .page-header,
        .print-btn {
            display: none !important;
        }
    }
</style>
<div class="container-xl mt-5">
    <div class="page-header">
        <div class="page-title">
            <h1>Daily Report - {{ $dailyReport->report_date->format('M d, Y') }}</h1>
        </div>
        <div class="page-actions">
        <a href="{{ route('daily-reports.edit', $dailyReport) }}" class="btn btn-warning">✏️ Edit Report</a>
        <a href="{{ route('daily-reports.export-pdf', $dailyReport) }}" class="btn btn-success">📄 Export PDF</a>
        <button onclick="window.print()" class="print-btn">🖨️ Print Report</button>
        <a href="{{ route('daily-reports.index') }}" class="btn btn-secondary">← Back to List</a>
    </div>
</div>

<div class="report-view mt-5">
    <!-- Header Section -->
    <div class="report-header">
        <div class="company-name">{{ $dailyReport->restaurant_name }}</div>
        @if($dailyReport->address)
            <div>{{ $dailyReport->address }}</div>
        @endif
        @if($dailyReport->phone)
            <div>Phone: {{ $dailyReport->phone }}</div>
        @endif
    </div>
    
    <div class="report-meta">
        <div>
            <strong>Report Date:</strong> {{ $dailyReport->report_date->format('F d, Y') }}
        </div>
        <div>
            <strong>Created by:</strong> {{ $dailyReport->creator->name }}
        </div>
        <div>
            <strong>Created:</strong> {{ $dailyReport->created_at->format('M d, Y h:i A') }}
        </div>
    </div>

    <!-- Environmental Info -->
    <div class="section-title">Report Information</div>
    <div class="content-section">
        <div class="info-grid">
            <div class="info-card">
                <h5>Environmental Data</h5>
                <div class="info-item">
                    <span class="label">Weather:</span>
                    <span class="value">{{ $dailyReport->weather ?? 'Not specified' }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Holiday/Event:</span>
                    <span class="value">{{ $dailyReport->holiday_event ?? 'None' }}</span>
                </div>
            </div>
            
            <div class="info-card">
                <h5>Basic Sales Data</h5>
                <div class="info-item">
                    <span class="label">Projected Sales:</span>
                    <span class="value">${{ number_format($dailyReport->projected_sales, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Gross Sales:</span>
                    <span class="value">${{ number_format($dailyReport->gross_sales, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Total Customers:</span>
                    <span class="value">{{ $dailyReport->total_customers }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions -->
    @if($dailyReport->transactions->count() > 0)
        <div class="section-title">Transaction Expenses</div>
        <div class="content-section">
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Company</th>
                        <th>Type</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dailyReport->transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_id }}</td>
                            <td>{{ $transaction->company }}</td>
                            <td>{{ $transaction->transactionType->description_name ?? 'N/A' }}</td>
                            <td>${{ number_format($transaction->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <!-- Revenue Income Types -->
    @if($dailyReport->revenues->count() > 0)
        <div class="section-title">Revenue Income Tracking</div>
        <div class="content-section">
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Revenue Type</th>
                        <th>Amount</th>
                        <th>Notes</th>
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
                            <td>${{ number_format($revenue->amount, 2) }}</td>
                            <td>{{ $revenue->notes ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background-color: #f8f9fa; font-weight: 600;">
                        <td>Total Revenue Entries</td>
                        <td>${{ number_format($dailyReport->total_revenue_entries, 2) }}</td>
                        <td>-</td>
                    </tr>
                    <tr style="background-color: #e9ecef; font-weight: 600;">
                        <td>Online Platform Revenue</td>
                        <td>${{ number_format($dailyReport->online_platform_revenue, 2) }}</td>
                        <td>-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <!-- Calculated Values -->
    <div class="section-title">Financial Summary</div>
    <div class="content-section">
        <div class="calculated-values">
            <h5>Calculated Financial Metrics</h5>
            <div class="calc-grid">
                <div class="calc-item">
                    <div class="label">Total Paid Outs</div>
                    <div class="value">${{ number_format($dailyReport->total_paid_outs, 2) }}</div>
                </div>
                <div class="calc-item">
                    <div class="label">Net Sales</div>
                    <div class="value">${{ number_format($dailyReport->net_sales, 2) }}</div>
                </div>
                <div class="calc-item">
                    <div class="label">Tax</div>
                    <div class="value">${{ number_format($dailyReport->tax, 2) }}</div>
                </div>
                <div class="calc-item">
                    <div class="label">Sales (Pre-tax)</div>
                    <div class="value">${{ number_format($dailyReport->sales_pre_tax, 2) }}</div>
                </div>
                <div class="calc-item">
                    <div class="label">Cash to Account For</div>
                    <div class="value">${{ number_format($dailyReport->cash_to_account_for, 2) }}</div>
                </div>
                <div class="calc-item">
                    <div class="label">Average Ticket</div>
                    <div class="value">${{ number_format($dailyReport->average_ticket, 2) }}</div>
                </div>
                @if($dailyReport->short != 0)
                    <div class="calc-item" style="border-left: 4px solid #dc3545;">
                        <div class="label">Short</div>
                        <div class="value" style="color: #dc3545;">${{ number_format($dailyReport->short, 2) }}</div>
                    </div>
                @endif
                @if($dailyReport->over != 0)
                    <div class="calc-item" style="border-left: 4px solid #28a745;">
                        <div class="label">Over</div>
                        <div class="value" style="color: #28a745;">${{ number_format($dailyReport->over, 2) }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <h5>Adjustments & Deductions</h5>
                <div class="info-item">
                    <span class="label">Amount of Cancels:</span>
                    <span class="value">${{ number_format($dailyReport->amount_of_cancels, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Amount of Voids:</span>
                    <span class="value">${{ number_format($dailyReport->amount_of_voids, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Coupons Received:</span>
                    <span class="value">${{ number_format($dailyReport->coupons_received, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Adjustments/Overrings:</span>
                    <span class="value">${{ number_format($dailyReport->adjustments_overrings, 2) }}</span>
                </div>
            </div>
            
            <div class="info-card">
                <h5>Payment Summary</h5>
                <div class="info-item">
                    <span class="label">Credit Cards:</span>
                    <span class="value">${{ number_format($dailyReport->credit_cards, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Actual Deposit:</span>
                    <span class="value">${{ number_format($dailyReport->actual_deposit, 2) }}</span>
                </div>
                <div class="info-item">
                    <span class="label">Number of No Sales:</span>
                    <span class="value">{{ $dailyReport->number_of_no_sales }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection