<?php

use App\Http\Controllers\Parent\ChildController;
use App\Http\Controllers\Parent\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('parent')->name('parent.')->middleware(['role:parent'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/children', [ChildController::class, 'index'])->name('children.index');
    Route::get('/children/{student}', [ChildController::class, 'show'])->name('children.show');
    Route::get('/children/{student}/progress', [ChildController::class, 'progress'])->name('children.progress');
    Route::get('/children/{student}/results', [ChildController::class, 'results'])->name('children.results');
    Route::get('/children/{student}/certificates', [ChildController::class, 'certificates'])->name('children.certificates');
    Route::get('/children/{student}/payments', [ChildController::class, 'payments'])->name('children.payments');
    Route::get('/children/{student}/attendance', [ChildController::class, 'attendance'])->name('children.attendance');
});
