<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', CategoryController::class);
    Route::get('categories/list/all', [CategoryController::class, 'list'])->name('categories.list');

    Route::resource('brands', BrandController::class);
    Route::resource('units', UnitController::class);
    Route::resource('suppliers', SupplierController::class);

    Route::resource('customers', CustomerController::class);
    Route::get('customers/search/ajax', [CustomerController::class, 'search'])->name('customers.search');

    Route::resource('employees', EmployeeController::class);
    Route::resource('products', ProductController::class);
    Route::get('products/data/table', [ProductController::class, 'data'])->name('products.data');
    Route::get('products/barcode/{product}', [ProductController::class, 'barcode'])->name('products.barcode');
    Route::get('products/list/all', [ProductController::class, 'list'])->name('products.list');

    Route::resource('purchases', PurchaseController::class);
    Route::resource('sales', SaleController::class);

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/data', [InventoryController::class, 'data'])->name('inventory.data');
    Route::get('inventory/movements', [InventoryController::class, 'movementsIndex'])->name('inventory.movements');
    Route::get('inventory/movements/data', [InventoryController::class, 'movementsData'])->name('inventory.movements.data');
    Route::get('inventory/alerts', [InventoryController::class, 'alerts'])->name('inventory.alerts');
    Route::get('inventory/alerts/count', [InventoryController::class, 'alertsCount'])->name('inventory.alerts.count');
    Route::post('inventory/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
    Route::post('inventory/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');
    Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

    Route::get('warehouses/list/all', [InventoryController::class, 'warehouses'])->name('warehouses.list');

    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('pos/products', [PosController::class, 'products'])->name('pos.products');
    Route::post('pos/store', [PosController::class, 'store'])->name('pos.store');

    Route::get('financial', [FinancialController::class, 'index'])->name('financial.index');
    Route::post('financial/expenses/store', [FinancialController::class, 'expensesStore'])->name('financial.expenses.store');
    Route::post('financial/incomes/store', [FinancialController::class, 'incomesStore'])->name('financial.incomes.store');
    Route::get('financial/pnl', [FinancialController::class, 'pnl'])->name('financial.pnl');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('reports/products', [ReportController::class, 'products'])->name('reports.products');
    Route::get('reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
    Route::get('reports/customers', [ReportController::class, 'customers'])->name('reports.customers');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/update', [SettingController::class, 'update'])->name('settings.update');
});
