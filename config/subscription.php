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
    | Billing enabled
    |--------------------------------------------------------------------------
    | Master switch for paid subscriptions / Stripe Checkout. While false, the
    | "Subscribe" button is disabled and the checkout flow is hidden — users
    | still get the full free trial. Flip BILLING_ENABLED=true once Stripe
    | (keys, price, webhook) is configured.
    */
    'enabled' => (bool) env('BILLING_ENABLED', false),

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
