<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss Statement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .info {
            margin-bottom: 20px;
        }
        .info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .section-header {
            background-color: #e0e0e0;
            font-weight: bold;
            font-size: 14px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
            border-top: 2px solid #333;
        }
        .amount {
            text-align: right;
        }
        .sub-item {
            padding-left: 20px;
            font-size: 11px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        .negative {
            color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>PROFIT & LOSS STATEMENT</h1>
    </div>

    <div class="info">
        <p><strong>Store:</strong> {{ $storeName }}</p>
        <p><strong>Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</p>
        <p><strong>Generated:</strong> {{ $generatedAt->format('M d, Y H:i:s') }}</p>
    </div>

    <table>
        <!-- REVENUE SECTION -->
        <tr class="section-header">
            <th colspan="2">REVENUE</th>
        </tr>
        @if(!empty($pl['revenue']['items']))
            @foreach($pl['revenue']['items'] as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="amount">${{ number_format($item['amount'], 2) }}</td>
                </tr>
            @endforeach
        @endif
        <tr class="total-row">
            <td><strong>Total Revenue</strong></td>
            <td class="amount"><strong>${{ number_format($pl['revenue']['total'] ?? 0, 2) }}</strong></td>
        </tr>

        <!-- COGS SECTION -->
        <tr class="section-header">
            <th colspan="2">COST OF GOODS SOLD (COGS)</th>
        </tr>
        @if(!empty($pl['cogs']['items']))
            @foreach($pl['cogs']['items'] as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="amount">${{ number_format($item['amount'], 2) }}</td>
                </tr>
            @endforeach
        @endif
        <tr class="total-row">
            <td><strong>Total COGS</strong></td>
            <td class="amount"><strong>${{ number_format($pl['cogs']['total'] ?? 0, 2) }}</strong></td>
        </tr>

        <!-- GROSS PROFIT -->
        <tr class="total-row">
            <td><strong>Gross Profit</strong></td>
            <td class="amount"><strong>${{ number_format($pl['gross_profit'] ?? 0, 2) }}</strong></td>
        </tr>
        <tr>
            <td>Gross Margin</td>
            <td class="amount">{{ number_format($pl['gross_margin'] ?? 0, 2) }}%</td>
        </tr>

        <!-- OPERATING EXPENSES SECTION -->
        <tr class="section-header">
            <th colspan="2">OPERATING EXPENSES</th>
        </tr>
        @if(!empty($pl['operating_expenses']['items']))
            @foreach($pl['operating_expenses']['items'] as $item)
                @if(isset($item['items']))
                    <!-- Parent category with sub-items -->
                    <tr>
                        <td><strong>{{ $item['name'] }}</strong></td>
                        <td class="amount"><strong>${{ number_format($item['total'] ?? 0, 2) }}</strong></td>
                    </tr>
                    @foreach($item['items'] ?? [] as $subItem)
                        <tr class="sub-item">
                            <td>{{ $subItem['name'] }}</td>
                            <td class="amount">${{ number_format($subItem['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td class="amount">${{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @endif
            @endforeach
        @endif
        <tr class="total-row">
            <td><strong>Total Operating Expenses</strong></td>
            <td class="amount"><strong>${{ number_format($pl['operating_expenses']['total'] ?? 0, 2) }}</strong></td>
        </tr>

        <!-- NET PROFIT/LOSS -->
        <tr class="total-row">
            <td><strong>NET PROFIT / (LOSS)</strong></td>
            <td class="amount {{ ($pl['net_profit'] ?? 0) < 0 ? 'negative' : '' }}">
                <strong>${{ number_format($pl['net_profit'] ?? 0, 2) }}</strong>
            </td>
        </tr>
        <tr>
            <td>Net Margin</td>
            <td class="amount {{ ($pl['net_margin'] ?? 0) < 0 ? 'negative' : '' }}">
                {{ number_format($pl['net_margin'] ?? 0, 2) }}%
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This report was generated automatically by the Restaurant Management System</p>
        <p>For questions or discrepancies, please contact your administrator</p>
    </div>
</body>
</html>

