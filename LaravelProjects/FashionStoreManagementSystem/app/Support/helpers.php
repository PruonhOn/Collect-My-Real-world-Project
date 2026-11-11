<?php

use App\Models\Setting;

if (! function_exists('app_setting')) {
    function app_setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return app_setting('currency', '$') ?? '$';
    }
}

if (! function_exists('money')) {
    function money(float|string|int|null $amount): string
    {
        return currency_symbol().' '.number_format((float) $amount, 2);
    }
}

if (! function_exists('store_name')) {
    function store_name(): string
    {
        return app_setting('store_name', 'Fashion Store') ?? 'Fashion Store';
    }
}

if (! function_exists('store_logo_url')) {
    function store_logo_url(): ?string
    {
        $path = app_setting('store_logo');

        return $path ? asset('storage/'.$path) : null;
    }
}

if (! function_exists('tax_rate')) {
    function tax_rate(): float
    {
        return (float) (app_setting('tax_rate', '0') ?? 0);
    }
}
