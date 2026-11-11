<?php

use App\Jobs\NotifyOverdueWorkOrders;
use Illuminate\Support\Facades\Schedule;

// Run every hour: check for overdue work orders and notify technicians
Schedule::job(new NotifyOverdueWorkOrders)->hourly()->name('notify-overdue');

// Daily midnight: clear expired dashboard cache
Schedule::call(fn() => cache()->forget('dashboard.kpis'))->daily()->name('clear-kpi-cache');
