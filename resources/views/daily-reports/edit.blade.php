@extends('layouts.tabler')
@section('title', 'Edit Daily Report')
@section('content')

<select id="transactionTypeTemplate" style="display:none;">
    <option value="">Select Type</option>
    @foreach($types as $type)
        <option value="{{ $type->id }}">{{ $type->description_name }}</option>
    @endforeach
</select>

<select id="revenueTypeTemplate" style="display:none;">
    @foreach($revenueTypes as $revenueType)
        <option value="{{ $revenueType->id }}" data-category="{{ $revenueType->category }}">{{ $revenueType->name }}</option>
    @endforeach
</select>

<style>
    .report-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .report-header {
        background: #f8f9fa;
        color: #202124;
        padding: 20px;
        text-align: center;
        border: 1px solid #e0e0e0;
    }
    
    .company-name {
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .company-info {
        opacity: 0.9;
        font-size: 0.9rem;
    }
    
    .section-title {
        background: #f8f9fa;
        padding: 15px 20px;
        font-weight: 600;
        border-bottom: 2px solid #e9ecef;
        color: #495057;
        font-size: 1.1rem;
    }
    
    .form-section {
        padding: 20px;
    }
    
    .transaction-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .transaction-table th {
        background: #f8f9fa;
        padding: 12px 8px;
        border: 1px solid #dee2e6;
        font-weight: 600;
        text-align: center;
    }
    
    .transaction-table td {
        border: 1px solid #dee2e6;
        padding: 8px;
    }
    
    .form-input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 8px;
        font-size: 14px;
    }
    
    .sales-table .form-input {
        min-width: 120px;
        padding: 8px 12px;
    }
    
    .form-input:focus {
        outline: none;
        background: rgba(253,126,20,0.1);
        border-radius: 4px;
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
    
    .total-row {
        background: #fff3cd;
        font-weight: 600;
    }
    
    .btn-add-row {
        background: #fd7e14;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .btn-add-row:hover {
        background: #e8681a;
        transform: translateY(-1px);
    }
    
    .btn-remove-row {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .btn-remove-row:hover {
        background: #c82333;
    }
    
    .side-panel {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .side-panel .form-group {
        margin-bottom: 15px;
    }
    
    .side-panel label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        display: block;
    }
    
    .side-panel input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .category-labels {
        background: #e9ecef;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
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
        gap: 30px;
        margin-top: 20px;
    }
    
    .sales-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    
    .sales-table td {
        padding: 12px 15px;
        border: 1px solid #dee2e6;
    }
    
    .sales-table td:first-child {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        width: 40%;
    }
    
    .sales-table td:last-child {
        width: 35%;
    }
    
    .sales-table td:nth-child(2) {
        width: 25%;
    }
    
    .sales-table input[type="number"].form-input {
        width: 100%;
        min-width: 120px;
        padding: 8px 12px;
        font-size: 14px;
    }
    
    .calculated-field {
        background: #fff3cd !important;
        color: #856404;
        font-weight: 600;
    }
    
    .update-btn {
        background: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(253, 126, 20, 0.3);
        transition: all 0.3s;
    }
    
    .update-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(253, 126, 20, 0.4);
    }
    
    @media (max-width: 768px) {
        .sales-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .transaction-table th,
        .transaction-table td {
            padding: 8px 4px;
            font-size: 12px;
        }
        
        .company-name {
            font-size: 1.4rem;
        }
        
        .section-title {
            padding: 10px 15px;
            font-size: 1rem;
        }
        
        .form-section {
            padding: 15px;
        }
    }
    
    @media (max-width: 480px) {
        .transaction-table {
            font-size: 11px;
        }
        
        .transaction-table th,
        .transaction-table td {
            padding: 6px 3px;
        }
        
        .side-panel {
            padding: 15px;
        }
    }
</style>
<div class="container-fluid" style="max-width: 95%; margin-left: auto; margin-right: auto;">
    <div class="page-header">
        <div class="page-title">
            <h1>Edit Daily Report - {{ $dailyReport->report_date->format('M d, Y') }}</h1>
        </div>
        <div class="page-actions">
        <a href="{{ route('daily-reports.show', $dailyReport) }}" class="btn btn-secondary">Cancel</a>
    </div>
</div>

<div class="p-4">
    <form id="dailyReportForm" method="POST" action="{{ route('daily-reports.update', $dailyReport) }}">
        @csrf
        @method('PUT')
        
        <input type="hidden" name="store_id" value="{{ $dailyReport->store_id }}">
        <input type="hidden" class="NetSales" name="net_sales" value="">
        <input type="hidden" class="TaxInput" name="tax" value="">
        <input type="hidden" class="SalesInput" name="sales" value="">
        <input type="hidden" class="TotalPaidOuts" name="total_paid_outs" value="">
        <input type="hidden" class="CashToAccountInput" name="cash_to_account" value="">
        <input type="hidden" class="ShortInput" name="short" value="">
        <input type="hidden" class="OverInput" name="over" value="">

        <div class="report-container">
            <!-- Header Section -->
            <div class="report-header">
                <div class="company-name">{{ $dailyReport->store->store_info ?? 'Store Information' }}</div>
                <div class="company-info">
                    <div>{{ $dailyReport->store->address ?? '' }}</div>
                    <div>Phone: {{ $dailyReport->store->phone ?? '' }}</div>
                </div>
            </div>

            <!-- Transaction Expenses Section -->
            <div class="section-title">Transaction Expenses</div>
            <div class="form-section">
                <div class="row">
                    <div class="col-lg-8">
                        <table class="transaction-table" id="transactionTable">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Transaction ID</th>
                                    <th style="width: 30%;">Company</th>
                                    <th style="width: 25%;">Transaction Type</th>
                                    <th style="width: 20%;">Amount ($)</th>
                                    <th style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyReport->transactions as $index => $transaction)
                                    <tr>
                                        <td>
                                            <input type="number" class="form-input" name="transactions[{{ $index }}][transaction_id]" value="{{ $transaction->transaction_id }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-input" name="transactions[{{ $index }}][company]" value="{{ $transaction->company }}">
                                        </td>
                                        <td>
                                            <select class="form-input" name="transactions[{{ $index }}][transaction_type_id]">
                                                <option value="">Select Type</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}" {{ $transaction->transaction_type_id == $type->id ? 'selected' : '' }}>{{ $type->description_name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-input number-input" name="transactions[{{ $index }}][amount]" step="0.01" value="{{ $transaction->amount }}">
                                        </td>
                                        <td>
                                            @if($loop->first)
                                                <button type="button" class="btn-add-row" onclick="addTransactionRow()">+</button>
                                            @else
                                                <button type="button" class="btn-remove-row" onclick="removeTransactionRow(this)">×</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                
                                @if($dailyReport->transactions->count() === 0)
                                    <tr>
                                        <td>
                                            <input type="number" class="form-input" name="transactions[0][transaction_id]" value="1">
                                        </td>
                                        <td>
                                            <input type="text" class="form-input" name="transactions[0][company]" placeholder="Company name">
                                        </td>
                                        <td>
                                            <select class="form-input" name="transactions[0][transaction_type_id]">
                                                <option value="">Select Type</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}">{{ $type->description_name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-input number-input" name="transactions[0][amount]" step="0.01" placeholder="0.00">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-add-row" onclick="addTransactionRow()">+</button>
                                        </td>
                                    </tr>
                                @endif
                                
                                <tr class="total-row">
                                    <td colspan="3"><strong>Total Paid Outs:</strong></td>
                                    <td id="totalPaidOuts" class="number-input"><strong>$0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="side-panel">
                            <div class="form-group">
                                <label>Date:</label>
                                <input type="text" name="report_date" class="form-control date-input" value="{{ $dailyReport->report_date->format('m-d-Y') }}" placeholder="MM-DD-YYYY" maxlength="10" required>
                            </div>
                            <div class="form-group">
                                <label>Weather:</label>
                                <input type="text" name="weather" class="form-control" value="{{ $dailyReport->weather }}">
                            </div>
                            <div class="form-group">
                                <label>Holiday/Special Event:</label>
                                <input type="text" name="holiday_event" class="form-control" value="{{ $dailyReport->holiday_event }}">
                            </div>
                        </div>
                        
                        <div class="category-labels">
                            <div class="category">Accounting</div>
                            <div class="category">Food Cost</div>
                            <div class="category">Rent</div>
                            <div class="category">Taxes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Types Section -->
            <div class="section-title">Revenue Income Tracking</div>
            <div class="form-section">
                <div class="row">
                    <div class="col-lg-12">
                        <table class="transaction-table" id="revenueTable">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Revenue Type</th>
                                    <th style="width: 20%;">Amount ($)</th>
                                    <th style="width: 35%;">Notes</th>
                                    <th style="width: 15%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyReport->revenues as $index => $revenue)
                                    <tr>
                                        <td>
                                            <select class="form-input" name="revenues[{{ $index }}][revenue_income_type_id]">
                                                <option value="">Select Revenue Type</option>
                                                @foreach($revenueTypes as $revenueType)
                                                    <option value="{{ $revenueType->id }}" data-category="{{ $revenueType->category }}" {{ $revenue->revenue_income_type_id == $revenueType->id ? 'selected' : '' }}>{{ $revenueType->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-input revenue-amount" name="revenues[{{ $index }}][amount]" step="0.01" min="0" value="{{ $revenue->amount }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-input" name="revenues[{{ $index }}][notes]" value="{{ $revenue->notes }}" placeholder="Optional notes">
                                        </td>
                                        <td>
                                            @if($loop->first)
                                                <button type="button" class="btn-add-row" onclick="addRevenueRow()">+</button>
                                            @else
                                                <button type="button" class="btn-remove" onclick="removeRevenueRow(this)">×</button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                
                                @if($dailyReport->revenues->count() === 0)
                                    <tr>
                                        <td>
                                            <select class="form-input" name="revenues[0][revenue_income_type_id]">
                                                <option value="">Select Revenue Type</option>
                                                @foreach($revenueTypes as $revenueType)
                                                    <option value="{{ $revenueType->id }}" data-category="{{ $revenueType->category }}">{{ $revenueType->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-input revenue-amount" name="revenues[0][amount]" step="0.01" min="0" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="text" class="form-input" name="revenues[0][notes]" placeholder="Optional notes">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-add-row" onclick="addRevenueRow()">+</button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; color: #495057;">Total Revenue Entries:</span>
                                <span id="totalRevenue" style="font-weight: 600; color: #28a745; font-size: 1.1rem;">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px;">
                                <span style="font-weight: 600; color: #495057;">Online Platform Revenue:</span>
                                <span id="onlineRevenue" style="font-weight: 600; color: #17a2b8; font-size: 1.1rem;">$0.00</span>
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
                                <td><input type="number" name="projected_sales" class="form-input number-input" step="0.01" value="{{ $dailyReport->projected_sales }}" required></td>
                            </tr>
                            <tr>
                                <td>Amount of Cancels</td>
                                <td><input type="number" name="amount_of_cancels" class="form-input number-input" step="0.01" value="{{ $dailyReport->amount_of_cancels }}"></td>
                            </tr>
                            <tr>
                                <td>Amount of Voids</td>
                                <td><input type="number" name="amount_of_voids" class="form-input number-input" step="0.01" value="{{ $dailyReport->amount_of_voids }}"></td>
                            </tr>
                            <tr>
                                <td>Number of No Sales</td>
                                <td><input type="number" name="number_of_no_sales" class="form-input number-input" value="{{ $dailyReport->number_of_no_sales }}"></td>
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
                                <td rowspan="2">
                                    <div style="display:flex;justify-content: space-between;align-items: center;">
                                        <span>Total # of Coupons</span>
                                        <span style="width:30%;"><input type="number" name="total_coupons" value="{{ $dailyReport->total_coupons }}" class="form-input number-input" step="0.01" style="background: white;"></span>
                                    </div>
                                </td>
                                <td><strong>Gross Sales:</strong></td>
                                <td><input type="number" name="gross_sales" class="form-input number-input" step="0.01" value="{{ $dailyReport->gross_sales }}" required></td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount of Coupons Received:</strong></td>
                                <td><input type="number" name="coupons_received" class="form-input number-input" step="0.01" value="{{ $dailyReport->coupons_received }}"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Adjustments: Overrings/Returns:</strong></td>
                                <td><input type="number" name="adjustments_overrings" class="form-input number-input" step="0.01" value="{{ $dailyReport->adjustments_overrings }}"></td>
                            </tr>
                            <tr>
                                <td rowspan="2">
                                    <div style="display:flex;justify-content: space-between;align-items: center;">
                                        <span>Total # of Customers</span>
                                        <span style="width:30%;"><input type="number" name="total_customers" value="{{ $dailyReport->total_customers }}" class="form-input number-input" step="0.01" style="background: white;"></span>
                                    </div>
                                </td>
                                <td><strong>Net Sales:</strong></td>
                                <td id="netSales" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Tax:</strong></td>
                                <td id="tax" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;justify-content: space-between;align-items: center;">
                                        <span>Average Ticket</span>
                                        <span style="width:30%;"><input type="number" name="average_ticket" id="averageTicketInput" value="{{ $dailyReport->average_ticket }}" class="form-input number-input" step="0.01" style="background: white;" readonly></span>
                                    </div>
                                </td>
                                <td><strong>Sales (Pre-tax):</strong></td>
                                <td id="salesPreTax" class="calculated-field number-input">$0.00</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-4">
                        <table class="sales-table">
                            <tr>
                                <td><strong>Net Sales:</strong></td>
                                <td id="netSales2" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Total Paid Outs:</strong></td>
                                <td id="totalPaidOuts2" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Credit Cards:</strong></td>
                                <td><input type="number" name="credit_cards" class="form-input number-input" step="0.01" value="{{ $dailyReport->credit_cards }}"></td>
                            </tr>
                            <tr>
                                <td><strong>Cash To Account For:</strong></td>
                                <td id="cashToAccountFor" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Actual Deposit:</strong></td>
                                <td><input type="number" name="actual_deposit" class="form-input number-input" step="0.01" value="{{ $dailyReport->actual_deposit }}"></td>
                            </tr>
                            <tr>
                                <td><strong>Short:</strong></td>
                                <td id="short" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Over:</strong></td>
                                <td id="over" class="calculated-field number-input">$0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="update-btn">Update Daily Report</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let transactionCount = {{ $dailyReport->transactions->count() }};
let revenueCount = {{ $dailyReport->revenues->count() }};

// Auto-calculation functions
function calculateTotals() {
    // Get form values
    const grossSales = parseFloat(document.querySelector('input[name="gross_sales"]').value || 0);
    const couponsReceived = parseFloat(document.querySelector('input[name="coupons_received"]').value || 0);
    const adjustmentsOverrings = parseFloat(document.querySelector('input[name="adjustments_overrings"]').value || 0);
    const creditCards = parseFloat(document.querySelector('input[name="credit_cards"]').value || 0);
    const actualDeposit = parseFloat(document.querySelector('input[name="actual_deposit"]').value || 0);

    // Calculate total paid outs from transactions
    let totalPaidOuts = 0;
    document.querySelectorAll('input[name*="transactions"][name*="[amount]"]').forEach(input => {
        totalPaidOuts += parseFloat(input.value || 0);
    });

    // Calculate revenue totals
    let totalRevenueEntries = 0;
    let onlinePlatformRevenue = 0;
    document.querySelectorAll('#revenueTable tbody tr').forEach(row => {
        const amountInput = row.querySelector('input[name*="revenues"][name*="[amount]"]');
        const selectElement = row.querySelector('select[name*="[revenue_income_type_id]"]');
        
        if (amountInput && selectElement && selectElement.selectedIndex > 0) {
            const amount = parseFloat(amountInput.value || 0);
            
            if (amount > 0) {
                totalRevenueEntries += amount;
                
                // Check if this is an online platform revenue
                const selectedOption = selectElement.options[selectElement.selectedIndex];
                if (selectedOption && selectedOption.dataset.category === 'online') {
                    onlinePlatformRevenue += amount;
                }
            }
        }
    });

    // Calculate derived values
    // Net sales = sum of revenues
    const netSales = totalRevenueEntries;
    
    // Calculate 8.25% sales tax
    const tax = netSales * 0.0825 / 1.0825;
    const salesPreTax = netSales - tax;
    
    // Calculate average ticket
    const totalCustomers = parseFloat(document.querySelector('input[name="total_customers"]').value || 0);
    const averageTicket = totalCustomers > 0 ? netSales / totalCustomers : 0;
    
    // Cash to account for = Net Sales - Transaction Expenses - Online Platform Revenue - Credit Cards
    // Formula: Net Sales - transaction expenses - online platforms - credit card
    let cashToAccountFor = netSales - totalPaidOuts - onlinePlatformRevenue - creditCards;
    
    // Ensure result is not negative (numbers cannot go negative)
    cashToAccountFor = Math.max(0, Math.round(cashToAccountFor * 100) / 100);
    
    let short = 0;
    let over = 0;
    if (actualDeposit < cashToAccountFor) {
        short = actualDeposit - cashToAccountFor;
    } else {
        over = actualDeposit - cashToAccountFor;
    }

    // Update display
    document.getElementById('totalPaidOuts').innerHTML = `<strong>$${totalPaidOuts.toFixed(2)}</strong>`;
    document.getElementById('totalPaidOuts2').textContent = `$${totalPaidOuts.toFixed(2)}`;
    document.getElementById('netSales').textContent = `$${netSales.toFixed(2)}`;
    document.getElementById('netSales2').textContent = `$${netSales.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('salesPreTax').textContent = `$${salesPreTax.toFixed(2)}`;
    
    // Update average ticket input
    const averageTicketInput = document.querySelector('input[name="average_ticket"]');
    if (averageTicketInput) {
        averageTicketInput.value = averageTicket.toFixed(2);
    }
    
    document.getElementById('cashToAccountFor').textContent = `$${cashToAccountFor.toFixed(2)}`;
    document.getElementById('short').textContent = `$${short.toFixed(2)}`;
    document.getElementById('over').textContent = `$${over.toFixed(2)}`;
    
    // Update revenue totals
    if (document.getElementById('totalRevenue')) {
        document.getElementById('totalRevenue').textContent = `$${totalRevenueEntries.toFixed(2)}`;
    }
    if (document.getElementById('onlineRevenue')) {
        document.getElementById('onlineRevenue').textContent = `$${onlinePlatformRevenue.toFixed(2)}`;
    }

    // Update hidden fields
    document.querySelector('.NetSales').value = netSales.toFixed(2);
    document.querySelector('.TaxInput').value = tax.toFixed(2);
    document.querySelector('.SalesInput').value = salesPreTax.toFixed(2);
    document.querySelector('.TotalPaidOuts').value = totalPaidOuts.toFixed(2);
    document.querySelector('.CashToAccountInput').value = cashToAccountFor.toFixed(2);
    document.querySelector('.ShortInput').value = short.toFixed(2);
    document.querySelector('.OverInput').value = over.toFixed(2);
}

function addTransactionRow() {
    const tbody = document.querySelector('#transactionTable tbody');
    const totalRow = tbody.querySelector('.total-row');

    // Get dynamic options from hidden select
    const optionsHtml = document.getElementById('transactionTypeTemplate').innerHTML;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <input type="number" class="form-input" name="transactions[${transactionCount}][transaction_id]" value="${transactionCount + 1}">
        </td>
        <td>
            <input type="text" class="form-input" name="transactions[${transactionCount}][company]" placeholder="Company name">
        </td>
        <td>
            <select class="form-input" name="transactions[${transactionCount}][transaction_type_id]">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" class="form-input number-input" name="transactions[${transactionCount}][amount]" step="0.01" placeholder="0.00">
        </td>
        <td>
            <button type="button" class="btn-remove-row" onclick="removeTransactionRow(this)">×</button>
        </td>
    `;
    
    tbody.insertBefore(newRow, totalRow);
    transactionCount++;
    
    // Add event listener for the new amount input
    const amountInput = newRow.querySelector('input[name*="[amount]"]');
    if (amountInput) {
        amountInput.addEventListener('input', calculateTotals);
    }
}

function removeTransactionRow(button) {
    if (document.querySelectorAll('#transactionTable tbody tr:not(.total-row)').length > 1) {
        button.closest('tr').remove();
        calculateTotals();
    }
}

function addRevenueRow() {
    const tbody = document.querySelector('#revenueTable tbody');

    // Get dynamic options from hidden select
    const optionsHtml = document.getElementById('revenueTypeTemplate').innerHTML;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <select class="form-input" name="revenues[${revenueCount}][revenue_income_type_id]">
                <option value="">Select Revenue Type</option>
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" class="form-input revenue-amount" name="revenues[${revenueCount}][amount]" step="0.01" min="0" placeholder="0.00">
        </td>
        <td>
            <input type="text" class="form-input" name="revenues[${revenueCount}][notes]" placeholder="Notes (optional)">
        </td>
        <td>
            <button type="button" class="btn-remove-row" onclick="removeRevenueRow(this)">×</button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    revenueCount++;
    
    // Add event listeners for the new revenue row
    const amountInput = newRow.querySelector('.revenue-amount');
    const selectInput = newRow.querySelector('select[name*="[revenue_income_type_id]"]');
    
    if (amountInput) {
        amountInput.addEventListener('input', calculateTotals);
    }
    if (selectInput) {
        selectInput.addEventListener('change', calculateTotals);
    }
}

function removeRevenueRow(button) {
    if (document.querySelectorAll('#revenueTable tbody tr').length > 1) {
        button.closest('tr').remove();
        calculateTotals();
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for all inputs that affect calculations
    const inputs = [
        'input[name="gross_sales"]',
        'input[name="coupons_received"]', 
        'input[name="adjustments_overrings"]',
        'input[name="credit_cards"]',
        'input[name="actual_deposit"]',
        'input[name*="transactions"][name*="[amount]"]',
        'input[name*="revenues"][name*="[amount]"]'
    ];
    
    inputs.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.addEventListener('input', calculateTotals);
        });
    });

    // Add event listeners for revenue amount inputs
    document.querySelectorAll('#revenueTable input[name*="revenues"][name*="[amount]"]').forEach(input => {
        input.addEventListener('input', calculateTotals);
    });

    // Add event listeners for revenue type selects
    document.querySelectorAll('#revenueTable select[name*="[revenue_income_type_id]"]').forEach(select => {
        select.addEventListener('change', calculateTotals);
    });
    
    // Initial calculation
    calculateTotals();
});
</script>

@endsection