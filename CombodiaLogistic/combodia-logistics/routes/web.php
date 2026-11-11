<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryRouteController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PickupRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleMaintenanceController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseShelfController;
use App\Http\Controllers\WarehouseZoneController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Dashboard
Route::get('/', fn () => redirect()->route('dashboard'));
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Authenticated Routes
Route::middleware('auth')->group(function () {

    // User Management
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

    // Role Management
    Route::resource('roles', RoleController::class);

    // Branch Management
    Route::resource('branches', BranchController::class);

    // Warehouse Management
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('warehouse-zones', WarehouseZoneController::class);
    Route::resource('warehouse-shelves', WarehouseShelfController::class);

    // Customer Management
    Route::resource('customers', CustomerController::class);

    // Supplier Management
    Route::resource('suppliers', SupplierController::class);

    // Vehicle Management
    Route::resource('vehicles', VehicleController::class);
    Route::resource('vehicle-types', VehicleTypeController::class);
    Route::resource('vehicle-maintenances', VehicleMaintenanceController::class);

    // Driver Management
    Route::resource('drivers', DriverController::class);

    // Shipment Management
    Route::resource('shipments', ShipmentController::class);
    Route::post('shipments/{shipment}/approve', [ShipmentController::class, 'approve'])->name('shipments.approve');
    Route::post('shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');
    Route::post('shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.update-status');
    Route::get('shipments/{shipment}/track', [ShipmentController::class, 'track'])->name('shipments.track');
    Route::post('shipments/{shipment}/barcode', [ShipmentController::class, 'generateBarcode'])->name('shipments.barcode');

    // Package Management
    Route::resource('packages', PackageController::class);
    Route::post('packages/{package}/barcode', [PackageController::class, 'generateBarcode'])->name('packages.barcode');

    // Pickup Request Management
    Route::resource('pickup-requests', PickupRequestController::class);
    Route::post('pickup-requests/{pickupRequest}/assign', [PickupRequestController::class, 'assign'])->name('pickup-requests.assign');
    Route::post('pickup-requests/{pickupRequest}/confirm', [PickupRequestController::class, 'confirm'])->name('pickup-requests.confirm');

    // Dispatch Management
    Route::resource('dispatches', DispatchController::class);
    Route::post('dispatches/{dispatch}/assign-driver', [DispatchController::class, 'assignDriver'])->name('dispatches.assign-driver');
    Route::post('dispatches/{dispatch}/load', [DispatchController::class, 'load'])->name('dispatches.load');
    Route::post('dispatches/{dispatch}/complete', [DispatchController::class, 'complete'])->name('dispatches.complete');

    // Delivery Route Management
    Route::resource('routes', DeliveryRouteController::class)->parameters([
        'routes' => 'deliveryRoute',
    ]);

    // Tracking
    Route::get('tracking', [TrackingController::class, 'index'])->name('tracking.index');
    Route::get('tracking/{shipment}', [TrackingController::class, 'show'])->name('tracking.show');
    Route::post('tracking/update-location', [TrackingController::class, 'updateLocation'])->name('tracking.update-location');

    // Inventory Management
    Route::resource('inventories', InventoryController::class);

    // Stock Movement Management
    Route::resource('stock-movements', StockMovementController::class)->parameters([
        'stock-movements' => 'stockMovement',
    ]);

    // Invoice Management
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoices.pdf');

    // Payment Management
    Route::resource('payments', PaymentController::class);

    // Expense Management
    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');

    // Income Management
    Route::resource('incomes', IncomeController::class);

    // Return Management
    Route::resource('returns', ReturnController::class);

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('weekly', [ReportController::class, 'weekly'])->name('weekly');
        Route::get('monthly', [ReportController::class, 'monthly'])->name('monthly');
        Route::get('yearly', [ReportController::class, 'yearly'])->name('yearly');
        Route::get('shipment', [ReportController::class, 'shipment'])->name('shipment');
        Route::get('financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('driver', [ReportController::class, 'driver'])->name('driver');
        Route::get('warehouse', [ReportController::class, 'warehouse'])->name('warehouse');
        Route::get('customer', [ReportController::class, 'customer'])->name('customer');
        Route::get('vehicle', [ReportController::class, 'vehicle'])->name('vehicle');
        Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
    });

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

    // System Settings
    Route::get('settings', [SystemSettingController::class, 'index'])->name('settings.index');
    Route::post('settings/update', [SystemSettingController::class, 'update'])->name('settings.update');

    // Backup Management
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::get('backups/create', [BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/download/{file}', [BackupController::class, 'download'])->name('backups.download');
    Route::post('backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
});
