@extends('layouts.tabler')
@section('title', 'Daily Report')
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
    }
    
    .table-material tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.04);
    }
    
    .transaction-table {
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    
    .transaction-table th {
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
    
    .transaction-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
    }
    
    .transaction-table tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.04);
    }
    
    .form-input {
        width: 100%;
        border: 1px solid rgba(0, 0, 0, 0.12);
        background: #f5f5f5;
        padding: 0.75rem;
        border-radius: 4px 4px 0 0;
        border-bottom: 2px solid rgba(0, 0, 0, 0.42);
        font-size: 0.875rem;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .form-input:focus {
        outline: none;
        background: #fff;
        border-bottom-color: #1976d2;
        box-shadow: 0 1px 0 0 #1976d2;
    }
    
    .number-input {
        text-align: right;
    }
    
    .total-row {
        background: #e3f2fd;
        font-weight: 500;
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
        cursor: pointer;
    }
    
    .btn-material:hover {
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .btn-add-row {
        background: #1976d2;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .btn-add-row:hover {
        background: #1565c0;
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .btn-remove,
    .btn-remove-row {
        background: #d32f2f;
        color: white;
        border: none;
        padding: 0.5rem 0.75rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.875rem;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 0px 2px 2px 0px rgba(0, 0, 0, 0.14), 0px 1px 5px 0px rgba(0, 0, 0, 0.12);
    }
    
    .btn-remove:hover,
    .btn-remove-row:hover {
        background: #c62828;
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
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
        background-color: #1976d2;
        color: white;
        border: none;
        padding: 0.625rem 1.5rem;
        border-radius: 4px;
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        letter-spacing: 0.0892857143em;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0px 3px 1px -2px rgba(0, 0, 0, 0.2), 0px 2px 2px 0px rgba(0, 0, 0, 0.14), 0px 1px 5px 0px rgba(0, 0, 0, 0.12);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .save-btn:hover {
        background-color: #1565c0;
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
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
        border-left: 4px solid #4caf50;
    }
    
    .info-card-material.info {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
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
    @foreach($types as $type)
        <option value="{{ $type->id }}">{{ $type->name }}</option>
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


    <form id="dailyReportForm" method="POST" action="{{ route('daily-reports.store') }}">
        @csrf

        <input type="hidden" name="store_id" value="{{$store->id ?? ''}}">
        <input type="hidden" class="NetSales" name="net_sales" value="">
        <input type="hidden" class="TaxInput" name="tax" value="">
        <input type="hidden" class="SalesInput" name="sales" value="">
        <input type="hidden" class="TotalPaidOuts" name="total_paid_outs" value="">
        <input type="hidden" class="CashToAccountInput" name="cash_to_account" value="">
        <input type="hidden" class="ShortInput" name="short" value="">
        <input type="hidden" class="OverInput" name="over" value="">





        
        <div class="card-material">
            <!-- Header Section -->
            <div class="card-header-material">
                <div class="card-title-material">{{@$store->store_info}}</div>
                <div class="card-subtitle-material">
                    <div>{{@$store->address}}</div>
                    <div>Phone: {{@$store->phone}}</div>
                </div>
            </div>

            <!-- Transaction Expenses Section -->
            <div class="section-header-material">
                <i class="bi bi-receipt me-2"></i>Transaction Expenses
            </div>
            <div class="section-body-material">
                <div class="row">
                    <div class="col-lg-8">
                        <table class="transaction-table" id="transactionTable">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Company</th>
                                    <th style="width: 35%;">Transaction Type</th>
                                    <th style="width: 20%;">Amount ($)</th>
                                    <th style="width: 5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
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
                                            <option value="{{$type->id}}">{{$type->name}}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-input number-input" name="transactions[0][amount]" min="0" step="0.01" placeholder="0.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-add-row" onclick="addTransactionRow()">+</button>
                                    </td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="2"><strong>Total Transaction Expenses:</strong></td>
                                    <td id="totalPaidOuts" class="number-input"><strong>$0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <div style="margin-top: 8px; padding: 8px; background: #fff3cd; border-radius: 4px; border: 1px solid #ffeaa7;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 600; color: #856404;">Total Transaction Expenses:</span>
                                <span id="totalTransactionExpenses" style="font-weight: 600; color: #856404; font-size: 1.1rem;">$0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="side-panel-material">
                            <div class="form-group-material">
                                <label class="form-label-material">Date</label>
                                <input type="text" name="report_date" class="form-control-material date-input {{ isset($reportDate) ? 'bg-light' : '' }}" value="{{ isset($reportDate) ? \Carbon\Carbon::parse($reportDate)->format('m-d-Y') : date('m-d-Y') }}" placeholder="MM-DD-YYYY" maxlength="10" required {{ isset($reportDate) ? 'readonly' : '' }}>
                                @if(isset($reportDate))
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-lock me-1"></i>Date pre-selected from multi-step process
                                    </small>
                                @endif
                            </div>
                            <div class="form-group-material">
                                <label class="form-label-material">Weather</label>
                                <input type="text" name="weather" class="form-control-material" placeholder="e.g., Sunny, Rainy">
                            </div>
                            <div class="form-group-material">
                                <label class="form-label-material">Holiday/Special Event</label>
                                <input type="text" name="holiday_event" class="form-control-material" placeholder="Special events">
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
            <div class="section-header-material">
                <i class="bi bi-cash-coin me-2"></i>Revenue Income Tracking
            </div>
            <div class="section-body-material">
                <div class="row">
                    <div class="col-lg-12">
                        <table class="transaction-table" id="revenueTable">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Revenue Type</th>
                                    <th style="width: 35%;">Amount ($)</th>
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
                                        <input type="number" class="form-input revenue-amount" name="revenues[0][amount]" step="0.01" min="0" placeholder="0.00">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-remove" onclick="removeRevenueRow(this)">×</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" id="addRevenueRow" class="btn-add-row" style="margin-top: 10px;">+ Add Revenue Entry</button>
                        <div class="row g-3 mt-3">
                            <div class="col-12 col-md-6">
                                <div class="info-card-material success">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="info-label">Total Revenue Income:</span>
                                        <span id="totalRevenue" class="info-value" style="color: #4caf50;">$0.00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="info-card-material info">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="info-label">Online Platform Revenue:</span>
                                        <span id="onlineRevenue" class="info-value" style="color: #2196f3;">$0.00</span>
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
            <div class="section-header-material">
                <i class="bi bi-calculator me-2"></i>Financial Summary
            </div>
            <div class="section-body-material">
                <div class="row">
                    <div class="col-8">
                        <table class="sales-table">
                            <tr>
                                <td>
                                    <div style="display:flex;justify-content: space-between;align-items: anchor-center;">
                                        <span>Total # of Coupons</span>
                                        <span style="width:30%;"><input type="number" name="total_coupons" value="0" class="form-input number-input" step="0.01" style="background: white;"></span>
                                    </div>
                                </td>
                                <td><strong>Gross Sales:</strong></td>
                                <td><input type="number" name="gross_sales" class="form-input number-input" step="0.01" required></td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="display:flex;justify-content: space-between;align-items: anchor-center;">
                                        <span><strong>Total Amount of Coupons Received:</strong></span>
                                        <span style="width:30%;"><input type="number" name="coupons_received" class="form-input number-input" step="0.01" value="0.00" style="background: white;"></span>
                                    </div>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td></td>
                                <td><strong>Adjustments: Overrings/Returns:</strong></td>
                                <td><input type="number" name="adjustments_overrings" class="form-input number-input" step="0.01" value="0.00"></td>
                            </tr>
                            <tr>
                                <td rowspan="2">
                                    
                                    <div style="display:flex;justify-content: space-between;align-items: anchor-center;">
                                        <span>Total # of Customers</span>
                                        <span style="width:30%;"><input type="number" name="total_customers" value="0" class="form-input number-input" step="0.01" style="background: white;"></span>
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
                                   
                                    <div style="display:flex;justify-content: space-between;align-items: anchor-center;">
                                        <span> Average Ticket</span>
                                        <span style="width:30%;"><input type="number" name="average_ticket" id="averageTicketInput" value="0" class="form-input number-input" step="0.01" style="background: white;" readonly></span>
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
                                <td><strong>Short:</strong></td>
                                <td id="short" class="calculated-field number-input">$0.00</td>
                            </tr>
                            <tr>
                                <td ><strong>Over:</strong></td>
                                <td id="over" class="calculated-field number-input">$0.00</td>
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

// Auto-calculation functions
function calculateTotals() {
    // Get form values
    const grossSales = parseFloat(document.querySelector('input[name="gross_sales"]').value || 0);
    const couponsReceived = parseFloat(document.querySelector('input[name="coupons_received"]').value || 0);
    const adjustmentsOverrings = parseFloat(document.querySelector('input[name="adjustments_overrings"]').value || 0);
    const creditCards = parseFloat(document.querySelector('input[name="credit_cards"]').value || 0);
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

    // Calculate revenue totals for this function
    document.querySelectorAll('#revenueTable tbody tr').forEach(row => {
        const amountInput = row.querySelector('.revenue-amount');
        const selectElement = row.querySelector('select[name*="revenue_income_type_id"]');

        if (amountInput && selectElement && selectElement.selectedIndex > 0) {
            const amount = parseFloat(amountInput.value || 0);
            const selectedOption = selectElement.options[selectElement.selectedIndex];

            if (amount > 0) {
                totalRevenueIncome += amount;

                if (selectedOption && selectedOption.dataset.category === 'online') {
                    onlinePlatformRevenue += amount;
                }
            }
        }
    });

    // Calculate derived values
    // Net sales = sum of revenues (calculated from revenue table above)
    const netSales = totalRevenueIncome;
    
    // Calculate 8.25% sales tax
    const tax = netSales * 0.0825 / 1.0825;
    const salesPreTax = netSales - tax;
    const averageTicket = totalCustomers > 0 ? netSales / totalCustomers : 0;
    
    // Cash to account for = Net Sales - Total Paid Out - Credit Cards - Online Platform Revenue
    const cashToAccountFor = netSales - totalPaidOuts - creditCards - onlinePlatformRevenue;
    
    let short = 0;
    let over = 0;
    if (actualDeposit < cashToAccountFor) {
        short = actualDeposit - cashToAccountFor;
    } else {
        over = actualDeposit - cashToAccountFor;
    }

    // Update display
    document.getElementById('totalPaidOuts').innerHTML = `<strong>$${totalPaidOuts.toFixed(2)}</strong>`;
    document.getElementById('totalTransactionExpenses').textContent = `$${totalPaidOuts.toFixed(2)}`;
    document.getElementById('totalPaidOuts2').textContent = `$${totalPaidOuts.toFixed(2)}`;
    document.getElementById('onlineRevenue2').textContent = `$${onlinePlatformRevenue.toFixed(2)}`;
    document.getElementById('netSales').textContent = `$${netSales.toFixed(2)}`;
    document.getElementById('netSales2').textContent = `$${netSales.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('salesPreTax').textContent = `$${salesPreTax.toFixed(2)}`;
    document.getElementById('averageTicketInput').value = averageTicket.toFixed(2);
    document.getElementById('cashToAccountFor').textContent = `$${cashToAccountFor.toFixed(2)}`;
    document.getElementById('short').textContent = `$${short.toFixed(2)}`;
    document.getElementById('over').textContent = `$${over.toFixed(2)}`;

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
            <input type="number" class="form-input number-input" name="transactions[${transactionCount}][amount]" step="0.01" min="0" placeholder="0.00">
        </td>
        <td>
            <button type="button" class="btn-remove-row" onclick="removeTransactionRow(this)">×</button>
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

// Input validation and formatting
function formatCurrency(input) {
    let value = parseFloat(input.value);
    if (isNaN(value)) value = 0;
    input.value = value.toFixed(2);
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
    
    // Validate gross vs net sales
    if (fieldName === 'net_sales') {
        const grossSales = parseFloat(document.querySelector('input[name="gross_sales"]').value || 0);
        if (value > grossSales) {
            isValid = false;
            message = 'Net sales cannot exceed gross sales';
        }
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
        'input[name="gross_sales"]',
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
            <input type="number" class="form-input revenue-amount" name="revenues[${revenueCount}][amount]" step="0.01" min="0" placeholder="0.00">
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
        const transactionTypeId = selectedOption.getAttribute('data-transaction-type-id');
        const vendorId = selectedValue;
        const vendorName = selectedOption.getAttribute('data-vendor-name');
        
        // Set vendor_id hidden input
        const vendorIdInput = selectElement.closest('tr').querySelector('.vendor-id-input');
        if (vendorIdInput) {
            vendorIdInput.value = vendorId;
        }
        
        // Auto-fill transaction type if vendor has default transaction type
        if (transactionTypeId) {
            const transactionTypeSelect = selectElement.closest('tr').querySelector('.transaction-type-select');
            if (transactionTypeSelect) {
                transactionTypeSelect.value = transactionTypeId;
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