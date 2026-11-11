<?php

return [

    'allow_all_sessions' => filter_var(env('ALLOW_ALL_SESSIONS', false), FILTER_VALIDATE_BOOLEAN),

    'features' => [
        'allow_all_sessions' => filter_var(env('ALLOW_ALL_SESSIONS', false), FILTER_VALIDATE_BOOLEAN),
        'api_throttling' => filter_var(env('LOGISTICS_API_THROTTLING', true), FILTER_VALIDATE_BOOLEAN),
        'document_upload' => filter_var(env('LOGISTICS_DOCUMENT_UPLOAD', true), FILTER_VALIDATE_BOOLEAN),
        'shipment_tracking' => filter_var(env('LOGISTICS_SHIPMENT_TRACKING', true), FILTER_VALIDATE_BOOLEAN),
        'invoice_generation' => filter_var(env('LOGISTICS_INVOICE_GENERATION', true), FILTER_VALIDATE_BOOLEAN),
        'customer_portal' => filter_var(env('LOGISTICS_CUSTOMER_PORTAL', true), FILTER_VALIDATE_BOOLEAN),
        'advanced_reporting' => filter_var(env('LOGISTICS_ADVANCED_REPORTING', false), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reverse proxy trust
    |--------------------------------------------------------------------------
    |
    | Behind Nginx / Cloudflare / an AWS load balancer, the immediate peer is
    | the proxy, not the client. Without this, $request->ip() returns the
    | proxy address, which silently breaks IP rate limiting and audit logs.
    |
    | null  = do not trust proxies (correct for `php artisan serve`)
    | '*'   = trust any upstream (only safe when the app is not directly
    |         reachable from the internet)
    | array = explicit trusted proxy IPs / CIDR ranges
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | A token is a bearer credential, so the only thing standing between an
    | attacker and the account is how hard the login endpoint is to guess at.
    | The limiter is keyed on the submitted email *and* the client IP together:
    | keying on the email alone lets one attacker lock a colleague out, and
    | keying on the IP alone means a botnet gets unlimited attempts.
    |
    | Login lockout answers 429 with a Retry-After header; the SPA surfaces the
    | message verbatim, so it has to read as an instruction rather than a
    | generic error.
    |
    */

    'auth' => [
        'login_max_attempts' => (int) env('AUTH_LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_seconds' => (int) env('AUTH_LOGIN_DECAY_SECONDS', 60),
        // Must match the "expires in 60 minutes" copy on the frontend.
        'password_reset_expires_minutes' => 60,
        // Email verification links expire after this window.
        'email_verification_expires_minutes' => (int) env('AUTH_EMAIL_VERIFICATION_EXPIRES', 60),
        // The SPA's public origin, used for redirects after an emailed link or
        // the Google callback. The API never assumes it shares a host with the SPA.
        'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),
        // One-time Google login code lifetime, in seconds. The code crosses a
        // browser redirect, so it is deliberately short and single-use.
        'google_login_code_ttl' => 120,
        // Token lifetime, in minutes. 720 = 12 hours.
        'token_expiration_minutes' => (int) env('SANCTUM_TOKEN_EXPIRATION', 720),
    ],

    /*
    |--------------------------------------------------------------------------
    | API throttling
    |--------------------------------------------------------------------------
    |
    | Applies to every /api route. Authenticated callers are limited per user id
    | so that several staff behind one office NAT share a budget rather than
    | locking each other out; anonymous callers fall back to their IP.
    |
    */

    'api_rate_limit' => (int) env('API_RATE_LIMIT', 120),
    'api_rate_limit_decay' => (int) env('API_RATE_LIMIT_DECAY', 1),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Every listing endpoint is server-side paginated. The allowed sizes are a
    | whitelist rather than a free integer so that a client cannot request
    | 100,000 rows and exhaust memory.
    |
    */

    'pagination' => [
        'default_per_page' => 20,
        'per_page_options' => [20, 50, 100],
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document number sequences
    |--------------------------------------------------------------------------
    |
    | Numbering is config-driven, never hard-coded in a service. Format is
    | {prefix}-{year}-{sequence padded to `padding`}. The year comes from the
    | current date, so each year starts a fresh sequence.
    |
    | Sequences are allocated with a row-level lock inside a transaction, and
    | every target column also carries a unique index as a hard backstop.
    |
    */

    'numbering' => [
        'booking' => [
            'prefix' => 'BK',
            'padding' => 6,
        ],
        'shipment' => [
            'prefix' => 'OPWT',
            'padding' => 6,
        ],
        'invoice' => [
            'prefix' => 'INV',
            'padding' => 6,
        ],
        'payment' => [
            'prefix' => 'PAY',
            'padding' => 6,
        ],
        'expense' => [
            'prefix' => 'EXP',
            'padding' => 6,
        ],
        'delivery' => [
            'prefix' => 'DLV',
            'padding' => 6,
        ],
        'customer' => [
            'prefix' => 'CUS',
            'padding' => 5,
        ],
        'shipper' => [
            'prefix' => 'SHP',
            'padding' => 5,
        ],
        'consignee' => [
            'prefix' => 'CNG',
            'padding' => 5,
        ],
        'carrier' => [
            'prefix' => 'CAR',
            'padding' => 4,
        ],
        'port' => [
            'prefix' => 'PRT',
            'padding' => 4,
        ],
        'warehouse' => [
            'prefix' => 'WHS',
            'padding' => 4,
        ],
        'factory' => [
            'prefix' => 'FAC',
            'padding' => 4,
        ],
        'truck' => [
            'prefix' => 'TRK',
            'padding' => 4,
        ],
        'driver' => [
            'prefix' => 'DRV',
            'padding' => 4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Finance
    |--------------------------------------------------------------------------
    |
    | Money never touches PHP floats. Amounts are decimal(15,4) in MySQL,
    | arithmetic runs through bcmath, and Eloquent casts hydrate them as
    | strings. The scale below is the working precision; output is rounded to
    | the currency's minor units.
    |
    */

    'finance' => [
        'scale' => 4,
        'base_currency' => env('BASE_CURRENCY', 'USD'),
        'default_tax_id' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Operational thresholds
    |--------------------------------------------------------------------------
    |
    | Drives the scheduled monitoring jobs and the dashboard "upcoming" and
    | "delayed" panels.
    |
    */

    'operations' => [
        // An ETA within this window raises an "ETA approaching" notification.
        'eta_approaching_days' => 3,
        // An ETA in the past with the shipment not yet arrived is delayed.
        'delay_threshold_days' => 0,
        // A driver licence expiring inside this window raises a warning.
        'licence_expiry_warning_days' => 30,
        // Default invoicing terms in days when a customer has no terms set.
        'default_payment_terms_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Documents
    |--------------------------------------------------------------------------
    |
    | Shipment documents are private. They are streamed through an authorised
    | endpoint and are never reachable by a direct public URL.
    |
    */

    'documents' => [
        'disk' => env('DOCUMENTS_DISK', 'local'),
        'max_upload_kb' => 20480,          // 20 MB
        // A certificate inside this window is "needs attention" rather than "fine".
        // 30 days because that is roughly the notice a reissued insurance
        // certificate or a renewed origin certificate actually takes.
        'expiry_warning_days' => 30,
        'allowed_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
        ],
        'max_versions_per_document' => 10,
    ],

];
