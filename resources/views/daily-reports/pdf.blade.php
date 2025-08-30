<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report - {{ $dailyReport->report_date->format('M d, Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .report-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-cell {
            display: table-cell;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        
        .info-label {
            font-weight: bold;
            width: 30%;
        }
        
        .section-title {
            background: #f8f9fa;
            padding: 10px 15px;
            font-weight: bold;
            color: #495057;
            margin: 20px 0 10px 0;
            border-left: 4px solid #007bff;
        }
        
        .financial-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .financial-row {
            display: table-row;
        }
        
        .financial-cell {
            display: table-cell;
            padding: 8px;
            border: 1px solid #ddd;
            width: 50%;
        }
        
        .financial-label {
            font-weight: bold;
            background: #f8f9fa;
        }
        
        .financial-value {
            text-align: right;
        }
        
        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .transaction-table th {
            background: #007bff;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        
        .transaction-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .transaction-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .calculated-values {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .calc-grid {
            display: table;
            width: 100%;
        }
        
        .calc-row {
            display: table-row;
        }
        
        .calc-cell {
            display: table-cell;
            padding: 5px 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $dailyReport->store->store_info ?? 'Daily Report' }}</div>
        <div>{{ $dailyReport->store->address ?? '' }}</div>
        @if($dailyReport->store->phone)
            <div>Phone: {{ $dailyReport->store->phone }}</div>
        @endif
    </div>

    <!-- Report Information -->
    <div class="report-info">
        <div class="info-row">
            <div class="info-cell info-label">Report Date:</div>
            <div class="info-cell">{{ $dailyReport->report_date->format('F d, Y') }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Created By:</div>
            <div class="info-cell">{{ $dailyReport->creator->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-cell info-label">Created On:</div>
            <div class="info-cell">{{ $dailyReport->created_at->format('M d, Y h:i A') }}</div>
        </div>
        @if($dailyReport->weather)
        <div class="info-row">
            <div class="info-cell info-label">Weather:</div>
            <div class="info-cell">{{ $dailyReport->weather }}</div>
        </div>
        @endif
        @if($dailyReport->holiday_event)
        <div class="info-row">
            <div class="info-cell info-label">Holiday/Event:</div>
            <div class="info-cell">{{ $dailyReport->holiday_event }}</div>
        </div>
        @endif
    </div>

    <!-- Sales Information -->
    <div class="section-title">Sales Information</div>
    <div class="financial-grid">
        <div class="financial-row">
            <div class="financial-cell financial-label">Projected Sales:</div>
            <div class="financial-cell financial-value">${{ number_format($dailyReport->projected_sales, 2) }}</div>
        </div>
        <div class="financial-row">
            <div class="financial-cell financial-label">Gross Sales:</div>
            <div class="financial-cell financial-value">${{ number_format($dailyReport->gross_sales, 2) }}</div>
        </div>
        <div class="financial-row">
            <div class="financial-cell financial-label">Amount of Cancels:</div>
            <div class="financial-cell financial-value">${{ number_format($dailyReport->amount_of_cancels, 2) }}</div>
        </div>
        <div class="financial-row">
            <div class="financial-cell financial-label">Amount of Voids:</div>
            <div class="financial-cell financial-value">${{ number_format($dailyReport->amount_of_voids, 2) }}</div>
        </div>
        <div class="financial-row">
            <div class="financial-cell financial-label">Coupons Received:</div>
            <div class="financial-cell financial-value">${{ number_format($dailyReport->coupons_received, 2) }}</div>
        </div>
        <div class="financial-row">
            <div class="financial-cell financial-label">Total Customers:</div>
            <div class="financial-cell financial-value">{{ $dailyReport->total_customers }}</div>
        </div>
    </div>

    <!-- Transaction Expenses -->
    @if($dailyReport->transactions->count() > 0)
        <div class="section-title">Transaction Expenses</div>
        <table class="transaction-table">
            <thead>
                <tr>
                    <th>ID</th>
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
                        <td>{{ $transaction->transaction_type }}</td>
                        <td>${{ number_format($transaction->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Calculated Values -->
    <div class="section-title">Financial Summary</div>
    <div class="calculated-values">
        <div class="calc-grid">
            <div class="calc-row">
                <div class="calc-cell"><strong>Net Sales:</strong></div>
                <div class="calc-cell">${{ number_format($dailyReport->net_sales, 2) }}</div>
            </div>
            <div class="calc-row">
                <div class="calc-cell"><strong>Tax:</strong></div>
                <div class="calc-cell">${{ number_format($dailyReport->tax, 2) }}</div>
            </div>
            <div class="calc-row">
                <div class="calc-cell"><strong>Sales (Pre-tax):</strong></div>
                <div class="calc-cell">${{ number_format($dailyReport->sales_pre_tax, 2) }}</div>
            </div>
            <div class="calc-row">
                <div class="calc-cell"><strong>Total Paid Outs:</strong></div>
                <div class="calc-cell">${{ number_format($dailyReport->total_paid_outs, 2) }}</div>
            </div>
            <div class="calc-row">
                <div class="calc-cell"><strong>Credit Cards:</strong></div>
                <div class="calc-cell">${{ number_format($dailyReport->credit_cards, 2) }}</div>
            </div>
            <div class="calc-row">
                <div class="calc-cell"><strong>Cash to Account For:</strong></div>
                <div class="calc-cell">${{ number_format($dailyReport->cash_to_account_for, 2) }}</div>
            </div>
            <div class="calc-row">
                <div class="calc-cell"><strong>Actual Deposit:</strong></div>
                <div class="calc-cell">${{ number_format($dailyReport->actual_deposit, 2) }}</div>
            </div>
            @if($dailyReport->short != 0)
            <div class="calc-row">
                <div class="calc-cell"><strong>Short:</strong></div>
                <div class="calc-cell" style="color: #dc3545;">${{ number_format($dailyReport->short, 2) }}</div>
            </div>
            @endif
            @if($dailyReport->over != 0)
            <div class="calc-row">
                <div class="calc-cell"><strong>Over:</strong></div>
                <div class="calc-cell" style="color: #28a745;">${{ number_format($dailyReport->over, 2) }}</div>
            </div>
            @endif
            <div class="calc-row">
                <div class="calc-cell"><strong>Average Ticket:</strong></div>
                <div class="calc-cell">${{ number_format($dailyReport->average_ticket, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        Generated on {{ date('F d, Y \a\t h:i A') }} | Restaurant Daily Report System
    </div>
</body>
</html>