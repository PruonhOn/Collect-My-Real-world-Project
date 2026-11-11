<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Driver
    |--------------------------------------------------------------------------
    |
    | The default payment provider used for handling transactions.
    | Supported: "mock", "bakong"
    |
    */

    'driver' => env('PAYMENT_DRIVER', 'mock'),

    'mode' => env('PAYMENT_MODE', 'sandbox'),

    'currency' => 'USD',

    'mock' => [
        'auto_approve' => env('PAYMENT_MOCK_AUTO_APPROVE', true),
    ],

    'bakong' => [
        'base_url' => env('BAKONG_BASE_URL', 'https://api-sandbox-bakong.nbc.gov.kh'),
        'merchant_id' => env('BAKONG_MERCHANT_ID'),
        'client_id' => env('BAKONG_CLIENT_ID'),
        'client_secret' => env('BAKONG_CLIENT_SECRET'),
        'api_key' => env('BAKONG_API_KEY'),
        'callback_url' => env('BAKONG_CALLBACK_URL', 'http://localhost:5173/payment/callback'),
    ],

];
