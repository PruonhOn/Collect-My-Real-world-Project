<?php

namespace App\Providers;

use DateInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         * Spatie v8 reads the permission cache lifetime from config when the
         * registrar is first resolved during boot. A short production TTL stops
         * a revoked permission from surviving until the 24-hour default expires.
         */
        if ($this->app->isProduction()) {
            config([
                'permission.cache.expiration_time' => DateInterval::createFromDateString(
                    max(1, (int) env('PERMISSION_CACHE_TTL', 5)).' minutes'
                ),
            ]);
        }
    }

    public function boot(): void
    {
        $this->registerRateLimiters();

        // Every API date is ISO-8601 with an explicit offset. Ambiguous
        // locale formats (dd/mm vs mm/dd) are a permanent source of
        // off-by-one-month bugs in logistics reporting.
        Date::use(Carbon::class);
        Date::serializeUsing(fn (\DateTimeInterface $date) => $date->format(\DateTimeInterface::ATOM));

        /*
         * Guard against N+1 queries. In non-production a lazy load throws an
         * exception instead of silently issuing one query per row, so a
         * missing eager load is caught by the test suite rather than in
         * production query logs.
         */
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes(! $this->app->isProduction());

        Password::defaults(fn () => Password::min(10)->letters()->numbers()->symbols());

        $allSessions = config('logistics.features.allow_all_sessions', false);

        if ($allSessions) {
            Gate::before(fn () => true);
        }

        $this->trustReverseProxies();
    }

    /**
     * The blanket limiter applied to every /api route as `throttle:api`.
     *
     * Registered in boot() rather than register(): `RateLimiter::for()` resolves
     * the cache repository to build its instance, and the cache binding is not
     * in place yet while providers are still being registered.
     *
     * Keyed on the user id once authenticated, so several staff sharing one
     * office NAT draw on a single budget per person rather than locking each
     * other out, and so a revoked token stops consuming anything immediately.
     * Anonymous callers - chiefly the sign-in endpoint - fall back to their IP.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $max = (int) config('logistics.api_rate_limit', 120);
            $decay = (int) config('logistics.api_rate_limit_decay', 1);

            $key = $request->user()?->getAuthIdentifier() ?: $request->ip();

            return new Limit((string) $key, $max, $decay);
        });
    }

    /**
     * Behind Nginx / Cloudflare / a load balancer the immediate TCP peer is the
     * proxy, not the client. Without this, $request->ip() returns the proxy
     * address, which silently breaks IP rate limiting and audit-log capture.
     *
     * Configured here rather than in bootstrap/app.php because the config
     * repository is not bound while middleware is being assembled.
     */
    private function trustReverseProxies(): void
    {
        $proxies = config('logistics.trusted_proxies');

        if ($proxies === null || $proxies === '') {
            return;
        }

        Request::setTrustedProxies(
            at: is_string($proxies) ? $proxies : (array) $proxies,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );
    }
}
