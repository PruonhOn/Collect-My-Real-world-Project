<?php

use App\Http\Controllers\Api\Admin\AdminAuthorController;
use App\Http\Controllers\Api\Admin\AdminBookController;
use App\Http\Controllers\Api\Admin\AdminCategoryController;
use App\Http\Controllers\Api\Admin\AdminContactController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminPurchaseController;
use App\Http\Controllers\Api\Admin\AdminReviewController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReaderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ============ Public Routes ============

    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::get('books', [BookController::class, 'index']);
    Route::get('books/{slug}', [BookController::class, 'show']);
    Route::get('books/{slug}/related', [BookController::class, 'related']);
    Route::get('books/{id}/read', [BookController::class, 'read']);
    Route::get('books/{id}/sample', [BookController::class, 'sample']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{slug}', [CategoryController::class, 'show']);

    Route::get('authors', [AuthorController::class, 'index']);
    Route::get('authors/{slug}', [AuthorController::class, 'show']);

    Route::post('contact', [ContactController::class, 'store']);

    // ============ Authenticated Routes ============

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('auth/password', [AuthController::class, 'changePassword']);

        Route::get('dashboard/my', [DashboardController::class, 'my']);

        Route::get('my/messages', [ContactController::class, 'myMessages']);

        Route::get('my/purchases', [PurchaseController::class, 'index']);
        Route::post('purchases', [PurchaseController::class, 'store']);
        Route::get('payments/{reference}/verify', [PaymentController::class, 'verify']);

        Route::post('books/{id}/bookmark', [BookController::class, 'toggleBookmark']);
        Route::get('my/bookmarks', [BookController::class, 'bookmarks']);
        Route::post('books/{id}/review', [BookController::class, 'storeReview']);

        Route::post('reader/{bookId}/progress', [ReaderController::class, 'saveProgress']);
        Route::get('reader/{bookId}/progress', [ReaderController::class, 'getProgress']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead']);
    });

    // ============ Admin Routes ============

    Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        Route::get('users', [AdminUserController::class, 'index']);
        Route::get('users/{id}', [AdminUserController::class, 'show']);
        Route::put('users/{id}', [AdminUserController::class, 'update']);
        Route::patch('users/{id}/toggle-active', [AdminUserController::class, 'toggleActive']);
        Route::delete('users/{id}', [AdminUserController::class, 'destroy']);

        Route::get('books', [AdminBookController::class, 'index']);
        Route::get('books/{id}', [AdminBookController::class, 'show']);
        Route::post('books', [AdminBookController::class, 'store']);
        Route::put('books/{id}', [AdminBookController::class, 'update']);
        Route::patch('books/{id}/toggle-publish', [AdminBookController::class, 'togglePublish']);
        Route::delete('books/{id}', [AdminBookController::class, 'destroy']);

        Route::get('categories', [AdminCategoryController::class, 'index']);
        Route::post('categories', [AdminCategoryController::class, 'store']);
        Route::put('categories/{id}', [AdminCategoryController::class, 'update']);
        Route::patch('categories/{id}/toggle-active', [AdminCategoryController::class, 'toggleActive']);
        Route::delete('categories/{id}', [AdminCategoryController::class, 'destroy']);

        Route::get('authors', [AdminAuthorController::class, 'index']);
        Route::post('authors', [AdminAuthorController::class, 'store']);
        Route::put('authors/{id}', [AdminAuthorController::class, 'update']);
        Route::delete('authors/{id}', [AdminAuthorController::class, 'destroy']);

        Route::get('purchases', [AdminPurchaseController::class, 'index']);
        Route::patch('purchases/{id}/approve', [AdminPurchaseController::class, 'approve']);
        Route::patch('purchases/{id}/refund', [AdminPurchaseController::class, 'refund']);

        Route::get('reviews', [AdminReviewController::class, 'index']);
        Route::delete('reviews/{id}', [AdminReviewController::class, 'destroy']);

        Route::get('contacts', [AdminContactController::class, 'index']);
        Route::get('contacts/{id}', [AdminContactController::class, 'show']);
        Route::patch('contacts/{id}/replied', [AdminContactController::class, 'markReplied']);
        Route::patch('contacts/{id}/reply', [AdminContactController::class, 'reply']);
        Route::delete('contacts/{id}', [AdminContactController::class, 'destroy']);
    });
});