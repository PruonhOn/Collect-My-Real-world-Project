<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Liveness / readiness probe.
 *
 * Confirms the full request pipeline works: routing, middleware, the response
 * envelope, and both the database and cache connections. Used by the health
 * check in Docker and by the deployment smoke test.
 */
class PingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'status' => 'ok',
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'api_version' => 'v1',
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'time' => now()->toIso8601String(),
        ], 'Service is healthy.');
    }

    /**
     * Deep check. Verifies that the database and cache backends actually answer,
     * not merely that PHP booted.
     */
    public function health(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::connection()->getPdo() !== null),
            'cache' => $this->check(function () {
                $key = 'healthcheck:'.random_int(1, PHP_INT_MAX);
                cache()->put($key, 'ok', 10);

                return cache()->pull($key) === 'ok';
            }),
        ];

        $healthy = true;
        foreach ($checks as $check) {
            if (isset($check['ok']) && $check['ok'] === false) {
                $healthy = false;
                break;
            }
        }

        return ApiResponse::success(
            ['checks' => $checks],
            $healthy ? 'All dependencies are healthy.' : 'One or more dependencies are unhealthy.',
            $healthy ? 200 : 503,
        );
    }

    public function version(): JsonResponse
    {
        return ApiResponse::success([
            'api_version' => 'v1',
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
        ], 'Version information.');
    }

    private function check(callable $probe): array
    {
        try {
            return ['ok' => (bool) $probe()];
        } catch (Throwable $e) {
            report($e);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
