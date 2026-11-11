<?php

return [

    /*
    | Payment gateway driver.
    |
    | 'mock'   → No external provider. Purchases are approved instantly
    |            (or require admin approval for acleda/wing methods).
    | 'stripe' → Real payments via Stripe Checkout. Requires STRIPE_SECRET_KEY
    |            (and STRIPE_WEBHOOK_SECRET for the webhook endpoint).
    */
    'driver' => env('PAYMENT_DRIVER', 'mock'),

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
    ],

    'bakong' => [
        'base_url' => env('BAKONG_API_URL', 'https://api-bakong.nbc.gov.kh'),
        'token' => env('BAKONG_API_TOKEN'),
        'account_id' => env('BAKONG_ACCOUNT_ID'),
        'merchant_name' => env('BAKONG_MERCHANT_NAME', 'PI Education Cambodia'),
        'merchant_city' => env('BAKONG_MERCHANT_CITY', 'Phnom Penh'),
        'currency' => env('BAKONG_CURRENCY', 'USD'),
    ],
];
