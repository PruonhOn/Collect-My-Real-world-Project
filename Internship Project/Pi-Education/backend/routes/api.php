<?php

use App\Http\Controllers\Api\V1\Admin\AdminAuthorController;
use App\Http\Controllers\Api\V1\Admin\AdminBookController;
use App\Http\Controllers\Api\V1\Admin\AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\AdminReviewController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminVipPlanController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ReaderController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\VipController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\Api\V1\WishlistController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Pi-Education API is running.',
            'data' => [
                'app' => config('app.name'),
                'env' => config('app.env'),
                'time' => now()->toIso8601String(),
            ],
        ]);
    });

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,60');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,60');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::get('/books', [BookController::class, 'index']);
    Route::get('/books/{slug}', [BookController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);
    Route::get('/authors', [AuthorController::class, 'index']);
    Route::get('/authors/{slug}', [AuthorController::class, 'show']);

    Route::get('/read/{slug}', [ReaderController::class, 'content']);

    Route::get('/books/{id}/reviews', [ReviewController::class, 'index']);

    Route::get('/vip/plans', [VipController::class, 'plans']);

    Route::get('/stats', [StatsController::class, 'index']);
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,60');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/vip/status', [VipController::class, 'status']);
        Route::post('/vip/subscribe', [VipController::class, 'subscribe']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
        Route::post('/orders/{orderNumber}/pay', [OrderController::class, 'pay']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/password', [ProfileController::class, 'changePassword']);

        Route::get('/my-books', [ReaderController::class, 'myBooks']);
        Route::put('/read/{slug}/progress', [ReaderController::class, 'progress']);

        Route::get('/reviews/mine/{bookId}', [ReviewController::class, 'my']);
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{id}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'store']);
        Route::delete('/wishlist/{bookId}', [WishlistController::class, 'destroy']);
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'stats']);

        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::patch('/admin/users/{id}', [AdminUserController::class, 'update']);

        Route::get('/admin/books', [AdminBookController::class, 'index']);
        Route::get('/admin/books/{id}', [AdminBookController::class, 'show']);
        Route::post('/admin/books', [AdminBookController::class, 'store']);
        Route::put('/admin/books/{id}', [AdminBookController::class, 'update']);
        Route::delete('/admin/books/{id}', [AdminBookController::class, 'destroy']);

        Route::get('/admin/categories', [AdminCategoryController::class, 'index']);
        Route::post('/admin/categories', [AdminCategoryController::class, 'store']);
        Route::put('/admin/categories/{id}', [AdminCategoryController::class, 'update']);
        Route::delete('/admin/categories/{id}', [AdminCategoryController::class, 'destroy']);

        Route::get('/admin/authors', [AdminAuthorController::class, 'index']);
        Route::post('/admin/authors', [AdminAuthorController::class, 'store']);
        Route::put('/admin/authors/{id}', [AdminAuthorController::class, 'update']);
        Route::delete('/admin/authors/{id}', [AdminAuthorController::class, 'destroy']);

        Route::get('/admin/vip/plans', [AdminVipPlanController::class, 'index']);
        Route::post('/admin/vip/plans', [AdminVipPlanController::class, 'store']);
        Route::put('/admin/vip/plans/{id}', [AdminVipPlanController::class, 'update']);
        Route::delete('/admin/vip/plans/{id}', [AdminVipPlanController::class, 'destroy']);

        Route::get('/admin/reviews', [AdminReviewController::class, 'index']);
        Route::patch('/admin/reviews/{id}', [AdminReviewController::class, 'update']);
        Route::delete('/admin/reviews/{id}', [AdminReviewController::class, 'destroy']);
    });
});
