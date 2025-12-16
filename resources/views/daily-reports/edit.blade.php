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
    
    .table-material tbody tr.total-row {
        background: #e3f2fd;
        font-weight: 500;
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
    }
    
    .btn-add-row:hover {
        background: #1565c0;
    }
    
    .btn-remove-row {
        background: #d32f2f;
        color: white;
        padding: 0.5rem 0.75rem;
        min-width: auto;
        height: auto;
    }
    
    .btn-remove-row:hover {
        background: #c62828;
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
        font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        width: 100%;
        border-collapse: collapse;
    }
    
    .sales-table td {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.12);
        color: rgba(0, 0, 0, 0.87);
    }
    
    .sales-table td:first-child {
        background: #fafafa;
        font-weight: 500;
        color: rgba(0, 0, 0, 0.6);
        width: 60%;
    }
    
    .calculated-field {
        background: #e3f2fd !important;
        color: #1976d2;
        font-weight: 500;
    }
    
    .update-btn {
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
    
    .update-btn:hover {
        background-color: #1565c0;
        box-shadow: 0px 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
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
<div class="container-fluid px-3 px-md-4 px-lg-5 py-4">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="material-headline" style="font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif; font-size: 2rem; font-weight: 400; line-height: 2.5rem; color: #202124; margin: 0;">
                Edit Daily Report
            </h1>
            <p class="material-subtitle" style="font-family: 'Google Sans', -apple-system, BlinkMacSystemFont, sans-serif; font-size: 0.875rem; color: #5f6368; margin: 0.5rem 0 0 0;">
                {{ $dailyReport->report_date->format('M d, Y') }}
            </p>
        </div>
        <a href="{{ route('daily-reports.show', $dailyReport) }}" class="btn btn-material btn-material-outlined" style="background-color: transparent; border: 1px solid rgba(0, 0, 0, 0.12); color: #1976d2; box-shadow: none;">
            <i class="bi bi-x-circle me-2"></i>
            <span>Cancel</span>
        </a>
    </div>
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

        <div class="card-material">
            <!-- Header Section -->
            <div class="card-header-material">
                <div class="card-title-material">{{ $dailyReport->store->store_info ?? 'Store Information' }}</div>
                <div class="card-subtitle-material">
                    <div>{{ $dailyReport->store->address ?? '' }}</div>
                    <div>Phone: {{ $dailyReport->store->phone ?? '' }}</div>
                </div>
            </div>

            <!-- Transaction Expenses Section -->
            <div class="section-header-material">
                <i class="bi bi-receipt me-2"></i>Transaction Expenses
            </div>
            <div class="section-body-material">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="table-responsive">
                            <table class="table-material" id="transactionTable">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Company</th>
                                    <th style="width: 35%;">Transaction Type</th>
                                    <th style="width: 20%;">Amount ($)</th>
                                    <th style="width: 5%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dailyReport->transactions as $index => $transaction)
                                    <tr>
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
                                    <td colspan="2"><strong>Total Paid Outs:</strong></td>
                                    <td id="totalPaidOuts" class="number-input"><strong>$0.00</strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="side-panel-material">
                            <div class="form-group-material">
                                <label class="form-label-material">Date</label>
                                <input type="text" name="report_date" class="form-control-material date-input" value="{{ $dailyReport->report_date->format('m-d-Y') }}" placeholder="MM-DD-YYYY" maxlength="10" required>
                            </div>
                            <div class="form-group-material">
                                <label class="form-label-material">Weather</label>
                                <input type="text" name="weather" class="form-control-material" value="{{ $dailyReport->weather }}">
                            </div>
                            <div class="form-group-material">
                                <label class="form-label-material">Holiday/Special Event</label>
                                <input type="text" name="holiday_event" class="form-control-material" value="{{ $dailyReport->holiday_event }}">
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
            <div class="section-header-material">
                <i class="bi bi-cash-coin me-2"></i>Revenue Income Tracking
            </div>
            <div class="section-body-material">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <table class="table-material" id="revenueTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50%;">Revenue Type</th>
                                        <th style="width: 35%;">Amount ($)</th>
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
                                            <button type="button" class="btn-add-row" onclick="addRevenueRow()">+</button>
                                        </td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>
                        </div>
                        <div class="row g-3 mt-3">
                            <div class="col-12 col-md-6">
                                <div class="info-card-material success">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="info-label">Total Revenue Entries:</span>
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
            <div class="section-header-material">
                <i class="bi bi-calculator me-2"></i>Financial Summary
            </div>
            <div class="section-body-material">
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
                    <button type="submit" class="btn btn-material btn-material-primary">
                        <i class="bi bi-save me-2"></i>
                        <span>Update Daily Report</span>
                    </button>
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