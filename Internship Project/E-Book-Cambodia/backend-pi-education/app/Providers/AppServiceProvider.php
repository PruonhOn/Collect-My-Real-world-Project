<?php

namespace App\Providers;

use App\Listeners\NotifyAuthorOnBookStatusChange;
use App\Models\Book;
use Illuminate\Cache\RateLimiting\Limit;
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

        // Hooked to the model event rather than the admin endpoints so every
        // route into the review workflow notifies the author exactly once.
        Book::updated(function (Book $book): void {
            $this->app->make(NotifyAuthorOnBookStatusChange::class)->handle($book);
        });
    }

    /**
     * Named rate limiters.
     *
     * The assistant limiter is keyed by user id rather than IP alone, because
     * AI calls are billed per request and a shared office or NAT IP must not
     * let one account spend everyone's budget. Unauthenticated callers fall
     * back to the IP.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(
            (int) config('ai.rate_limit', 20)
        )->by('ai:'.($request->user()?->getAuthIdentifier() ?: $request->ip())));
    }
}
