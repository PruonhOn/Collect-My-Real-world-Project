<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', CategoryController::class);
    Route::resource('products', ProductController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('sales', SaleController::class);
    Route::get('sales/{sale}/invoice', [SaleController::class, 'invoice'])->name('sales.invoice');
    Route::resource('purchases', PurchaseController::class);
    Route::resource('expenses', ExpenseController::class);

    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class);
    });

    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::post('add-to-cart', [PosController::class, 'addToCart'])->name('add-to-cart');
        Route::delete('remove-from-cart/{key}', [PosController::class, 'removeFromCart'])->name('remove-from-cart');
        Route::patch('update-cart/{key}', [PosController::class, 'updateCart'])->name('update-cart');
        Route::post('apply-discount', [PosController::class, 'applyDiscount'])->name('apply-discount');
        Route::post('checkout', [PosController::class, 'checkout'])->name('checkout');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('sales', [ReportController::class, 'salesReport'])->name('sales');
        Route::get('inventory', [ReportController::class, 'inventoryReport'])->name('inventory');
        Route::get('profit', [ReportController::class, 'profitReport'])->name('profit');
    });

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
