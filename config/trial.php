<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Free trial length (days)
    |--------------------------------------------------------------------------
    | How long a self-serve signup gets before the workspace is locked out.
    */
    'days' => (int) env('TRIAL_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | "Expiring soon" reminder window (days)
    |--------------------------------------------------------------------------
    | The trials:check command emails the client a reminder this many days
    | before their trial ends.
    */
    'expiring_soon_days' => (int) env('TRIAL_EXPIRING_SOON_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Sales team inbox
    |--------------------------------------------------------------------------
    | Where new-signup, trial-expiry and "request to continue" alerts are sent.
    */
    'sales_email' => env('SALES_TEAM_EMAIL', 'sales@example.com'),

];
