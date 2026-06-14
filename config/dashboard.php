<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Circular metric cost categories
    |--------------------------------------------------------------------------
    | Chart-of-account codes that make up each cost metric. Expenses are matched
    | to these via expense_transactions.coa_id → chart_of_accounts.account_code.
    | (Food = COGS food/beverage/packaging; Payroll = wages + payroll taxes;
    | Rent = occupancy.)
    */
    'coa' => [
        'food' => ['5100', '5200', '5300'],
        'payroll' => ['6600', '6610'],
        'rent' => ['6500'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Target cost percentages (of net sales)
    |--------------------------------------------------------------------------
    | The projection each cost metric is measured against. Variance = actual % −
    | target %; under target is "ahead" (good), over target is "behind".
    */
    'targets' => [
        'food' => (float) env('DASHBOARD_TARGET_FOOD_PCT', 30),
        'payroll' => (float) env('DASHBOARD_TARGET_PAYROLL_PCT', 30),
        'rent' => (float) env('DASHBOARD_TARGET_RENT_PCT', 10),
    ],

];
