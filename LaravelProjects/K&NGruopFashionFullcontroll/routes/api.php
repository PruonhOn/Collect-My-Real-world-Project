<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AdminProductController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SystemController;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/{slug}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/products/category/{categorySlug}', [ProductController::class, 'byCategory']);
Route::post('/contact', [ContactController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::apiResource('products', AdminProductController::class);
        Route::patch('/products/{product}/toggle-status', [AdminProductController::class, 'toggleStatus']);
        Route::post('/upload-image', [AdminProductController::class, 'uploadImage']);
        Route::apiResource('categories', CategoryController::class)->except(['show']);
        Route::apiResource('users', UserController::class);
        Route::get('/contacts', [ContactController::class, 'index']);
        Route::get('/contacts/{contactMessage}', [ContactController::class, 'show']);
        Route::delete('/contacts/{contactMessage}', [ContactController::class, 'destroy']);

        Route::get('/settings', [SystemController::class, 'settings']);
        Route::put('/settings', [SystemController::class, 'updateSettings']);
        Route::get('/system-info', [SystemController::class, 'systemInfo']);
        Route::post('/clear-cache', [SystemController::class, 'clearCache']);
        Route::post('/maintenance', [SystemController::class, 'maintenanceMode']);
        Route::get('/activity-logs', [SystemController::class, 'activityLogs']);
        Route::delete('/activity-logs', [SystemController::class, 'clearActivityLogs']);
    });
});
