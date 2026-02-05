@extends('layouts.tabler')
@section('title', 'Daily Report')
@section('content')

<style>
    /* Safari-specific fixes for daily report form */
    @supports (-webkit-appearance: none) {
        button, input[type="button"], input[type="submit"] {
            -webkit-appearance: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        input, textarea {
            -webkit-appearance: none;
        }
        
        select.form-input, .transaction-table select {
            -webkit-appearance: none;
            appearance: none;
            min-height: 38px;
            position: relative;
            z-index: 2;
            background-color: #fff;
        }
        
        .form-control, .form-input:not(select) {
            -webkit-appearance: none;
            border-radius: 4px;
        }
        
        .transaction-table tbody tr td {
            position: relative;
        }
        .transaction-table tbody tr td:nth-child(2),
        .transaction-table tbody tr td:nth-child(3) {
            z-index: 1;
        }
        .transaction-table tbody tr:focus-within td:nth-child(2),
        .transaction-table tbody tr:focus-within td:nth-child(3) {
            z-index: 10;
        }
    }

    /* Custom light-theme dropdown (replaces native select popup in WebKit/Safari) */
    .custom-select-wrap {
        position: relative;
        width: 100%;
        min-height: 38px;
    }
    .custom-select-wrap select.select-native-hidden {
        position: absolute !important;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        min-height: 38px;
        opacity: 0;
        z-index: 2;
        pointer-events: none;
    }
    .custom-select-overlay {
        position: absolute;
        left: 0;
        top: 0;
        right: 0;
        min-height: 38px;
        padding: 8px 28px 8px 8px;
        background: #ffffff !important;
        color: #202124 !important;
        border: 1px solid #dadce0;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        z-index: 1;
        display: flex;
        align-items: center;
        box-sizing: border-box;
    }
    .custom-select-overlay::after {
        content: '';
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: 5px solid transparent;
        border-top-color: #5f6368;
    }
    .custom-select-menu {
        display: none;
        position: absolute;
        left: 0;
        top: 100%;
        min-width: 100%;
        max-height: 280px;
        overflow-y: auto;
        background: #ffffff !important;
        color: #202124 !important;
        border: 1px solid #dadce0;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 1000;
        margin-top: 2px;
    }
    .custom-select-menu.open {
        display: block;
    }
    .custom-select-option {
        display: block;
        width: 100%;
        padding: 10px 12px;
        background: #ffffff !important;
        color: #202124 !important;
        border: none;
        text-align: left;
        cursor: pointer;
        font-size: 14px;
    }
    .custom-select-option:hover {
        background: #f1f3f4 !important;
        color: #202124 !important;
    }
    
    /* Ensure buttons are visible in Safari */
    button {
        display: inline-block;
        width: auto;
        min-width: auto;
    }
    
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
        overflow: visible; /* allow select dropdowns to show outside (Safari) */
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
        margin-bottom: 20px;
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
        overflow: visible;
        vertical-align: middle;
    }
    
    .form-input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 8px;
        font-size: 14px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
    }
    
    .form-input:focus {
        outline: none;
        background: rgba(0,123,255,0.1);
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
        width: auto;
        min-width: 120px;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
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
        display: inline-block;
        width: auto;
        min-width: 30px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    
    .btn-remove:hover {
        background: #c82333;
    }
    
    .btn-remove-row {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        display: inline-block;
        width: auto;
        min-width: 30px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
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
        font-size: 14px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        box-sizing: border-box;
        -webkit-box-sizing: border-box;
        display: block;
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
        display: -webkit-grid;
        display: grid;
        -webkit-grid-template-columns: 1fr 1fr;
        grid-template-columns: 1fr 1fr;
        -webkit-grid-gap: 15px;
        gap: 15px;
        margin-top: 12px;
    }
    
    .sales-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .sales-table td {
        padding: 8px 10px;
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
        background: -webkit-linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 25px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        -webkit-box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        transition: all 0.3s;
        -webkit-transition: all 0.3s;
        display: inline-block;
        width: auto;
        min-width: 180px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    
    .save-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    }
    
    @media (max-width: 768px) {
        .sales-grid {
            -webkit-grid-template-columns: 1fr;
            grid-template-columns: 1fr;
            -webkit-grid-gap: 20px;
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

    <select id="transactionTypeTemplate" style="display:none;">
    <option value="">Select Type</option>
    @foreach($coas as $coa)
        <option value="{{ $coa->id }}">{{ $coa->account_code }} - {{ $coa->account_name }}</option>
    @endforeach
</select>

<select id="vendorTemplate" style="display:none;">
    <option value="">Select Company</option>
    <option value="__create_new__">+ Create New Company</option>
    @foreach($vendors as $vendor)
        <option value="{{ $vendor->id }}" 
                data-vendor-name="{{ $vendor->vendor_name }}"
                data-default-coa-id="{{ $vendor->default_coa_id }}">
            {{ $vendor->vendor_name }}
        </option>
    @endforeach
</select>


    <form id="dailyReportForm" method="POST" action="{{ route('daily-reports.store') }}">
        @csrf

        <input type="hidden" name="store_id" value="{{$store->id ?? ''}}">
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
                <div class="company-name">{{@$store->store_info}}</div>
                <div class="company-info">
                    <div>{{@$store->address}}</div>
                    <div>Phone: {{@$store->phone}}</div>
                </div>
            </div>

            <!-- Transaction Expenses Section -->
            <div class="section-title">Transaction Expenses</div>
            <div class="form-section" style="overflow: visible;">
                <div class="row" style="overflow: visible;">
                    <div class="col-lg-8" style="overflow: visible;">
                        <table class="transaction-table" id="transactionTable">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Transaction ID</th>
                                    <th style="width: 30%;">Vendor Description</th>
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
                                        <select class="form-input vendor-select" name="transactions[0][company]" data-row="0" onchange="handleVendorChange(this)">
                                            <option value="">Select Company</option>
                                            <option value="__create_new__">+ Create New Company</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" 
                                                        data-vendor-name="{{ $vendor->vendor_name }}"
                                                        data-default-coa-id="{{ $vendor->default_coa_id }}">
                                                    {{ $vendor->vendor_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="transactions[0][vendor_id]" class="vendor-id-input" value="">
                                    </td>
                                    <td>
                                        <select class="form-input transaction-type-select" name="transactions[0][transaction_type]" data-row="0">
                                            <option value="">Select Type</option>
                                            @foreach($coas as $coa)
                                       <option value="{{ $coa->id }}">{{ $coa->account_code }} - {{ $coa->account_name }}</option>
                                         @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-input number-input" name="transactions[0][amount]" min="0" placeholder="0.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-add-row" onclick="addTransactionRow()" style="width: auto; min-width: 40px;">+</button>
                                    </td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="3"><strong>Total Transaction Expenses:</strong></td>
                                    <td id="totalPaidOuts" class="number-input"><strong>$0</strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <div style="margin-top: 8px; padding: 8px; background: #fff3cd; border-radius: 4px; border: 1px solid #ffeaa7;">
                            <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; width: 100%;">
                                <span style="font-weight: 600; color: #856404;">Total Transaction Expenses:</span>
                                <span id="totalTransactionExpenses" style="font-weight: 600; color: #856404; font-size: 1.1rem;">$0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="side-panel">
                            <div class="form-group">
                                <label>Date:</label>
                                <input type="text" name="report_date" class="form-control date-input {{ isset($reportDate) ? 'bg-light' : '' }}" value="{{ isset($reportDate) ? \Carbon\Carbon::parse($reportDate)->format('m-d-Y') : date('m-d-Y') }}" placeholder="MM-DD-YYYY" maxlength="10" required {{ isset($reportDate) ? 'readonly' : '' }}>
                                @if(isset($reportDate))
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>Date pre-selected from multi-step process
                                    </small>
                                @endif
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
                                        <input type="number" class="form-input revenue-amount" name="revenues[0][amount]" min="0" placeholder="0">
                                    </td>
                                    <td>
                                        <input type="text" class="form-input" name="revenues[0][notes]" placeholder="Optional notes">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-remove" onclick="removeRevenueRow(this)">×</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" id="addRevenueRow" class="btn-add-row" style="margin-top: 10px; display: inline-block !important; visibility: visible !important; opacity: 1 !important; width: auto;">+ Add Revenue Entry</button>
                        <div style="margin-top: 8px; padding: 8px; background: #f8f9fa; border-radius: 4px;">
                            <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; width: 100%;">
                                <span style="font-weight: 600; color: #495057;">Total Revenue Income:</span>
                                <span id="totalRevenue" style="font-weight: 600; color: #28a745; font-size: 1.1rem;">$0</span>
                            </div>
                            <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; margin-top: 5px; width: 100%;">
                                <span style="font-weight: 600; color: #495057;">Online Platform Revenue:</span>
                                <span id="onlineRevenue" style="font-weight: 600; color: #17a2b8; font-size: 1.1rem;">$0</span>
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
                                <td><input type="number" name="projected_sales" class="form-input number-input" value="1200" required></td>
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
                                    <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; width: 100%;">
                                        <span><strong>Gross Sales:</strong></span>
                                        <span style="width:30%;" id="grossSales" class="calculated-field number-input">$0</span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; width: 100%;">
                                        <span><strong>Total Amount of Coupons Received:</strong></span>
                                        <span style="width:30%;"><input type="number" name="coupons_received" class="form-input number-input" value="0" style="background: white;"></span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; width: 100%;">
                                        <span>Total # of Coupons</span>
                                        <span style="width:30%;"><input type="number" name="total_coupons" value="0" class="form-input number-input" style="background: white;"></span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; width: 100%;">
                                        <span><strong>Adjustments: Overrings/Returns:</strong></span>
                                        <span style="width:30%;"><input type="number" name="adjustments_overrings" class="form-input number-input" value="0" style="background: white;"></span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td rowspan="2">
                                    
                                    <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; width: 100%;">
                                        <span>Total # of Customers</span>
                                        <span style="width:30%;"><input type="number" name="total_customers" value="0" class="form-input number-input" style="background: white;"></span>
                                    </div>

                                </td>
                                <td><strong>Net Sales:</strong></td>
                                <td id="netSales" class="calculated-field number-input">$0</td>
                            </tr>
                            <tr>
                                <td><strong>Tax:</strong></td>
                                <td id="tax" class="calculated-field number-input">$0</td>
                            </tr>
                            <tr>
                                <td>
                                   
                                    <div style="display: -webkit-flex; display: flex; -webkit-justify-content: space-between; justify-content: space-between; -webkit-align-items: center; align-items: center; width: 100%;">
                                        <span> Average Ticket</span>
                                        <span style="width:30%;"><input type="number" name="average_ticket" id="averageTicketInput" value="0" class="form-input number-input" style="background: white;" readonly></span>
                                    </div>
                                </td>
                                <td><strong>Sales (Pre-tax):</strong></td>
                                <td id="salesPreTax" class="calculated-field number-input">$0</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-4">
                        <table class="sales-table">
                            <tr>
                                <td><strong>Net Sales:</strong></td>
                                <td id="netSales2" class="calculated-field number-input">$0</td>
                            </tr>
                            <tr>
                                <td><strong>Total Transaction Expenses:</strong></td>
                                <td id="totalPaidOuts2" class="calculated-field number-input">$0</td>
                            </tr>
                            <tr>
                                <td><strong>Online Platform Revenue:</strong></td>
                                <td id="onlineRevenue2" class="calculated-field number-input">$0</td>
                            </tr>
                            <tr>
                                <td><strong>Credit Cards:</strong></td>
                                <td><input type="number" name="credit_cards" class="form-input number-input" value="0"></td>
                            </tr>
                            <tr>
                                <td><strong>Cash To Account For:</strong></td>
                                <td id="cashToAccountFor" class="calculated-field number-input">$0</td>
                            </tr>
                            <tr>
                                <td><strong>Actual Deposit:</strong></td>
                                <td><input type="number" name="actual_deposit" class="form-input number-input" value="0"></td>
                            </tr>
                            <tr>
                                <td id="short-td"><strong>Short:</strong></td>
                                <td id="short" class="calculated-field number-input">$0</td>
                            </tr>
                            <tr>
                                <td id="over-td"><strong>Over:</strong></td>
                                <td id="over" class="calculated-field number-input">$0</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="text-center mt-2">
                    <button type="submit" class="save-btn">Save Daily Report</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let transactionCount = 1;

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

// Auto-calculation functions
function calculateTotals() {
    // Get form values
    const couponsReceived = parseFloat(document.querySelector('input[name="coupons_received"]').value || 0);
    const adjustmentsOverrings = parseFloat(document.querySelector('input[name="adjustments_overrings"]').value || 0);
    let creditCards = parseFloat(document.querySelector('input[name="credit_cards"]').value || 0);
    const actualDeposit = parseFloat(document.querySelector('input[name="actual_deposit"]').value || 0);
    const totalCustomers = parseFloat(document.querySelector('input[name="total_customers"]').value || 0);

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
    const netSales = totalRevenueIncome - adjustmentsOverrings;
    
    // Tax = Net Sales minus (Net Sales / 1.0825)
    const tax = netSales - (netSales / 1.0825);
    
    // Sales (Pre-tax) = Net Sales - Tax
    const salesPreTax = netSales - tax;
    
    // Average Ticket = Net Sales / Total Customers
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
    console.log(cashToAccountFor,'cashToAccountFor');
    // Ensure result is not negative (numbers cannot go negative)
    // cashToAccountFor = Math.max(0, Math.round(cashToAccountFor * 100) / 100);
    console.log(cashToAccountFor,'cashToAccountFor after');
    // Over/Short = Actual Deposit - Cash To Account For
    
    // let short = 0;
    // let over = 0;
    // if (actualDeposit < cashToAccountFor) {
    //     short = actualDeposit - cashToAccountFor;
    // } else {
    //     over = actualDeposit - cashToAccountFor;
    // }
    let short = 0;
    let over = 0;
    
    let diff;

if (cashToAccountFor >= 0) {
    diff = actualDeposit - cashToAccountFor;
} else {
    diff = actualDeposit + cashToAccountFor;
}

if (diff < 0) {
    short = diff;   // negative = short
} else {
    over = diff;    // positive = over
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
    if (document.getElementById('totalTransactionExpenses')) {
        formatAmount(totalPaidOuts, document.getElementById('totalTransactionExpenses'));
    }
    formatAmount(totalPaidOuts, document.getElementById('totalPaidOuts2'));
    formatAmount(onlinePlatformRevenue, document.getElementById('onlineRevenue2'));
    
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
    
    document.getElementById('averageTicketInput').value = averageTicket.toFixed(2);
    if (averageTicket < 0) {
        document.getElementById('averageTicketInput').classList.add('negative');
    } else {
        document.getElementById('averageTicketInput').classList.remove('negative');
    }
    
    formatAmount(cashToAccountFor, document.getElementById('cashToAccountFor'));

    const shortEl = document.getElementById('short');
     const overEl  = document.getElementById('over');
     const shortTd = document.getElementById('short-td');
     const overTd = document.getElementById('over-td');

    formatAmount(short, document.getElementById('short'));
    formatAmount(over, document.getElementById('over'));

    // Show/hide Short and Over rows based on values
    const shortRow = shortTd ? shortTd.closest('tr') : null;
    const overRow = overTd ? overTd.closest('tr') : null;
    
    if (short < 0) {
        // If short is less than 0, hide over row
        if (overRow) {
            overRow.style.display = 'none';
        }
        // Show short row
        if (shortRow) {
            shortRow.style.display = '';
        }
    } else if (over >= 0) {
        // If over is 0 or greater, hide short row
        if (shortRow) {
            shortRow.style.display = 'none';
        }
        // Show over row
        if (overRow) {
            overRow.style.display = '';
        }
    } else {
        // Default: show both
        if (shortRow) {
            shortRow.style.display = '';
        }
        if (overRow) {
            overRow.style.display = '';
        }
    }

        document.querySelector('.NetSales').value = netSales.toFixed(2);
    document.querySelector('.TaxInput').value = tax.toFixed(2);
    document.querySelector('.SalesInput').value = salesPreTax.toFixed(2); // SalesInput = salesPreTax
    document.querySelector('.TotalPaidOuts').value = totalPaidOuts.toFixed(2);
    document.querySelector('.CashToAccountInput').value = cashToAccountFor.toFixed(2);

    document.querySelector('.ShortInput').value = short.toFixed(2);
    document.querySelector('.OverInput').value = over.toFixed(2);
}



window.addTransactionRow = function () {
    const tbody = document.querySelector('#transactionTable tbody');
    const totalRow = tbody.querySelector('.total-row');

    // get dynamic options from hidden select
    const optionsHtml = document.getElementById('transactionTypeTemplate').innerHTML;

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
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number" class="form-input number-input" name="transactions[${transactionCount}][amount]" min="0" placeholder="0.00">
        </td>
        <td>
            <button type="button" class="btn-remove-row" onclick="removeTransactionRow(this)">×</button>
        </td>
    `;

    tbody.insertBefore(newRow, totalRow);
    transactionCount++;

    // attach input listener for totals
    newRow.querySelector('input[name*="[amount]"]').addEventListener('input', calculateTotals);
    // init custom light-theme dropdowns for new row (Safari/WebKit)
    if (typeof initLightDropdowns === 'function') initLightDropdowns(newRow);
}


function removeTransactionRow(button) {
    if (document.querySelectorAll('#transactionTable tbody tr:not(.total-row)').length > 1) {
        button.closest('tr').remove();
        calculateTotals();
    }
}

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
    
    // Validate gross vs net sales (removed since gross sales is now auto-calculated)
    
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
    const existingTooltip = document.querySelector('.validation-tooltip');
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

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
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
    
    // Prevent Create Vendor form from submitting (e.g. Enter key) and reloading the page
    const createVendorForm = document.getElementById('createVendorForm');
    if (createVendorForm) {
        createVendorForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveNewVendor();
        });
    }

    // Initial calculation
    calculateTotals();
    calculateRevenueTotals();
});

// Revenue Functions
let revenueCount = 1;

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
    }
}

function calculateRevenueTotals() {
    let totalRevenue = 0;
    let onlineRevenue = 0;

    // Get all revenue rows
    document.querySelectorAll('#revenueTable tbody tr').forEach(row => {
        const amountInput = row.querySelector('.revenue-amount');
        const selectElement = row.querySelector('select[name*="revenue_income_type_id"]');

        if (amountInput && selectElement && selectElement.selectedIndex > 0) {
            const amount = parseFloat(amountInput.value || 0);
            const selectedOption = selectElement.options[selectElement.selectedIndex];

            if (amount > 0) {
                totalRevenue += amount;

                // Check if this is an online platform using category data
                if (selectedOption && selectedOption.dataset.category === 'online') {
                    onlineRevenue += amount;
                    console.log('Online revenue found:', amount, 'Total online:', onlineRevenue);
                }
            }
        }
    });

    // Update revenue display (separate from transaction expenses)
    document.getElementById('totalRevenue').textContent = `$${totalRevenue.toFixed(2)}`;
    document.getElementById('onlineRevenue').textContent = `$${onlineRevenue.toFixed(2)}`;

    // Recalculate main totals when revenue changes (affects Cash To Account For)
    calculateTotals();
}

// Event listeners for revenue functionality
document.addEventListener('DOMContentLoaded', function() {
    initLightDropdowns(document.body);
    // Add revenue row button
    document.getElementById('addRevenueRow').addEventListener('click', addRevenueRow);
    
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
});

/* Custom light-theme dropdown (avoids WebKit/Safari native dark select popup) */
function getSelectedOptionText(select) {
    const opt = select.options[select.selectedIndex];
    return opt ? opt.textContent.trim() : '';
}

function initLightDropdowns(container) {
    const selects = container.querySelectorAll('.vendor-select, .transaction-type-select');
    selects.forEach(select => {
        if (select.dataset.lightDropdown === '1') return;
        select.dataset.lightDropdown = '1';

        const wrap = document.createElement('div');
        wrap.className = 'custom-select-wrap';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('select-native-hidden');

        const overlay = document.createElement('div');
        overlay.className = 'custom-select-overlay';
        overlay.setAttribute('tabindex', '0');
        overlay.textContent = getSelectedOptionText(select);
        wrap.appendChild(overlay);

        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'custom-select-option';
            btn.textContent = opt.textContent.trim();
            btn.dataset.value = opt.value;
            if (opt.getAttribute('data-vendor-name')) btn.dataset.vendorName = opt.getAttribute('data-vendor-name');
            if (opt.getAttribute('data-default-coa-id')) btn.dataset.defaultCoaId = opt.getAttribute('data-default-coa-id');
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const val = btn.dataset.value;
                if (select.classList.contains('vendor-select') && val === '__create_new__') {
                    openCreateVendorModal(select.getAttribute('data-row'), select);
                    menu.classList.remove('open');
                    return;
                }
                select.value = val;
                overlay.textContent = btn.textContent;
                menu.classList.remove('open');
                select.dispatchEvent(new Event('change', { bubbles: true }));
                if (select.classList.contains('vendor-select')) handleVendorChange(select);
            });
            menu.appendChild(btn);
        }
        wrap.appendChild(menu);

        select.addEventListener('change', function() { overlay.textContent = getSelectedOptionText(select); });

        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            const open = menu.classList.toggle('open');
            if (open) {
                const close = function(ev) {
                    if (!wrap.contains(ev.target)) { menu.classList.remove('open'); document.removeEventListener('click', close); }
                };
                setTimeout(() => document.addEventListener('click', close), 0);
            }
        });
        overlay.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); overlay.click(); }
        });
    });
}

// Handle vendor selection change
window.handleVendorChange = function(selectElement) {
    const row = selectElement.getAttribute('data-row');
    const selectedValue = selectElement.value;
    
    if (selectedValue === '__create_new__') {
        // Open create vendor modal
        openCreateVendorModal(row, selectElement);
        return;
    }
    
    if (selectedValue) {
        // Get selected option
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const defaultCoaId = selectedOption.getAttribute('data-default-coa-id');
        const vendorId = selectedValue;
        const vendorName = selectedOption.getAttribute('data-vendor-name');
        
        // Set vendor_id hidden input
        const vendorIdInput = selectElement.closest('tr').querySelector('.vendor-id-input');
        if (vendorIdInput) {
            vendorIdInput.value = vendorId;
        }
        
        // Auto-fill transaction type if vendor has default_coa_id
        if (defaultCoaId) {
            const transactionTypeSelect = selectElement.closest('tr').querySelector('.transaction-type-select');
            if (transactionTypeSelect) {
                transactionTypeSelect.value = defaultCoaId;
                // Update custom light-theme dropdown overlay if present
                const twrap = transactionTypeSelect.parentElement;
                if (twrap && twrap.classList.contains('custom-select-wrap')) {
                    const toverlay = twrap.querySelector('.custom-select-overlay');
                    if (toverlay && typeof getSelectedOptionText === 'function') toverlay.textContent = getSelectedOptionText(transactionTypeSelect);
                }
            }
        }
    } else {
        // Clear vendor_id when no vendor selected
        const vendorIdInput = selectElement.closest('tr').querySelector('.vendor-id-input');
        if (vendorIdInput) {
            vendorIdInput.value = '';
        }
    }
}

// Open create vendor modal
window.openCreateVendorModal = function(row, selectElement) {
    // Store the row and select element for later use
    window.currentVendorRow = row;
    window.currentVendorSelect = selectElement;
    
    // Reset modal form
    document.getElementById('newVendorName').value = '';
    document.getElementById('newVendorCoa').value = '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('createVendorModal'));
    modal.show();
}

// Save new vendor
window.saveNewVendor = async function() {
    const vendorName = document.getElementById('newVendorName').value.trim();
    const coaId = document.getElementById('newVendorCoa').value;
    
    if (!vendorName) {
        alert('Please enter a vendor description');
        return;
    }
    
    if (!coaId) {
        alert('Please select a default chart of account');
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
                vendor_type: 'Other', // Default type since it's required
                default_coa_id: coaId
            })
        });
        
        const json = await response.json();
        
        if (response.ok) {
            // API returns { message, data: { id, vendor_name, default_coa_id, ... } }
            const v = json.data || json;
            const vendorId = v.id;
            const vendorName = v.vendor_name || v.name || '';
            const defaultCoaId = (v.default_coa_id != null) ? String(v.default_coa_id) : '';

            // Add new vendor to template so future rows and all selects have it
            const vendorTemplate = document.getElementById('vendorTemplate');
            const newOption = document.createElement('option');
            newOption.value = vendorId;
            newOption.setAttribute('data-vendor-name', vendorName);
            newOption.setAttribute('data-default-coa-id', defaultCoaId);
            newOption.textContent = vendorName;
            vendorTemplate.appendChild(newOption);

            // Add new vendor option to every vendor select on the page (so no refresh needed)
            document.querySelectorAll('.vendor-select').forEach(function(sel) {
                const hasOption = Array.from(sel.options).some(function(o) { return o.value === String(vendorId); });
                if (hasOption) return;
                const opt = newOption.cloneNode(true);
                sel.appendChild(opt);
                // If this select has a custom light-theme menu, add the new option button to the menu
                const wrap = sel.parentElement;
                if (wrap && wrap.classList.contains('custom-select-wrap')) {
                    const menu = wrap.querySelector('.custom-select-menu');
                    const overlay = wrap.querySelector('.custom-select-overlay');
                    if (menu && overlay) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'custom-select-option';
                        btn.textContent = vendorName;
                        btn.dataset.value = vendorId;
                        btn.dataset.vendorName = vendorName;
                        btn.dataset.defaultCoaId = defaultCoaId;
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            sel.value = btn.dataset.value;
                            overlay.textContent = btn.textContent;
                            menu.classList.remove('open');
                            sel.dispatchEvent(new Event('change', { bubbles: true }));
                            handleVendorChange(sel);
                        });
                        menu.appendChild(btn);
                    }
                }
            });

            // Select the new vendor in the current row and update overlay
            if (window.currentVendorSelect) {
                const currentSelect = window.currentVendorSelect;
                currentSelect.value = vendorId;
                handleVendorChange(currentSelect);
                const wrap = currentSelect.parentElement;
                if (wrap && wrap.classList.contains('custom-select-wrap')) {
                    const overlay = wrap.querySelector('.custom-select-overlay');
                    if (overlay) overlay.textContent = vendorName;
                }
            }

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createVendorModal'));
            modal.hide();
            
            // Show success message
            alert('Vendor created successfully!');
        } else {
            const errMsg = (json.errors && Object.values(json.errors).flat().join(' ')) || json.message || json.error || 'Unknown error';
            alert('Error creating vendor: ' + errMsg);
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
                    <h5 class="modal-title" id="createVendorModalLabel">Create New Vendor Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createVendorForm">
                        <div class="mb-3">
                            <label for="newVendorName" class="form-label">Vendor Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="newVendorName" required>
                        </div>
                        <div class="mb-3">
                            <label for="newVendorCoa" class="form-label">Default Chart of Account <span class="text-danger">*</span></label>
                            <select class="form-select" id="newVendorCoa" required>
                                <option value="">Select COA</option>
                                @php
                                    $coas = \App\Models\ChartOfAccount::where('is_active', true)
                                        ->whereIn('account_type', ['COGS', 'Expense'])
                                        ->orderBy('account_code')
                                        ->orderBy('account_name')
                                        ->get();
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
                    <button type="button" class="btn btn-primary" onclick="saveNewVendor()">Create Vendor</button>
                </div>
            </div>
        </div>
    </div>

@endsection