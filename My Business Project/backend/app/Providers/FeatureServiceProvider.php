<?php

namespace App\Providers;

use App\Services\Features\FeatureGate;
use Illuminate\Support\ServiceProvider;

class FeatureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeatureGate::class, function ($app) {
            return new FeatureGate();
        });
    }

    public function boot(): void
    {
        //
    }
}
