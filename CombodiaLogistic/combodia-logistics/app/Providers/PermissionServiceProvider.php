<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(PermissionRegistrar::class, function ($app) {
            return new PermissionRegistrar($app);
        });
    }

    public function boot(): void
    {
        //
    }
}
