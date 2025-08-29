@extends('layouts.tabler')
@section('title', 'Daily Report')
@section('content')

<style>
    .report-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .report-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        text-align: center;
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
    
    .form-input:focus {
        outline: none;
        background: rgba(0,123,255,0.1);
        border-radius: 4px;
    }
    
    .number-input {
        text-align: right;
    }
    
    .total-row {
        background: #e7f3ff;
        font-weight: 600;
    }
    
    .btn-add-row {
        background: #28a745;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .btn-add-row:hover {
        background: #218838;
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
    }
    
    .sales-table td {
        padding: 12px 15px;
        border: 1px solid #dee2e6;
    }
    
    .sales-table td:first-child {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        width: 60%;
    }
    
    .calculated-field {
        background: #e7f3ff !important;
        color: #004085;
        font-weight: 600;
    }
    
    .save-btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        transition: all 0.3s;
    }
    
    .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
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

<div class="container-fluid p-4">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form id="dailyReportForm" method="POST" action="{{ route('daily-reports.store') }}">
        @csrf
        
        <div class="report-container">
            <!-- Header Section -->
            <div class="report-header">
                <div class="company-name">Phil's Philly Steaks - Hulen Mall</div>
                <div class="company-info">
                    <div>6301 Hulen Bend Blvd, Fort Worth, TX 76132</div>
                    <div>Phone: (817) 346-4221</div>
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
                                <tr>
                                    <td>
                                        <input type="number" class="form-input" name="transactions[0][transaction_id]" value="1">
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" name="transactions[0][company]" placeholder="Company name">
                                    </td>
                                    <td>
                                        <select class="form-input" name="transactions[0][transaction_type]">
                                            <option value="">Select Type</option>
                                            <option value="Food Cost">Food Cost</option>
                                            <option value="Rent">Rent</option>
                                            <option value="Accounting">Accounting</option>
                                            <option value="Taxes">Taxes</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-input number-input" name="transactions[0][amount]" step="0.01" placeholder="0.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-add-row" onclick="addTransactionRow()">+</button>
                                    </td>
                                </tr>
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
                                <input type="date" name="report_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="form-group">
                                <label>Weather:</label>
                                <input type="text" name="weather" class="form-control" placeholder="e.g., Sunny, Rainy">
                            </div>
                            <div class="form-group">
                                <label>Holiday/Special Event:</label>
                                <input type="text" name="holiday_event" class="form-control" placeholder="Special events">
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

            <!-- Sales Section -->
            <div class="section-title">Sales Information</div>
            <div class="form-section">
                <div class="row">
                    <div class="col-md-6">
                        <table class="sales-table">
                            <tr>
                                <td>Projected Sales</td>
                                <td><input type="number" name="projected_sales" class="form-input number-input" step="0.01" value="1200.00" required></td>
                            </tr>
                            <tr>
                                <td>Amount of Cancels</td>
                                <td><input type="number" name="amount_of_cancels" class="form-input number-input" step="0.01" value="0.00"></td>
                            </tr>
                            <tr>
                                <td>Amount of Voids</td>
                                <td><input type="number" name="amount_of_voids" class="form-input number-input" step="0.01" value="0.00"></td>
                            </tr>
                            <tr>
                                <td>Number of No Sales</td>
                                <td><input type="number" name="number_of_no_sales" class="form-input number-input" value="0"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Financial Summary Section -->
            <div class="section-title">Financial Summary</div>
            <div class="form-section">
                <div class="sales-grid">
                    <div>
                        <table class="sales-table">
                            <tr>
                                <td rowspan="2">Total # of Coupons</td>
                                <td><strong>Gross Sales:</strong></td>
                                <td><input type="number" name="gross_sales" class="form-input number-input" step="0.01" required></td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount of Coupons Received:</strong></td>
                                <td><input type="number" name="coupons_received" class="form-input number-input" step="0.01" value="0.00"></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Adjustments: Overrings/Returns:</strong></td>
                                <td><input type="number" name="adjustments_overrings" class="form-input number-input" step="0.01" value="0.00"></td>
                            </tr>
                            <tr>
                                <td rowspan="2">Total # of Customers</td>
                                <td><strong>Net Sales:</strong></td>
                                <td id="netSales" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Tax:</strong></td>
                                <td id="tax" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td>Average Ticket</td>
                                <td><strong>Sales (Pre-tax):</strong></td>
                                <td id="salesPreTax" class="calculated-field number-input">$0.00</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div>
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
                                <td><input type="number" name="credit_cards" class="form-input number-input" step="0.01" value="0.00"></td>
                            </tr>
                            <tr>
                                <td><strong>Cash To Account For:</strong></td>
                                <td id="cashToAccountFor" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Actual Deposit:</strong></td>
                                <td><input type="number" name="actual_deposit" class="form-input number-input" step="0.01" value="0.00"></td>
                            </tr>
                            <tr>
                                <td style="width: 40%;"><strong>Short:</strong></td>
                                <td id="short" class="calculated-field number-input" style="width: 30%;">$0.00</td>
                                <td style="width: 30%;"><strong>Over:</strong></td>
                                <td id="over" class="calculated-field number-input">$0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="save-btn">Save Daily Report</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let transactionCount = 1;

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
    document.querySelectorAll('input[name*="[amount]"]').forEach(input => {
        totalPaidOuts += parseFloat(input.value || 0);
    });

    // Calculate derived values
    const netSales = grossSales - couponsReceived - adjustmentsOverrings;
    const tax = netSales - (netSales / 1.0825); // Texas tax rate 8.25%
    const salesPreTax = netSales - tax;
    const cashToAccountFor = netSales - totalPaidOuts - creditCards;
    
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
    document.getElementById('cashToAccountFor').textContent = `$${cashToAccountFor.toFixed(2)}`;
    document.getElementById('short').textContent = `$${short.toFixed(2)}`;
    document.getElementById('over').textContent = `$${over.toFixed(2)}`;
}

function addTransactionRow() {
    const tbody = document.querySelector('#transactionTable tbody');
    const totalRow = tbody.querySelector('.total-row');
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <input type="number" class="form-input" name="transactions[${transactionCount}][transaction_id]" value="${transactionCount + 1}">
        </td>
        <td>
            <input type="text" class="form-input" name="transactions[${transactionCount}][company]" placeholder="Company name">
        </td>
        <td>
            <select class="form-input" name="transactions[${transactionCount}][transaction_type]">
                <option value="">Select Type</option>
                <option value="Food Cost">Food Cost</option>
                <option value="Rent">Rent</option>
                <option value="Accounting">Accounting</option>
                <option value="Taxes">Taxes</option>
                <option value="Other">Other</option>
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
    newRow.querySelector('input[name*="[amount]"]').addEventListener('input', calculateTotals);
}

function removeTransactionRow(button) {
    if (document.querySelectorAll('#transactionTable tbody tr:not(.total-row)').length > 1) {
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
        'input[name*="[amount]"]'
    ];
    
    inputs.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.addEventListener('input', calculateTotals);
        });
    });
    
    // Initial calculation
    calculateTotals();
});
</script>

@endsection