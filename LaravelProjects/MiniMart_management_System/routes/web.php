<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {
        Route::resource('products', ProductController::class);
        Route::get('/products/search/query', [ProductController::class, 'search'])->name('products.search');
        Route::get('/products/search/barcode', [ProductController::class, 'searchByBarcode'])->name('products.search.barcode');

        Route::resource('categories', CategoryController::class);
        Route::resource('expenses', ExpenseController::class);

        Route::get('/inventory', [ProductController::class, 'lowStock'])->name('inventory.index');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');
    });

    // Routes accessible by both admin and cashier
    Route::middleware('role:admin,cashier')->group(function () {
        Route::get('/sales/pos', [SaleController::class, 'pos'])->name('sales.pos');
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{id}', [SaleController::class, 'show'])->name('sales.show');
        Route::post('/sales/checkout', [SaleController::class, 'checkout'])->name('sales.checkout');
        Route::get('/sales/{id}/receipt', [SaleController::class, 'printReceipt'])->name('sales.receipt');
    });
});
