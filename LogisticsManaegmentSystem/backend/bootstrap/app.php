<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        apiPrefix: 'api',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The API is stateless and always speaks JSON. Anything unauthenticated
        // must produce a 401 envelope, never a redirect to a login page.
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->api(append: [
            // Every authenticated request re-checks that the account is still
            // active, and the whole surface is rate limited.
            EnsureUserIsActive::class,
            'throttle:api',
        ]);

        // Spatie ships these middleware classes but does not register the route
        // aliases, so `permission:invoice.view` would otherwise be an unknown
        // middleware at boot. Aliasing them here is what lets a route declare
        // the permission it needs, as routes/api.php requires.
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Reverse-proxy trust is configured in AppServiceProvider::boot(),
        // because the config repository is not bound yet at this point.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
        |--------------------------------------------------------------------------
        | API error contract
        |--------------------------------------------------------------------------
        |
        | Every failure leaves the application as:
        |
        |     { "success": false, "message": "...", "errors": { ... } }
        |
        | Controllers and services never build error payloads by hand; this
        | renderer is the single place where an exception becomes a response.
        |
        */

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // 422 - validation
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Validation failed.', $e->status, $e->errors());
        });

        // 401 - unauthenticated
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Unauthenticated. Please sign in again.', 401);
        });

        // 403 - authenticated but not permitted
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage() ?: 'You are not authorized to perform this action.',
                403,
            );
        });

        // 404 - missing record
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::notFound();
        });

        // 405 / 409 / 413 / 422 and any other explicit HTTP exception.
        // A message carried by the exception wins over the generic wording.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = $e->getStatusCode();

            // Symfony's generated exceptions name the concrete route and verb
            // ("The POST method is not supported for route api/v1/ping"), which
            // is internal detail a client has no business seeing. Those get
            // generic wording. A plain HttpException, by contrast, only ever
            // comes from an explicit abort() in application code, so its
            // message is deliberate and safe to surface.
            $generatedBySymfony = $e instanceof MethodNotAllowedHttpException
                || $e instanceof NotAcceptableHttpException;

            $message = ($e instanceof HttpException && ! $generatedBySymfony && $e->getMessage() !== '')
                ? $e->getMessage()
                : match ($status) {
                    405 => 'The HTTP method is not allowed for this endpoint.',
                    409 => 'The request conflicts with the current state of the resource.',
                    413 => 'The uploaded file is too large.',
                    419 => 'The page has expired. Please refresh and try again.',
                    422 => 'The request was well formed but could not be processed.',
                    429 => 'Too many requests. Please slow down and try again shortly.',
                    default => 'The request could not be completed.',
                };

            return ApiResponse::error($message, $status, [], $e->getHeaders());
        });

        // 500 - anything unexpected. The real message never reaches the
        // client unless APP_DEBUG is on, and never in production.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            report($e);

            return ApiResponse::error(
                config('app.debug')
                    ? $e->getMessage()
                    : 'An unexpected error occurred. Please try again or contact support.',
                500,
            );
        });
    })->create();
