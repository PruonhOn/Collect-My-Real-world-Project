<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\LibraryController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ReaderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SharingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Email verification. Named route so the framework's VerifyEmail
    // notification can build a signed verification link.
    Route::get('/email/verify/{id}', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify');

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{book}', [BookController::class, 'show']);

    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);

    // Public reader (free books accessible without login)
    Route::get('/reader/{book}', [ReaderController::class, 'read']);
    Route::get('/reader/{book}/stream', [ReaderController::class, 'stream']);

    Route::get('/books/{book}/reviews', [ReviewController::class, 'index']);

    // Payment gateway webhook (Stripe). Signature verified inside the handler;
    // the endpoint refuses payloads when STRIPE_WEBHOOK_SECRET is unset.
    Route::post('/webhooks/stripe', [PaymentWebhookController::class, 'handle']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendNotification']);

        // Purchase + payment
        Route::post('/books/{book}/purchase', [PaymentController::class, 'purchase']);
        Route::post('/courses/{course}/purchase', [PaymentController::class, 'purchaseCourse']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);

        // Library
        Route::get('/library', [LibraryController::class, 'index']);
        Route::get('/library/{book}', [LibraryController::class, 'show']);

        // Reader (authenticated features: progress, bookmarks)
        Route::post('/reader/{book}/progress', [ReaderController::class, 'updateProgress']);
        Route::get('/reader/{book}/progress', [ReaderController::class, 'getProgress']);
        Route::get('/reader/{book}/bookmarks', [ReaderController::class, 'indexBookmarks']);
        Route::post('/reader/{book}/bookmarks', [ReaderController::class, 'storeBookmark']);
        Route::delete('/reader/bookmarks/{bookmark}', [ReaderController::class, 'destroyBookmark']);

        // Sharing
        Route::post('/books/{book}/share', [SharingController::class, 'generate']);
        Route::post('/redeem', [SharingController::class, 'redeem']);
        Route::get('/sharing', [SharingController::class, 'index']);
        Route::delete('/sharing/{sharedAccess}', [SharingController::class, 'revoke']);

        // Reviews
        Route::post('/books/{book}/reviews', [ReviewController::class, 'store']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

        // Admin
        Route::middleware('role:admin')->prefix('/admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard']);

            Route::get('/users', [AdminController::class, 'users']);
            Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole']);
            Route::delete('/users/{user}', [AdminController::class, 'destroyUser']);

            Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
            Route::get('/books', [BookController::class, 'adminIndex']);
            Route::apiResource('books', BookController::class)->except(['index', 'show']);
            Route::apiResource('courses', CourseController::class)->except(['index', 'show']);

            Route::get('/payments', [AdminController::class, 'payments']);
            Route::post('/payments/{payment}/approve', [AdminController::class, 'approvePayment']);
            Route::get('/sharing-keys', [AdminController::class, 'sharingKeys']);

            Route::get('/reviews', [ReviewController::class, 'adminIndex']);
            Route::patch('/reviews/{review}', [ReviewController::class, 'updateStatus']);
            Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
        });
    });
});
