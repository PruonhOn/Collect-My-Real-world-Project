<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default gateway
    |--------------------------------------------------------------------------
    |
    | The gateway used when a business starts a checkout. In production this
    | should be a real provider (for example "stripe"). The "fake" gateway is
    | a deterministic, offline implementation used for local development and
    | automated tests. It never activates a paid subscription by itself.
    |
    */

    'default_gateway' => env('BILLING_DEFAULT_GATEWAY', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | Trial
    |--------------------------------------------------------------------------
    */

    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 14),

    /*
    |--------------------------------------------------------------------------
    | Grace period
    |--------------------------------------------------------------------------
    |
    | Number of days a past-due subscription keeps its paid entitlements while
    | the platform retries collection.
    |
    */

    'grace_period_days' => (int) env('BILLING_GRACE_PERIOD_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | Usage nudge threshold
    |--------------------------------------------------------------------------
    |
    | Percentage of a quota at which the UI starts showing an upgrade prompt.
    |
    */

    'usage_nudge_threshold' => (int) env('BILLING_USAGE_NUDGE_THRESHOLD', 80),

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    */

    'gateways' => [

        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'price_ids' => [
                'monthly' => env('STRIPE_PRICE_PRO_MONTHLY'),
                'yearly' => env('STRIPE_PRICE_PRO_YEARLY'),
            ],
        ],

        'bakong_khqr' => [
            'merchant_id' => env('BAKONG_MERCHANT_ID'),
            'secret_key' => env('BAKONG_SECRET_KEY'),
            'app_id' => env('BAKONG_APP_ID'),
            'base_url' => env('BAKONG_BASE_URL', 'https://api.bakong.com.kh'),
        ],

        'aba_payway' => [
            'merchant_id' => env('ABA_MERCHANT_ID'),
            'private_key' => env('ABA_PRIVATE_KEY'),
            'public_key' => env('ABA_PUBLIC_KEY'),
            'ipn_hash' => env('ABA_IPN_HASH'),
            'base_url' => env('ABA_BASE_URL', 'https://checkout.payway.com.kh'),
        ],

    ],

];
