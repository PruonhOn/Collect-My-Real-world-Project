<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart', [DashboardController::class, 'chartData'])->name('dashboard.chart');

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
    Route::get('/pos/variant/{variant}', [PosController::class, 'variant'])->name('pos.variant');
    Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/receipt/{order}', [PosController::class, 'receipt'])->name('pos.receipt');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');

    Route::middleware('role:admin,manager')->group(function () {
        Route::resource('customers', CustomerController::class);
        Route::resource('categories', CategoryController::class);
        Route::resource('products', ProductController::class);

        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/sales/export', [ReportController::class, 'exportSales'])->name('reports.sales.export');
        Route::get('/reports/inventory', [ReportController::class, 'inventoryValuation'])->name('reports.inventory');
        Route::get('/reports/inventory/export', [ReportController::class, 'exportInventory'])->name('reports.inventory.export');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('staff', StaffController::class);
        Route::post('/staff/{staff}/toggle', [StaffController::class, 'toggleActive'])->name('staff.toggle');

        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
