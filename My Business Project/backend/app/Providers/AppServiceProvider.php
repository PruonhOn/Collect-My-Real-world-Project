<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureNotificationUrls();
    }

    /**
     * Point every account related email at the single page application.
     *
     * The API is never the place where a user has to finish a flow: the
     * frontend receives the token and exchanges it through the API.
     */
    protected function configureNotificationUrls(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }

    /**
     * Define the API rate limiters.
     *
     * The default `api` limiter protects every /api/v1 route from abuse.
     * Authentication routes additionally get a much tighter, per-IP limit so
     * that credential stuffing cannot succeed.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by('auth-ip:'.$request->ip()),
            Limit::perMinute(5)->by('auth-user:'.mb_strtolower((string) $request->input('email'))),
        ]);
    }
}
