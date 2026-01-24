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
        overflow: visible;
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
    
    .form-input:focus {
        outline: none;
        background: rgba(253,126,20,0.1);
        border-radius: 4px;
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
    input[type="number"].negative,
    .number-input.negative,
    .calculated-field.negative {
        color: #dc3545 !important;
    }
    
    input[type="number"]:invalid {
        color: #dc3545;
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
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
        position: relative;
        z-index: 10;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    
    .btn-add-row:hover {
        background: #218838;
        transform: translateY(-1px);
    }
    
    .btn-remove {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .btn-remove:hover {
        background: #c82333;
    }
    
    .btn-remove-row {
        background: #dc3545;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }
    
    .btn-remove-row:hover {
        background: #c82333;
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
    
    .sales-table input[type="number"].form-input {
        width: 100%;
        min-width: 120px;
        padding: 8px 12px;
        font-size: 14px;
    }
    
    .calculated-field {
        background: #e7f3ff !important;
        color: #004085;
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
<div class="container-fluid p-4">
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">⚠️ Please Review and Fix the Following Issues:</h5>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>⚠️ Warning:</strong> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="export-buttons" style="margin-bottom: 20px;">
        <a href="{{ route('daily-reports.show', $dailyReport) }}" class="export-btn export-btn-back">
            ← Cancel
        </a>
    </div>

    <select id="transactionTypeTemplate" style="display:none;">
        <option value="">Select Type</option>
        @foreach($types as $type)
            <option value="{{ $type->id }}">{{ $type->name }}</option>
        @endforeach
    </select>

    <select id="revenueTypeTemplate" style="display:none;">
        @foreach($revenueTypes as $revenueType)
            <option value="{{ $revenueType->id }}" data-category="{{ $revenueType->category }}">{{ $revenueType->name }}</option>
        @endforeach
    </select>

    <select id="vendorTemplate" style="display:none;">
        <option value="">Select Company</option>
        <option value="__create_new__">+ Create New Company</option>
        @foreach($vendors as $vendor)
            <option value="{{ $vendor->id }}" 
                    data-vendor-name="{{ $vendor->vendor_name }}"
                    data-transaction-type-id="{{ $vendor->default_transaction_type_id }}">
                {{ $vendor->vendor_name }}
            </option>
        @endforeach
    </select>

    <form id="dailyReportForm" method="POST" action="{{ route('daily-reports.update', $dailyReport) }}">
        @csrf
        @method('PUT')
        
        <input type="hidden" name="store_id" value="{{ $dailyReport->store_id }}">
        <input type="hidden" name="gross_sales" id="grossSalesHidden" value="0">
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
                                            <select class="form-input vendor-select" name="transactions[{{ $index }}][company]" data-row="{{ $index }}" onchange="handleVendorChange(this)">
                                                <option value="">Select Company</option>
                                                <option value="__create_new__">+ Create New Company</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}" 
                                                            data-vendor-name="{{ $vendor->vendor_name }}"
                                                            data-transaction-type-id="{{ $vendor->default_transaction_type_id }}"
                                                            {{ $transaction->company == $vendor->vendor_name ? 'selected' : '' }}>
                                                        {{ $vendor->vendor_name }}
                                                    </option>
                                                @endforeach
                                                @if($transaction->company && !$vendors->contains('vendor_name', $transaction->company))
                                                    <option value="{{ $transaction->company }}" selected>{{ $transaction->company }}</option>
                                                @endif
                                            </select>
                                            <input type="hidden" name="transactions[{{ $index }}][vendor_id]" class="vendor-id-input" value="{{ $transaction->vendor_id ?? '' }}">
                                        </td>
                                        <td>
                                            <select class="form-input transaction-type-select" name="transactions[{{ $index }}][transaction_type]" data-row="{{ $index }}">
                                                <option value="">Select Type</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}" {{ $transaction->transaction_type_id == $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-input number-input" name="transactions[{{ $index }}][amount]" min="0" value="{{ $transaction->amount }}">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-remove" onclick="removeTransactionRow(this)">×</button>
                                        </td>
                                    </tr>
                                @endforeach
                                
                                @if($dailyReport->transactions->count() === 0)
                                    <tr>
                                        <td>
                                            <input type="number" class="form-input" name="transactions[0][transaction_id]" value="1">
                                        </td>
                                        <td>
                                            <select class="form-input vendor-select" name="transactions[0][company]" data-row="0" onchange="handleVendorChange(this)">
                                                <option value="">Select Company</option>
                                                <option value="__create_new__">+ Create New Company</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->id }}" 
                                                            data-vendor-name="{{ $vendor->vendor_name }}"
                                                            data-transaction-type-id="{{ $vendor->default_transaction_type_id }}">
                                                        {{ $vendor->vendor_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="transactions[0][vendor_id]" class="vendor-id-input" value="">
                                        </td>
                                        <td>
                                            <select class="form-input transaction-type-select" name="transactions[0][transaction_type]" data-row="0">
                                                <option value="">Select Type</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-input number-input" name="transactions[0][amount]" min="0" placeholder="0.00">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-remove" onclick="removeTransactionRow(this)">×</button>
                                        </td>
                                    </tr>
                                @endif
                                
                                <tr class="total-row">
                                    <td colspan="3"><strong>Total Transaction Expenses:</strong></td>
                                    <td id="totalPaidOuts" class="number-input"><strong>$0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" id="addTransactionRow" class="btn-add-row" style="margin-top: 10px;">+ Add Transaction</button>
                        <div style="margin-top: 8px; padding: 8px; background: #fff3cd; border-radius: 4px; border: 1px solid #ffeaa7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; color: #856404;">Total Transaction Expenses:</span>
                                <span id="totalTransactionExpenses" style="font-weight: 600; color: #856404; font-size: 1.1rem;">$0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="side-panel">
                            <div class="form-group">
                                <label>Date:</label>
                                <input type="text" name="report_date" class="form-control date-input bg-light" value="{{ $dailyReport->report_date->format('m-d-Y') }}" placeholder="MM-DD-YYYY" maxlength="10" required readonly>
                                <small class="text-muted">
                                    <i class="fas fa-lock me-1"></i>Date cannot be changed
                                </small>
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
                        
                        <!-- <div class="category-labels">
                            <div class="category">Accounting</div>
                            <div class="category">Food Cost</div>
                            <div class="category">Rent</div>
                            <div class="category">Taxes</div>
                        </div> -->
                    </div>
                </div>
            </div>

            <!-- Revenue Types Section -->
            <div class="section-title">Revenue Income Tracking</div>
            <div class="form-section">
                <div class="row">
                    <div class="col-lg-12" style="overflow: visible;">
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
                                            <input type="number" class="form-input revenue-amount" name="revenues[{{ $index }}][amount]" min="0" value="{{ $revenue->amount }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-input" name="revenues[{{ $index }}][notes]" value="{{ $revenue->notes }}" placeholder="Optional notes">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-remove" onclick="removeRevenueRow(this)">×</button>
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
                                            <input type="number" class="form-input revenue-amount" name="revenues[0][amount]" min="0" placeholder="0.00">
                                        </td>
                                        <td>
                                            <input type="text" class="form-input" name="revenues[0][notes]" placeholder="Optional notes">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-remove" onclick="removeRevenueRow(this)">×</button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <button type="button" id="addRevenueRow" class="btn-add-row" style="margin-top: 10px; display: inline-block !important; visibility: visible !important; opacity: 1 !important;">+ Add Revenue Entry</button>
                        <div style="margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; color: #495057;">Total Revenue Income:</span>
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
                                <td><input type="number" name="projected_sales" class="form-input number-input" value="{{ $dailyReport->projected_sales }}" required></td>
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
                                <td>
                                    <div style="display:flex;justify-content: space-between;align-items: center;">
                                        <span><strong>Gross Sales:</strong></span>
                                        <span style="width:30%;" id="grossSales" class="calculated-field number-input">$0.00</span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;justify-content: space-between;align-items: center;">
                                        <span><strong>Total Amount of Coupons Received:</strong></span>
                                        <span style="width:30%;"><input type="number" name="coupons_received" class="form-input number-input" value="{{ $dailyReport->coupons_received }}" style="background: white;"></span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;justify-content: space-between;align-items: center;">
                                        <span>Total # of Coupons</span>
                                        <span style="width:30%;"><input type="number" name="total_coupons" value="{{ $dailyReport->total_coupons }}" class="form-input number-input" style="background: white;"></span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;justify-content: space-between;align-items: center;">
                                        <span><strong>Adjustments: Overrings/Returns:</strong></span>
                                        <span style="width:30%;"><input type="number" name="adjustments_overrings" class="form-input number-input" value="{{ $dailyReport->adjustments_overrings }}" style="background: white;"></span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td rowspan="2">
                                    <div style="display:flex;justify-content: space-between;align-items: center;">
                                        <span>Total # of Customers</span>
                                        <span style="width:30%;"><input type="number" name="total_customers" value="{{ $dailyReport->total_customers }}" class="form-input number-input" style="background: white;"></span>
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
                                        <span style="width:30%;"><input type="number" name="average_ticket" id="averageTicketInput" value="{{ $dailyReport->average_ticket }}" class="form-input number-input" style="background: white;" readonly></span>
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
                                <td><strong>Total Transaction Expenses:</strong></td>
                                <td id="totalPaidOuts2" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Online Platform Revenue:</strong></td>
                                <td id="onlineRevenue2" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Credit Cards:</strong></td>
                                <td><input type="number" name="credit_cards" id="creditCardsInput" class="form-input number-input" value="{{ $dailyReport->credit_cards }}"></td>
                            </tr>
                            <tr>
                                <td><strong>Cash To Account For:</strong></td>
                                <td id="cashToAccountFor" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Actual Deposit:</strong></td>
                                <td><input type="number" name="actual_deposit" class="form-input number-input" value="{{ $dailyReport->actual_deposit }}"></td>
                            </tr>
                            <tr>
                                <td><strong>Short:</strong></td>
                                <td id="short" class="calculated-field number-input" style="color: #495057;">$0.00</td>
                            </tr>
                            <tr>
                                <td><strong>Over:</strong></td>
                                <td id="over" class="calculated-field number-input" style="color: #495057;">$0.00</td>
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
let transactionCount = {{ $dailyReport->transactions->count() > 0 ? $dailyReport->transactions->count() : 1 }};
let revenueCount = {{ $dailyReport->revenues->count() > 0 ? $dailyReport->revenues->count() : 1 }};

// Auto-calculation functions
function calculateTotals() {
    // Get form values
    const couponsReceived = parseFloat(document.querySelector('input[name="coupons_received"]').value || 0);
    const adjustmentsOverrings = parseFloat(document.querySelector('input[name="adjustments_overrings"]').value || 0);
    let creditCards = parseFloat(document.querySelector('input[name="credit_cards"]').value || 0);
    const actualDeposit = parseFloat(document.querySelector('input[name="actual_deposit"]').value || 0);

    // Calculate total paid outs from transactions (Transaction Expenses)
    let totalPaidOuts = 0;
    document.querySelectorAll('#transactionTable input[name*="[amount]"]').forEach(input => {
        totalPaidOuts += parseFloat(input.value || 0);
    });

    // Get revenue totals (already calculated separately)
    let totalRevenueIncome = 0;
    let onlinePlatformRevenue = 0;
    let creditCardRevenue = 0;
    let checksRevenue = 0;
    let cryptoRevenue = 0;

    // Calculate revenue totals for this function
    document.querySelectorAll('#revenueTable tbody tr').forEach(row => {
        const amountInput = row.querySelector('.revenue-amount');
        const selectElement = row.querySelector('select[name*="revenue_income_type_id"]');

        if (amountInput && selectElement && selectElement.selectedIndex > 0) {
            const amount = parseFloat(amountInput.value || 0);
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const category = selectedOption ? selectedOption.dataset.category : '';

            if (amount > 0) {
                totalRevenueIncome += amount;

                if (category === 'online') {
                    onlinePlatformRevenue += amount;
                }
                
                if (category === 'card') {
                    creditCardRevenue += amount;
                }
                
                if (category === 'check') {
                    checksRevenue += amount;
                }
                
                if (category === 'crypto') {
                    cryptoRevenue += amount;
                }
            }
        }
    });

    // Gross Sales = Total Revenue Entries + Coupons Amount Received
    const grossSales = totalRevenueIncome + couponsReceived;
    
    // Net Sales = Total Revenue Entries - Coupons Received - Adjustments: Overrings/Returns
    const netSales = totalRevenueIncome - couponsReceived - adjustmentsOverrings;
    
    // Tax = Net Sales minus (Net Sales / 1.0825)
    const tax = netSales - (netSales / 1.0825);
    
    // Sales (Pre-tax) = Net Sales - Tax
    const salesPreTax = netSales - tax;
    
    // Average Ticket = Net Sales / Total Customers
    const totalCustomers = parseFloat(document.querySelector('input[name="total_customers"]').value || 0);
    const averageTicket = totalCustomers > 0 ? netSales / totalCustomers : 0;
    
    // Auto-fill Credit Cards from card category revenues
    const creditCardsInput = document.querySelector('input[name="credit_cards"]');
    if (creditCardsInput) {
        // Auto-fill Credit Cards unless user has manually edited it
        const isManuallyEdited = creditCardsInput.dataset.manuallyEdited === 'true';
        
        if (!isManuallyEdited) {
            if (creditCardRevenue > 0) {
                creditCardsInput.value = creditCardRevenue.toFixed(2);
                creditCardsInput.dataset.lastCalculated = creditCardRevenue.toFixed(2);
                checkNegativeInputs();
            } else if (creditCardRevenue === 0) {
                creditCardsInput.value = '0.00';
            }
        } else {
            creditCardsInput.dataset.lastCalculated = creditCardRevenue.toFixed(2);
        }
        
        creditCards = parseFloat(creditCardsInput.value || 0);
    }
    
    // Cash To Account For = Net Sales - Total Transaction Expenses - Online Platform Revenue - Credit Cards - Checks - Crypto
    let cashToAccountFor = netSales - totalPaidOuts - onlinePlatformRevenue - creditCards - checksRevenue - cryptoRevenue;
    
    // Ensure result is not negative (numbers cannot go negative)
    cashToAccountFor = Math.max(0, Math.round(cashToAccountFor * 100) / 100);
    
    // Over/Short = Actual Deposit - Cash To Account For
    let short = 0;
    let over = 0;
    if (actualDeposit < cashToAccountFor) {
        short = actualDeposit - cashToAccountFor;
    } else {
        over = actualDeposit - cashToAccountFor;
    }

    // Helper function to format and color amounts
    function formatAmount(amount, element, isNegative = false) {
        const formatted = `$${Math.abs(amount).toFixed(2)}`;
        if (amount < 0 || isNegative) {
            element.textContent = `-${formatted}`;
            element.classList.add('negative');
            element.style.color = '#dc3545';
        } else {
            element.textContent = formatted;
            element.classList.remove('negative');
            element.style.color = '';
        }
    }
    
    // Helper function to format amount with HTML (for innerHTML)
    function formatAmountHTML(amount) {
        const absAmount = Math.abs(amount).toFixed(2);
        const color = amount < 0 ? '#dc3545' : '';
        const sign = amount < 0 ? '-' : '';
        return `<strong style="color: ${color || ''}">${sign}$${absAmount}</strong>`;
    }
    
    // Update display
    document.getElementById('totalPaidOuts').innerHTML = formatAmountHTML(totalPaidOuts);
    if (document.getElementById('totalPaidOutsDisplay')) {
        formatAmount(totalPaidOuts, document.getElementById('totalPaidOutsDisplay'));
    }
    formatAmount(totalPaidOuts, document.getElementById('totalPaidOuts2'));
    if (document.getElementById('onlineRevenue2')) {
        formatAmount(onlinePlatformRevenue, document.getElementById('onlineRevenue2'));
    }
    
    // Update Gross Sales
    if (document.getElementById('grossSales')) {
        formatAmount(grossSales, document.getElementById('grossSales'));
    }
    if (document.getElementById('grossSalesHidden')) {
        document.getElementById('grossSalesHidden').value = grossSales.toFixed(2);
    }
    
    formatAmount(netSales, document.getElementById('netSales'));
    formatAmount(netSales, document.getElementById('netSales2'));
    formatAmount(tax, document.getElementById('tax'));
    formatAmount(salesPreTax, document.getElementById('salesPreTax'));
    
    // Update average ticket input
    const averageTicketInput = document.querySelector('input[name="average_ticket"]');
    if (averageTicketInput) {
        averageTicketInput.value = averageTicket.toFixed(2);
        if (averageTicket < 0) {
            averageTicketInput.classList.add('negative');
        } else {
            averageTicketInput.classList.remove('negative');
        }
    }
    
    formatAmount(cashToAccountFor, document.getElementById('cashToAccountFor'));
    const shortElement = document.getElementById('short');
    const overElement = document.getElementById('over');
    if (shortElement) {
        formatAmount(short, shortElement);
    }
    if (overElement) {
        formatAmount(over, overElement);
    }
    
    // Update revenue totals
    if (document.getElementById('totalRevenue')) {
        formatAmount(totalRevenueIncome, document.getElementById('totalRevenue'));
    }
    if (document.getElementById('onlineRevenue')) {
        formatAmount(onlinePlatformRevenue, document.getElementById('onlineRevenue'));
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

    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <input type="number" class="form-input" name="transactions[${transactionCount}][transaction_id]" value="${transactionCount + 1}">
        </td>
        <td>
            <select class="form-input vendor-select" name="transactions[${transactionCount}][company]" data-row="${transactionCount}" onchange="handleVendorChange(this)">
                ${document.getElementById('vendorTemplate').innerHTML}
            </select>
            <input type="hidden" name="transactions[${transactionCount}][vendor_id]" class="vendor-id-input" value="">
        </td>
        <td>
            <select class="form-input transaction-type-select" name="transactions[${transactionCount}][transaction_type]" data-row="${transactionCount}">
                ${document.getElementById('transactionTypeTemplate').innerHTML}
            </select>
        </td>
        <td>
            <input type="number" class="form-input number-input" name="transactions[${transactionCount}][amount]" min="0" placeholder="0.00">
        </td>
        <td>
            <button type="button" class="btn-remove" onclick="removeTransactionRow(this)">×</button>
        </td>
    `;

    tbody.insertBefore(newRow, totalRow);
    transactionCount++;

    // attach input listener for totals
    newRow.querySelector('input[name*="[amount]"]').addEventListener('input', calculateTotals);
}

function removeTransactionRow(button) {
    if (document.querySelectorAll('#transactionTable tbody tr:not(.total-row)').length > 1) {
        button.closest('tr').remove();
        calculateTotals();
    }
}

function addRevenueRow() {
    const tbody = document.querySelector('#revenueTable tbody');
    
    // Get revenue type options from the first row
    const firstSelect = tbody.querySelector('select[name*="revenue_income_type_id"]');
    const optionsHtml = firstSelect ? firstSelect.innerHTML : '';
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td>
            <select class="form-input" name="revenues[${revenueCount}][revenue_income_type_id]">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" class="form-input revenue-amount" name="revenues[${revenueCount}][amount]" min="0" placeholder="0.00">
        </td>
        <td>
            <input type="text" class="form-input" name="revenues[${revenueCount}][notes]" placeholder="Optional notes">
        </td>
        <td>
            <button type="button" class="btn-remove" onclick="removeRevenueRow(this)">×</button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    revenueCount++;
    
    // Attach event listeners
    newRow.querySelector('.revenue-amount').addEventListener('input', function() {
        calculateRevenueTotals();
        calculateTotals(); // Also trigger main calculation
    });
    newRow.querySelector('select').addEventListener('change', function() {
        calculateRevenueTotals();
        calculateTotals(); // Also trigger main calculation
    });
}

function removeRevenueRow(button) {
    if (document.querySelectorAll('#revenueTable tbody tr').length > 1) {
        button.closest('tr').remove();
        calculateRevenueTotals();
        calculateTotals();
    }
}

// Revenue totals calculation (separate function)
function calculateRevenueTotals() {
    let totalRevenueIncome = 0;
    let onlinePlatformRevenue = 0;

    document.querySelectorAll('#revenueTable tbody tr').forEach(row => {
        const amountInput = row.querySelector('.revenue-amount');
        const selectElement = row.querySelector('select[name*="revenue_income_type_id"]');

        if (amountInput && selectElement && selectElement.selectedIndex > 0) {
            const amount = parseFloat(amountInput.value || 0);
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const category = selectedOption ? selectedOption.dataset.category : '';

            if (amount > 0) {
                totalRevenueIncome += amount;

                if (category === 'online') {
                    onlinePlatformRevenue += amount;
                }
            }
        }
    });

    // Update display
    if (document.getElementById('totalRevenue')) {
        const element = document.getElementById('totalRevenue');
        const formatted = `$${Math.abs(totalRevenueIncome).toFixed(2)}`;
        element.textContent = formatted;
        element.style.color = totalRevenueIncome < 0 ? '#dc3545' : '#28a745';
    }
    
    if (document.getElementById('onlineRevenue')) {
        const element = document.getElementById('onlineRevenue');
        const formatted = `$${Math.abs(onlinePlatformRevenue).toFixed(2)}`;
        element.textContent = formatted;
        element.style.color = onlinePlatformRevenue < 0 ? '#dc3545' : '#17a2b8';
    }
}

// Handle vendor change
window.handleVendorChange = function(selectElement) {
    const row = selectElement.getAttribute('data-row');
    const selectedValue = selectElement.value;
    
    if (selectedValue === '__create_new__') {
        openCreateVendorModal(row, selectElement);
        return;
    }
    
    // Find the vendor option
    const selectedOption = selectElement.options[selectElement.selectedIndex];
    if (selectedOption && selectedValue && selectedValue !== '__create_new__') {
        const vendorName = selectedOption.dataset.vendorName || selectedOption.textContent;
        const transactionTypeId = selectedOption.dataset.transactionTypeId;
        
        // Update the company name in the select (for display)
        // The value is already set to vendor ID
        
        // Update vendor_id hidden input
        const rowElement = selectElement.closest('tr');
        const vendorIdInput = rowElement.querySelector('.vendor-id-input');
        if (vendorIdInput) {
            vendorIdInput.value = selectedValue;
        }
        
        // Auto-fill transaction type if available
        if (transactionTypeId) {
            const transactionTypeSelect = rowElement.querySelector('.transaction-type-select');
            if (transactionTypeSelect) {
                transactionTypeSelect.value = transactionTypeId;
            }
        }
    }
};

// Input validation and formatting
function formatCurrency(input) {
    let value = parseFloat(input.value);
    if (isNaN(value)) value = 0;
    input.value = value;
}

function validateField(input) {
    const value = parseFloat(input.value || 0);
    const fieldName = input.name;
    
    // Clear previous error styling
    input.classList.remove('is-invalid', 'is-valid');
    
    let isValid = true;
    let message = '';
    
    // Validate negative values
    if (value < 0 && !fieldName.includes('short')) {
        isValid = false;
        message = 'Value cannot be negative';
    }
    
    // Show validation feedback
    if (isValid) {
        input.classList.add('is-valid');
    } else {
        input.classList.add('is-invalid');
        showTooltip(input, message);
    }
    
    return isValid;
}

function showTooltip(element, message) {
    // Remove existing tooltip
    const existingTooltip = element.parentNode.querySelector('.validation-tooltip');
    if (existingTooltip) existingTooltip.remove();
    
    // Create tooltip
    const tooltip = document.createElement('div');
    tooltip.className = 'validation-tooltip alert alert-danger p-2 mt-1';
    tooltip.textContent = message;
    tooltip.style.fontSize = '0.8rem';
    
    element.parentNode.appendChild(tooltip);
    
    // Auto-hide after 5 seconds
    setTimeout(() => tooltip.remove(), 5000);
}

// Function to check and color negative inputs
function checkNegativeInputs() {
    document.querySelectorAll('input[type="number"]').forEach(input => {
        const value = parseFloat(input.value) || 0;
        if (value < 0) {
            input.classList.add('negative');
        } else {
            input.classList.remove('negative');
        }
    });
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Check initial negative values
    checkNegativeInputs();
    
    // Add event listeners for all inputs that affect calculations
    const inputs = [
        'input[name="coupons_received"]',
        'input[name="adjustments_overrings"]',
        'input[name="credit_cards"]',
        'input[name="actual_deposit"]',
        'input[name="total_customers"]',
        'input[name*="[amount]"]'
    ];
    
    inputs.forEach(selector => {
        const elements = document.querySelectorAll(selector);
        elements.forEach(element => {
            element.addEventListener('input', function() {
                // Mark credit cards as manually edited if user changes it
                if (element.name === 'credit_cards') {
                    const currentValue = parseFloat(element.value || 0);
                    const lastCalculated = parseFloat(element.dataset.lastCalculated || 0);
                    // If value changed significantly, mark as manually edited
                    if (Math.abs(currentValue - lastCalculated) > 0.01) {
                        element.dataset.manuallyEdited = 'true';
                    }
                }
                checkNegativeInputs();
                calculateTotals();
                validateField(this);
            });
            
            element.addEventListener('blur', function() {
                if (this.type === 'number') {
                    formatCurrency(this);
                }
            });
        });
    });
    
    // Add loading state to form submission
    const form = document.getElementById('dailyReportForm');
    if (form) {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Add transaction row button
    const addTransactionBtn = document.getElementById('addTransactionRow');
    if (addTransactionBtn) {
        addTransactionBtn.addEventListener('click', addTransactionRow);
    }
    
    // Add revenue row button
    const addRevenueBtn = document.getElementById('addRevenueRow');
    if (addRevenueBtn) {
        addRevenueBtn.addEventListener('click', addRevenueRow);
    }
    
    // Initial revenue amount listeners
    document.querySelectorAll('.revenue-amount').forEach(input => {
        input.addEventListener('input', function() {
            calculateRevenueTotals();
            calculateTotals(); // Also trigger main calculation
        });
    });

    // Initial revenue type change listeners
    document.querySelectorAll('select[name*="revenue_income_type_id"]').forEach(select => {
        select.addEventListener('change', function() {
            calculateRevenueTotals();
            calculateTotals(); // Also trigger main calculation
        });
    });
    
    // Initial calculation
    calculateTotals();
    calculateRevenueTotals();
});

window.openCreateVendorModal = function(row, selectElement) {
    // Store the row and select element for later use
    window.currentVendorRow = row;
    window.currentVendorSelect = selectElement;
    
    // Reset modal form
    document.getElementById('newVendorName').value = '';
    document.getElementById('newVendorType').value = '';
    document.getElementById('newVendorTransactionType').value = '';
    document.getElementById('newVendorCoa').value = '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('createVendorModal'));
    modal.show();
}

// Save new vendor
window.saveNewVendor = async function() {
    const vendorName = document.getElementById('newVendorName').value.trim();
    const vendorType = document.getElementById('newVendorType').value;
    const transactionTypeId = document.getElementById('newVendorTransactionType').value;
    const coaId = document.getElementById('newVendorCoa').value;
    
    if (!vendorName) {
        alert('Please enter a vendor name');
        return;
    }
    
    if (!vendorType) {
        alert('Please select a vendor type');
        return;
    }
    
    try {
        const response = await fetch('/api/vendors', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                vendor_name: vendorName,
                vendor_type: vendorType,
                default_transaction_type_id: transactionTypeId || null,
                default_coa_id: coaId || null
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Add new vendor to dropdown
            const vendorTemplate = document.getElementById('vendorTemplate');
            const newOption = document.createElement('option');
            newOption.value = data.id;
            newOption.setAttribute('data-vendor-name', data.vendor_name);
            newOption.setAttribute('data-transaction-type-id', data.default_transaction_type_id || '');
            newOption.textContent = data.vendor_name;
            vendorTemplate.appendChild(newOption);
            
            // Update current select
            if (window.currentVendorSelect) {
                const currentSelect = window.currentVendorSelect;
                const newSelectOption = newOption.cloneNode(true);
                currentSelect.appendChild(newSelectOption);
                currentSelect.value = data.id;
                
                // Trigger change to auto-fill transaction type
                handleVendorChange(currentSelect);
            }
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createVendorModal'));
            modal.hide();
            
            // Show success message
            alert('Vendor created successfully!');
        } else {
            alert('Error creating vendor: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error creating vendor. Please try again.');
    }
}
</script>

    <!-- Create Vendor Modal -->
    <div class="modal fade" id="createVendorModal" tabindex="-1" aria-labelledby="createVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createVendorModalLabel">Create New Company</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createVendorForm">
                        <div class="mb-3">
                            <label for="newVendorName" class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="newVendorName" required>
                        </div>
                        <div class="mb-3">
                            <label for="newVendorType" class="form-label">Company Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="newVendorType" required>
                                <option value="">Select Type</option>
                                <option value="Food">Food</option>
                                <option value="Beverage">Beverage</option>
                                <option value="Supplies">Supplies</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Services">Services</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newVendorTransactionType" class="form-label">Default Transaction Type</label>
                            <select class="form-select" id="newVendorTransactionType">
                                <option value="">Select Transaction Type</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="newVendorCoa" class="form-label">Default Chart of Account</label>
                            <select class="form-select" id="newVendorCoa">
                                <option value="">Select COA</option>
                                @php
                                    $coas = \App\Models\ChartOfAccount::where('is_active', true)->orderBy('account_name')->get();
                                @endphp
                                @foreach($coas as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->account_code }} - {{ $coa->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveNewVendor()">Create Company</button>
                </div>
            </div>
        </div>
    </div>

@endsection