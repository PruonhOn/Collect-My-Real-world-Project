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
];
