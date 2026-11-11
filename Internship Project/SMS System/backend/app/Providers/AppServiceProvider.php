<?php

namespace App\Providers;

use App\Services\PaymentGatewayManager;
use App\Services\PaymentGateways\ManualGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function () {
            $manager = new PaymentGatewayManager(new ManualGateway);

            // Future gateway drivers can be registered from services config, e.g.:
            // $manager->register('bakong', new BakongGateway(config('services.bakong')));
            $this->registerConfiguredGateways($manager);

            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                Str::transliterate(Str::lower((string) $request->string('email'))).'|'.$request->ip(),
            );
        });

        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(120)->by($request->user()->getAuthIdentifier())
                : Limit::perMinute(30)->by($request->ip());
        });
    }

    private function registerConfiguredGateways(PaymentGatewayManager $manager): void
    {
        $gateways = config('services.payment_gateways', []);

        foreach ($gateways as $name => $class) {
            if (class_exists($class)) {
                $manager->register($name, app($class));
            }
        }
    }
}
