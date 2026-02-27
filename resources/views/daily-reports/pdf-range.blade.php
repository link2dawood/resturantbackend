<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Reports - {{ $startDate }} to {{ $endDate }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #000;
            padding: 20px;
            background: #fff;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 3px double #000;
        }
        
        .header-table td {
            padding: 10px;
            vertical-align: top;
            border: 3px double #000;
        }
        
        .logo-cell {
            width: 30%;
        }
        
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 10px;
        }
        
        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .store-info-cell {
            width: 70%;
            text-align: center;
        }
        
        .store-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .store-address {
            font-size: 11px;
            margin-bottom: 3px;
        }
        
        .store-phone {
            font-size: 11px;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .main-table td {
            vertical-align: top;
            border: 3px double #000;
            padding: 10px;
        }
        
        .left-cell {
            width: 60%;
        }
        
        .right-cell {
            width: 40%;
        }
        
        .section-table {
            width: 100%;
            border-collapse: collapse;
            border: 3px double #000;
            margin-bottom: 15px;
        }
        
        .section-table td {
            padding: 8px;
            border: 3px double #000;
            font-size: 11px;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 13px;
            background-color: #f0f0f0;
            border-bottom: 3px double #000;
        }
        
        .transactions-table {
            min-height: 80px;
        }
        
        .transactions-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .transactions-value {
            font-size: 14px;
            font-weight: bold;
        }
        
        .sales-table {
            width: 100%;
            border-collapse: collapse;
            border: 3px double #000;
        }
        
        .sales-table td {
            padding: 6px;
            border: 3px double #000;
            font-size: 11px;
        }
        
        .sales-left-col {
            width: 50%;
            vertical-align: top;
        }
        
        .sales-right-col {
            width: 50%;
            vertical-align: top;
        }
        
        .sales-item {
            margin-bottom: 8px;
        }
        
        .sales-label {
            display: inline-block;
            width: 60%;
        }
        
        .sales-value {
            display: inline-block;
            width: 38%;
            text-align: right;
            font-weight: bold;
        }
        
        .sales-summary {
            margin-top: 15px;
            border-top: 3px double #000;
            padding-top: 10px;
        }
        
        .summary-item {
            margin-bottom: 6px;
            font-size: 11px;
        }
        
        .summary-label {
            display: inline-block;
            width: 65%;
            font-weight: bold;
        }
        
        .summary-value {
            display: inline-block;
            width: 33%;
            text-align: right;
            font-weight: bold;
        }
        
        .weather-table {
            width: 100%;
            border-collapse: collapse;
            border: 3px double #000;
            min-height: 100px;
        }
        
        .weather-table td {
            padding: 8px;
            border: 3px double #000;
            font-size: 11px;
        }
        
        .weather-label {
            font-weight: bold;
            width: 40%;
        }
        
        .weather-value {
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding: 2px 0;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 3px double #000;
            min-height: 200px;
        }
        
        .summary-table td {
            padding: 8px;
            border: 3px double #000;
            font-size: 11px;
        }
        
        .summary-label-cell {
            width: 60%;
            font-weight: bold;
        }
        
        .summary-value-cell {
            width: 40%;
            text-align: right;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-bold {
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    @foreach($reports as $index => $dailyReport)
        @if($index > 0)
            <div class="page-break"></div>
        @endif
        
        <!-- Header with Logo and Store Info -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="https://stores.fannsphilly.com/images/logo.jpg" alt="Logo" class="logo" />
                    <div class="report-title">Daily Report - {{ $dailyReport->report_date->format('m/d/Y') }}</div>
                </td>
                <td class="store-info-cell text-center">
                    <div class="store-name">{{ $dailyReport->store->store_info ?? 'Store Name' }}</div>
                    <div class="store-address">{{ $dailyReport->store->address ?? '' }}</div>
                    <div class="store-phone">Phone: {{ $dailyReport->store->phone ?? '' }}</div>
                </td>
            </tr>
        </table>

        <!-- Main Content Container -->
        <table class="main-table">
            <tr>
                <!-- Left Section -->
                <td class="left-cell">
                    <!-- Transactions Section (Top Left) -->
                    <table class="section-table transactions-table">
                        <tr>
                            <td class="section-title" colspan="2">Transactions</td>
                        </tr>
                        <tr>
                            <td class="transactions-label">Total Payouts:</td>
                            <td class="text-right text-bold">${{ number_format($dailyReport->total_paid_outs ?? 0, 2) }}</td>
                        </tr>
                    </table>

                    <!-- Sales Section (Middle Left) -->
                    <table class="section-table">
                        <tr>
                            <td class="section-title" colspan="2">Sales</td>
                        </tr>
                        <tr>
                            <td class="sales-left-col">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">
                                            <div class="sales-item">
                                                <span class="sales-label">Total # of No Sales:</span>
                                                <span class="sales-value">{{ $dailyReport->number_of_no_sales ?? 0 }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">
                                            <div class="sales-item">
                                                <span class="sales-label">Total # of Coupons:</span>
                                                <span class="sales-value">{{ $dailyReport->total_coupons ?? 0 }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">
                                            <div class="sales-item">
                                                <span class="sales-label">Total # of Customers:</span>
                                                <span class="sales-value">{{ $dailyReport->total_customers ?? 0 }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">
                                            <div class="sales-item">
                                                <span class="sales-label">Average Ticket:</span>
                                                <span class="sales-value">${{ number_format($dailyReport->average_ticket ?? 0, 2) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                            <td class="sales-right-col">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">
                                            <div class="sales-item">
                                                <span class="sales-label">Projected Sales:</span>
                                                <span class="sales-value">${{ number_format($dailyReport->projected_sales ?? 0, 2) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">
                                            <div class="sales-item">
                                                <span class="sales-label">Amount of Cancels:</span>
                                                <span class="sales-value">${{ number_format($dailyReport->amount_of_cancels ?? 0, 2) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">
                                            <div class="sales-item">
                                                <span class="sales-label">Amount of Voids:</span>
                                                <span class="sales-value">${{ number_format($dailyReport->amount_of_voids ?? 0, 2) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border-top: 3px double #000; padding-top: 10px;">
                                <div class="summary-item">
                                    <span class="summary-label">Gross Sales:</span>
                                    <span class="summary-value">${{ number_format($dailyReport->gross_sales ?? 0, 2) }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Total Amount of Coupons Received:</span>
                                    <span class="summary-value">${{ number_format($dailyReport->coupons_received ?? 0, 2) }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Adjustments: Overrings/Returns:</span>
                                    <span class="summary-value">${{ number_format($dailyReport->adjustments_overrings ?? 0, 2) }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Net Sales:</span>
                                    <span class="summary-value">${{ number_format($dailyReport->net_sales ?? 0, 2) }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Sales Tax:</span>
                                    <span class="summary-value">${{ number_format($dailyReport->tax ?? 0, 2) }}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Sales:</span>
                                    <span class="summary-value">${{ number_format($dailyReport->sales_pre_tax ?? 0, 2) }}</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Right Section -->
                <td class="right-cell">
                    <!-- Weather / Special Event Section (Top Right) -->
                    <table class="weather-table">
                        <tr>
                            <td class="section-title" colspan="2">Weather / Special Event</td>
                        </tr>
                        <tr>
                            <td class="weather-label">Weather Temp.</td>
                            <td class="weather-value">{{ $dailyReport->weather ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="weather-label">Weather Type</td>
                            <td class="weather-value"></td>
                        </tr>
                        <tr>
                            <td class="weather-label">Holiday/Event</td>
                            <td class="weather-value">{{ $dailyReport->holiday_event ?? '' }}</td>
                        </tr>
                    </table>

                    <!-- Summary Section (Middle Right) -->
                    <table class="summary-table">
                        <tr>
                            <td class="section-title" colspan="2">Summary</td>
                        </tr>
                        <tr>
                            <td class="summary-label-cell">Net Sales:</td>
                            <td class="summary-value-cell">${{ number_format($dailyReport->net_sales ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label-cell">Total Paid Outs:</td>
                            <td class="summary-value-cell">${{ number_format($dailyReport->total_paid_outs ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label-cell">Credit Cards:</td>
                            <td class="summary-value-cell">${{ number_format($dailyReport->credit_cards ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label-cell">Cash to Account for:</td>
                            <td class="summary-value-cell">${{ number_format($dailyReport->cash_to_account_for ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label-cell">Actual Deposit:</td>
                            <td class="summary-value-cell">${{ number_format($dailyReport->actual_deposit ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td class="summary-label-cell">Short/Over:</td>
                            <td class="summary-value-cell">
                                @if(($dailyReport->short ?? 0) < 0)
                                    ${{ number_format($dailyReport->short, 2) }}
                                @elseif(($dailyReport->over ?? 0) > 0)
                                    ${{ number_format($dailyReport->over, 2) }}
                                @else
                                    $0.00
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endforeach
</body>
</html>
