<?php

// Admin panel (requires auth + role)
Route::middleware(['auth', 'role:admin|staff'])->group(function () {
    Route::resource('shipments', ShipmentController::class);
    Route::resource('customers', CustomerController::class);
    Route::resource('drivers', DriverController::class);
    Route::resource('warehouses', WarehouseController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::get('reports', [ReportController::class, 'index']);
    Route::get('reports/export', [ReportController::class, 'export']);
});

// Customer portal (separate auth guard)
Route::middleware(['auth:portal'])->prefix('portal')->group(function () {
    Route::get('dashboard', [CustomerPortalController::class, 'dashboard']);
    Route::get('shipments', [CustomerPortalController::class, 'shipments']);
});

// Public tracking (no login needed)
Route::get('tra ck/{code}', [TrackingController::class, 'public'])->name('track.pub lic');