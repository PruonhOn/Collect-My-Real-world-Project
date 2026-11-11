<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS Application Configuration
    |--------------------------------------------------------------------------
    */

    'payment_gateway' => env('PAYMENT_GATEWAY', 'manual'),

    'storage_disk' => env('FILESYSTEM_DISK', 'local'),

    'reports' => [
        'max_rows' => env('REPORT_MAX_ROWS', 2000),
    ],

];
