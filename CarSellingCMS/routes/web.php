<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ContactController as AdminContactController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [HomeController::class, 'search'])->name('search');

Route::get('/cars', [CarController::class, 'index'])->name('cars.index');
Route::get('/cars/{slug}', [CarController::class, 'show'])->name('cars.show');

Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/page/{slug}', [PageController::class, 'show'])->name('pages.show');

// Payment Routes
Route::get('/payment/checkout/{car}', [PaymentController::class, 'checkout'])->name('payment.checkout');
Route::post('/payment/process', [PaymentController::class, 'process'])->name('payment.process');
Route::get('/payment/return', [PaymentController::class, 'returnUrl'])->name('payment.return');
Route::get('/payment/cancel', [PaymentController::class, 'cancelUrl'])->name('payment.cancel');
Route::get('/payment/success/{id}', [PaymentController::class, 'success'])->name('payment.success');

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('cars', AdminCarController::class);
    Route::resource('categories', AdminCategoryController::class);
    Route::resource('contacts', AdminContactController::class)->only(['index', 'show', 'destroy']);
    Route::resource('pages', AdminPageController::class);
    Route::resource('testimonials', AdminTestimonialController::class);

    Route::get('transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/{transaction}', [AdminTransactionController::class, 'show'])->name('transactions.show');

    Route::get('settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [AdminSettingController::class, 'update'])->name('settings.update');
});

require __DIR__ . '/auth.php';
