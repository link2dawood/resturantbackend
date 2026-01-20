@extends('layouts.tabler')

@section('title', 'Daily Report Summary')

@section('content')
<div class="container-xl mt-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0" style="font-family: 'Google Sans', sans-serif; font-size: 1.75rem; font-weight: 400; color: var(--on-surface, #202124);">Daily Report Summary</h1>
            <p class="text-muted mb-0" style="font-family: 'Google Sans', sans-serif; margin-top: 0.25rem;">Compare daily reports across stores</p>
        </div>
    </div>

    @if(!$summaryData)
    <div class="alert alert-info d-flex align-items-center mb-4" role="alert" style="border-radius: 0.75rem; border-left: 4px solid var(--google-blue, #4285f4);">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--google-blue, #4285f4)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="16" x2="12" y2="12"/>
            <line x1="12" y1="8" x2="12.01" y2="8"/>
        </svg>
        <div>You must select stores, data items and select a date range to view report.</div>
    </div>
    @endif

    <form method="GET" action="{{ route('daily-reports.summary') }}" id="summaryForm">
        <!-- Date Range Selection -->
        <div class="card mb-4">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Date Range</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">From:</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">To:</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" name="get_report" value="1" class="btn btn-primary w-100">Get Report</button>
                            <button type="button" onclick="document.getElementById('summaryForm').reset();" class="btn btn-outline-secondary">Clear</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Type Selection -->
        <div class="card mb-4">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Report Type</h3>
            </div>
            <div class="card-body">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="report_type" id="report_type_daily" value="daily" {{ request('report_type', 'totals') === 'daily' ? 'checked' : '' }}>
                    <label class="form-check-label" for="report_type_daily">Daily Breakdown</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="report_type" id="report_type_totals" value="totals" {{ request('report_type', 'totals') === 'totals' ? 'checked' : '' }}>
                    <label class="form-check-label" for="report_type_totals">Totals Breakdown</label>
                </div>
            </div>
        </div>

        <!-- Store Selection -->
        <div class="card mb-4">
            <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Select Stores to Compare</h3>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="checkAllStores" onchange="toggleAllStores(this)">
                    <label class="form-check-label" for="checkAllStores">Check All</label>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($stores as $store)
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input class="form-check-input store-checkbox" type="checkbox" name="store_ids[]" value="{{ $store->id }}" id="store_{{ $store->id }}" {{ in_array($store->id, request('store_ids', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="store_{{ $store->id }}">{{ $store->store_info }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Data Items Selection -->
        <div class="card mb-4">
            <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">Select Data Items to Compare</h3>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="checkAllItems" onchange="toggleAllItems(this)">
                    <label class="form-check-label" for="checkAllItems">Check All</label>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="total_sales" id="item_total_sales" {{ in_array('total_sales', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_total_sales">Total # of Sales</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="average_ticket" id="item_average_ticket" {{ in_array('average_ticket', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_average_ticket">Average Ticket</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="voids" id="item_voids" {{ in_array('voids', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_voids">Voids</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="adjustments" id="item_adjustments" {{ in_array('adjustments', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_adjustments">Adjustments</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="net_sales" id="item_net_sales" {{ in_array('net_sales', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_net_sales">Net Sales</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="cash_to_account" id="item_cash_to_account" {{ in_array('cash_to_account', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_cash_to_account">Cash To Account</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="total_coupons" id="item_total_coupons" {{ in_array('total_coupons', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_total_coupons">Total # of Coupons</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="projected_sales" id="item_projected_sales" {{ in_array('projected_sales', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_projected_sales">Projected Sales</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="gross_sales" id="item_gross_sales" {{ in_array('gross_sales', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_gross_sales">Gross Sales</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="sales_tax" id="item_sales_tax" {{ in_array('sales_tax', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_sales_tax">Sales Tax</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="total_paidout" id="item_total_paidout" {{ in_array('total_paidout', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_total_paidout">Total Paidout</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="actual_deposit" id="item_actual_deposit" {{ in_array('actual_deposit', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_actual_deposit">Actual Deposit</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="total_customers" id="item_total_customers" {{ in_array('total_customers', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_total_customers">Total # of Customers</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="cancels" id="item_cancels" {{ in_array('cancels', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_cancels">Cancels</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="coupons_amount" id="item_coupons_amount" {{ in_array('coupons_amount', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_coupons_amount">Coupons Amount</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="sales" id="item_sales" {{ in_array('sales', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_sales">Sales</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="credit_cards" id="item_credit_cards" {{ in_array('credit_cards', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_credit_cards">Credit Cards</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input data-item-checkbox" type="checkbox" name="data_items[]" value="short_over" id="item_short_over" {{ in_array('short_over', request('data_items', [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="item_short_over">Short Over</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Summary Results -->
    @if($summaryData)
    <div class="card">
        <div class="card-header border-0 pb-0">
            <h3 class="card-title" style="font-family: 'Google Sans', sans-serif; font-size: 1.125rem; font-weight: 500;">
                Summary Report - {{ \Carbon\Carbon::parse(request('from_date'))->format('M d, Y') }} to {{ \Carbon\Carbon::parse(request('to_date'))->format('M d, Y') }}
            </h3>
        </div>
        <div class="card-body">
            @if($summaryData['type'] === 'daily')
                @include('daily-reports.summary-daily', ['summaryData' => $summaryData, 'dataItems' => request('data_items', [])])
            @else
                @include('daily-reports.summary-totals', ['summaryData' => $summaryData, 'dataItems' => request('data_items', [])])
            @endif
        </div>
    </div>
    @endif
</div>

<script>
function toggleAllStores(checkbox) {
    const storeCheckboxes = document.querySelectorAll('.store-checkbox');
    storeCheckboxes.forEach(cb => cb.checked = checkbox.checked);
}

function toggleAllItems(checkbox) {
    const itemCheckboxes = document.querySelectorAll('.data-item-checkbox');
    itemCheckboxes.forEach(cb => cb.checked = checkbox.checked);
}
</script>
@endsection

