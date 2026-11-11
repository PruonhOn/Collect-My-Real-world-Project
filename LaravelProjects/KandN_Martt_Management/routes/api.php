<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\UnitController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\FinancialController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\RoleController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChart']);

    // Products
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/barcode/{id}', [ProductController::class, 'generateBarcode']);
    Route::apiResource('/products', ProductController::class);

    // Categories
    Route::apiResource('/categories', CategoryController::class);

    // Brands
    Route::apiResource('/brands', BrandController::class);

    // Units
    Route::apiResource('/units', UnitController::class);

    // Suppliers
    Route::apiResource('/suppliers', SupplierController::class);

    // Customers
    Route::post('/customers/{id}/points', [CustomerController::class, 'addPoints']);
    Route::apiResource('/customers', CustomerController::class);

    // Employees
    Route::apiResource('/employees', EmployeeController::class);

    // Inventory
    Route::post('/inventory/stock-in', [InventoryController::class, 'stockIn']);
    Route::post('/inventory/stock-out', [InventoryController::class, 'stockOut']);
    Route::post('/inventory/adjust', [InventoryController::class, 'adjustStock']);
    Route::get('/inventory/movements', [InventoryController::class, 'movements']);
    Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock']);
    Route::apiResource('/inventory', InventoryController::class);

    // Purchases
    Route::apiResource('/purchases', PurchaseController::class);

    // POS
    Route::get('/pos/search', [PosController::class, 'searchProduct']);
    Route::post('/pos/scan', [PosController::class, 'scanBarcode']);
    Route::post('/pos/checkout', [PosController::class, 'checkout']);
    Route::get('/pos/receipt/{id}', [PosController::class, 'getReceipt']);
    Route::get('/pos/low-stock', [PosController::class, 'lowStockAlerts']);

    // Sales
    Route::get('/sales/today', [SaleController::class, 'getTodaySales']);
    Route::get('/sales/date-range', [SaleController::class, 'getByDateRange']);
    Route::post('/sales/{id}/refund', [SaleController::class, 'refund']);
    Route::apiResource('/sales', SaleController::class);

    // Financial
    Route::get('/financial/profit-loss', [FinancialController::class, 'profitLoss']);
    Route::get('/financial/cash-flow', [FinancialController::class, 'cashFlow']);
    Route::apiResource('/expenses', FinancialController::class)->parameters(['expenses' => 'expense'])->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::apiResource('/incomes', FinancialController::class)->parameters(['incomes' => 'income'])->only(['index', 'store', 'show', 'update', 'destroy']);

    // Reports
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
    Route::get('/reports/monthly-sales', [ReportController::class, 'monthlySales']);
    Route::get('/reports/product-performance', [ReportController::class, 'productPerformance']);
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('/reports/inventory', [ReportController::class, 'inventory']);
    Route::get('/reports/customers', [ReportController::class, 'customers']);

    // Settings
    Route::get('/settings/{group}', [SettingController::class, 'showByGroup']);
    Route::apiResource('/settings', SettingController::class);

    // Roles & Permissions
    Route::post('/roles/assign', [RoleController::class, 'assignRole']);
    Route::post('/roles/remove', [RoleController::class, 'removeRole']);
    Route::apiResource('/roles', RoleController::class);
});
