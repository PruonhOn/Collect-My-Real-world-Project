<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Billing lifecycle reconciliation and enforcement.
Schedule::command('subscriptions:expire')->hourly();
Schedule::command('subscriptions:grace-period')->hourly();
Schedule::command('subscriptions:expire-trials')->hourly();
Schedule::command('subscriptions:reconcile')->everyFourHours();

