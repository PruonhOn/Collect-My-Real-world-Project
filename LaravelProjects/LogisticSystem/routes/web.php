<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    BranchController,
    CustomerController,
    ShipmentController,
    WarehouseController,
    WarehouseLocationController,
    VehicleController,
    VehicleMaintenanceController,
    DriverController,
    RouteController,
    DeliveryController,
    TrackingController,
    InvoiceController,
    PaymentController,
    FinanceController,
    TransactionController,
    ExpenseController,
    ReportController,
    NotificationController,
    AuditLogController,
    InventoryController,
};
use App\Http\Controllers\Admin\{
    DashboardController,
    UserController,
    RoleController,
    SettingController,
};

// Public routes
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::get('/track', [TrackingController::class, 'track'])->name('tracking.index');
Route::get('/track/{trackingNumber}', [TrackingController::class, 'show'])->name('tracking.show');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');

    // Branches
    Route::resource('branches', BranchController::class);

    // Customers
    Route::resource('customers', CustomerController::class);

    // Shipments
    Route::resource('shipments', ShipmentController::class);
    Route::patch('shipments/{id}/status', [ShipmentController::class, 'statusUpdate'])->name('shipments.status-update');
    Route::get('shipments/{id}/print-label', [ShipmentController::class, 'printLabel'])->name('shipments.print-label');

    // Warehouses
    Route::resource('warehouses', WarehouseController::class);

    // Warehouse Locations
    Route::resource('warehouse-locations', WarehouseLocationController::class);

    // Vehicles
    Route::resource('vehicles', VehicleController::class);

    // Vehicle Maintenance
    Route::resource('vehicle-maintenances', VehicleMaintenanceController::class);

    // Drivers
    Route::resource('drivers', DriverController::class);

    // Routes
    Route::resource('routes', RouteController::class);

    // Deliveries
    Route::resource('deliveries', DeliveryController::class);
    Route::patch('deliveries/{id}/assign-driver', [DeliveryController::class, 'assignDriver'])->name('deliveries.assign-driver');
    Route::patch('deliveries/{id}/mark-delivered', [DeliveryController::class, 'markDelivered'])->name('deliveries.mark-delivered');
    Route::post('deliveries/{id}/upload-proof', [DeliveryController::class, 'uploadProof'])->name('deliveries.upload-proof');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{id}/download-pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.download-pdf');
    Route::patch('invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');

    // Payments
    Route::resource('payments', PaymentController::class);

    // Finance
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('/finance/transactions', [FinanceController::class, 'transactions'])->name('finance.transactions');
    Route::get('/finance/expenses', [FinanceController::class, 'expenses'])->name('finance.expenses');

    // Transactions
    Route::resource('transactions', TransactionController::class);

    // Expenses
    Route::resource('expenses', ExpenseController::class);

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/shipments', [ReportController::class, 'shipments'])->name('reports.shipments');
    Route::get('/reports/deliveries', [ReportController::class, 'deliveries'])->name('reports.deliveries');
    Route::get('/reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
    Route::get('/reports/financial', [ReportController::class, 'financial'])->name('reports.financial');
    Route::get('/reports/warehouses', [ReportController::class, 'warehouses'])->name('reports.warehouses');
    Route::get('/reports/export-pdf/{type}', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('/reports/export-excel/{type}', [ReportController::class, 'exportExcel'])->name('reports.export-excel');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    // Inventory
    Route::resource('inventory', InventoryController::class);
    Route::get('inventory/{id}/stock-movements', [InventoryController::class, 'stockMovements'])->name('inventory.stock-movements');
    Route::post('inventory/{id}/adjust-stock', [InventoryController::class, 'adjustStock'])->name('inventory.adjust-stock');
});

// Admin routes
Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('settings', SettingController::class)->only(['index', 'update']);
});

require __DIR__.'/auth.php';
