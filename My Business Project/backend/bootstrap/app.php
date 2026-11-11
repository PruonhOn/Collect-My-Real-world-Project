<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'business.context' => \App\Http\Middleware\EnsureBusinessContext::class,
            'feature.enabled' => \App\Http\Middleware\EnsureFeatureEnabled::class,
            'super.admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
            'business.member' => \App\Http\Middleware\BusinessMembershipMiddleware::class,
            'api.token' => \App\Http\Middleware\AuthenticateApiToken::class,
        ]);

        // API routes are rate limited by default (see AppServiceProvider).
        $middleware->api(prepend: [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ]);

        // Unauthenticated requests to the SPA API must not be redirected to a
        // login page; they must receive a 401 JSON response.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Never leak internal exception details or stack traces to API clients.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], $e->status);
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->json(['message' => 'Resource not found.'], 404);
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $payload = [
                'message' => match ($status) {
                    403 => $e->getMessage() ?: 'Forbidden.',
                    404 => $e->getMessage() ?: 'Resource not found.',
                    419 => 'CSRF token mismatch.',
                    429 => $e->getMessage() ?: 'Too many requests.',
                    default => 'Server error.',
                },
            ];

            if (config('app.debug') && $status === 500) {
                $payload['debug'] = [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ];
            }

            return response()->json($payload, $status);
        });
    })->create();

