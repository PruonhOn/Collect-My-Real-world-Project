<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The React SPA is served from a separate origin (e.g. Vite dev server or
    | the production static host). These settings allow the browser to call
    | the API. In production, restrict `allowed_origins` to your real domains
    | via the `CORS_ALLOWED_ORIGINS` environment variable.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
