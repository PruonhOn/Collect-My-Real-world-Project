<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public
        Route::middleware('throttle:auth')->group(function () {
            Route::post('/auth/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
            Route::post('/auth/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
        });
        Route::get('/plans', [\App\Http\Controllers\Api\PlanPublicController::class, 'index']);

        // Email verification
        Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Api\Auth\VerificationController::class, 'verify'])->name('verification.verify');

        // Password reset
        Route::post('/auth/forgot-password', [\App\Http\Controllers\Api\Auth\PasswordResetController::class, 'sendResetLink'])->middleware('throttle:auth');
        Route::post('/auth/reset-password', [\App\Http\Controllers\Api\Auth\PasswordResetController::class, 'reset'])->middleware('throttle:auth');

        // Invitations
        Route::get('/invitations/{token}', [\App\Http\Controllers\Api\InvitationController::class, 'show']);

    // Webhooks
    Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\WebhookController::class, 'stripe']);
    Route::post('/webhooks/bakong', [\App\Http\Controllers\Api\WebhookController::class, 'bakong']);
    Route::post('/webhooks/aba', [\App\Http\Controllers\Api\WebhookController::class, 'aba']);

    // Development/testing webhook for the deterministic fake gateway. It is
    // never registered in a production environment.
    if (app()->environment(['local', 'testing'])) {
        Route::post('/webhooks/fake', [\App\Http\Controllers\Api\WebhookController::class, 'fake']);
    }

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user()->load('ownedBusinesses', 'memberships');
        });
        Route::post('/auth/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::post('/email/verification-notification', [\App\Http\Controllers\Api\Auth\VerificationController::class, 'send']);
        Route::post('/invitations/{token}/accept', [\App\Http\Controllers\Api\InvitationController::class, 'accept']);
        Route::post('/businesses', [\App\Http\Controllers\Api\BusinessController::class, 'store']);
        Route::get('/businesses', [\App\Http\Controllers\Api\BusinessController::class, 'index']);
        Route::get('/businesses/{business}', [\App\Http\Controllers\Api\BusinessController::class, 'show']);
        Route::put('/businesses/{business}', [\App\Http\Controllers\Api\BusinessController::class, 'update']);
        Route::post('/businesses/switch', [\App\Http\Controllers\Api\BusinessController::class, 'switch']);
        Route::post('/businesses/{business}/invite', [\App\Http\Controllers\Api\BusinessController::class, 'invite']);

        // Business workspace
        Route::middleware('business.context')->prefix('business')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\Business\BusinessDashboardController::class, 'index']);
            Route::apiResource('products', \App\Http\Controllers\Api\Business\ProductController::class);
            Route::apiResource('customers', \App\Http\Controllers\Api\Business\CustomerController::class);
            Route::apiResource('orders', \App\Http\Controllers\Api\Business\OrderController::class);
            Route::apiResource('invoices', \App\Http\Controllers\Api\Business\InvoiceController::class);
            Route::apiResource('team', \App\Http\Controllers\Api\Business\TeamController::class);
            Route::apiResource('branches', \App\Http\Controllers\Api\Business\BranchController::class)->middleware('feature.enabled:multi_branch');
            Route::get('/exports', [\App\Http\Controllers\Api\Business\ExportController::class, 'index']);
            Route::get('/exports/{resource}', [\App\Http\Controllers\Api\Business\ExportController::class, 'show']);
            Route::apiResource('api-tokens', \App\Http\Controllers\Api\Business\ApiTokenController::class)
                ->except(['show', 'update'])
                ->parameters(['api-tokens' => 'token'])
                ->middleware('feature.enabled:api_access');
            Route::get('/reports/sales', [\App\Http\Controllers\Api\Business\ReportController::class, 'sales'])->middleware('feature.enabled:advanced_reports');
            Route::get('/reports/profit', [\App\Http\Controllers\Api\Business\ReportController::class, 'profit'])->middleware('feature.enabled:profit_reports');
            Route::get('/billing', [\App\Http\Controllers\Api\Business\BillingController::class, 'index']);
            Route::post('/billing/checkout', [\App\Http\Controllers\Api\Business\BillingController::class, 'checkout']);
            Route::post('/billing/confirm', [\App\Http\Controllers\Api\Business\BillingController::class, 'confirm']);
            Route::post('/billing/portal', [\App\Http\Controllers\Api\Business\BillingController::class, 'portal']);
            Route::get('/billing/invoices', [\App\Http\Controllers\Api\Business\BillingController::class, 'invoices']);
            Route::get('/settings', [\App\Http\Controllers\Api\BusinessController::class, 'settings']);
            Route::put('/settings', [\App\Http\Controllers\Api\BusinessController::class, 'updateSettings']);
            Route::get('/invoices/{invoice}/pdf', [\App\Http\Controllers\Api\Business\InvoiceController::class, 'pdf']);
        });

        // Platform admin
        Route::middleware('super.admin')->prefix('admin')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\AdminDashboardController::class, 'index']);
            // Administrators manage existing records. Accounts and businesses
            // are only ever created through their own onboarding flows.
            Route::apiResource('users', \App\Http\Controllers\Api\Admin\UserController::class)->except(['store']);
            Route::apiResource('businesses', \App\Http\Controllers\Api\Admin\BusinessAdminController::class)->except(['store']);
            Route::apiResource('plans', \App\Http\Controllers\Api\Admin\PlanController::class);
            Route::apiResource('coupons', \App\Http\Controllers\Api\Admin\CouponController::class);
            Route::apiResource('subscriptions', \App\Http\Controllers\Api\Admin\SubscriptionController::class)->except(['store', 'destroy']);
            Route::get('/payments', [\App\Http\Controllers\Api\Admin\PaymentController::class, 'index']);
            Route::post('/impersonate/{user}', [\App\Http\Controllers\Api\Admin\ImpersonationController::class, 'start']);
            Route::post('/impersonate/stop', [\App\Http\Controllers\Api\Admin\ImpersonationController::class, 'stop']);
            Route::post('/businesses/{business}/suspend', [\App\Http\Controllers\Api\Admin\BusinessAdminController::class, 'suspend']);
            Route::post('/businesses/{business}/unsuspend', [\App\Http\Controllers\Api\Admin\BusinessAdminController::class, 'unsuspend']);
        });
    });
});

// External API (business scoped tokens)
Route::prefix('v1/external')->middleware(['api.token'])->group(function () {
    Route::get('/products', [\App\Http\Controllers\Api\External\ExternalApiController::class, 'products'])->middleware('api.token:products:read');
    Route::get('/customers', [\App\Http\Controllers\Api\External\ExternalApiController::class, 'customers'])->middleware('api.token:customers:read');
    Route::get('/orders', [\App\Http\Controllers\Api\External\ExternalApiController::class, 'orders'])->middleware('api.token:orders:read');
    Route::post('/orders', [\App\Http\Controllers\Api\External\ExternalApiController::class, 'storeOrder'])->middleware('api.token:orders:write');
});