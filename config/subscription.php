<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cashier subscription "type"
    |--------------------------------------------------------------------------
    | The internal name Cashier uses for the subscription on the billable model.
    | A single-plan SaaS keeps one type ('default').
    */
    'type' => env('SUBSCRIPTION_TYPE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Price ID
    |--------------------------------------------------------------------------
    | The recurring monthly Price created in the Stripe dashboard that new
    | subscriptions are billed against.
    */
    'price_id' => env('STRIPE_PRICE_ID'),

    /*
    |--------------------------------------------------------------------------
    | Display name + monthly amount
    |--------------------------------------------------------------------------
    | 'monthly_amount' is in cents and is used for local MRR/churn reporting so
    | the admin dashboard doesn't have to call Stripe for every subscription.
    | Keep it in sync with the Stripe price.
    */
    'plan_name' => env('SUBSCRIPTION_PLAN_NAME', 'Pro'),
    'monthly_amount' => (int) env('SUBSCRIPTION_MONTHLY_AMOUNT', 9900),

];
