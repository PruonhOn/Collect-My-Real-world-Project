<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('Welcome'))->name('home');
Route::get('/about', fn() => Inertia::render('About'))->name('about');
Route::get('/contact', fn() => Inertia::render('Contact'))->name('contact');
Route::get('/products', fn() => Inertia::render('Products'))->name('products');
Route::get('/products/{slug}', fn($slug) => Inertia::render('ProductDetail', ['slug' => $slug]))->name('products.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/management/dashboard', fn() => Inertia::render('Management/Dashboard'))->name('management.dashboard');
    Route::get('/management/products', fn() => Inertia::render('Management/Products'))->name('management.products');
    Route::get('/management/products/create', fn() => Inertia::render('Management/ProductForm'))->name('management.products.create');
    Route::get('/management/products/{product}/edit', fn($id) => Inertia::render('Management/ProductForm', ['productId' => $id]))->name('management.products.edit');
    Route::get('/management/categories', fn() => Inertia::render('Management/Categories'))->name('management.categories');
    Route::get('/management/users', fn() => Inertia::render('Management/Users'))->name('management.users');
    Route::get('/management/contacts', fn() => Inertia::render('Management/Contacts'))->name('management.contacts');

    Route::prefix('system')->group(function () {
        Route::get('/settings', fn() => Inertia::render('System/Settings'))->name('system.settings');
        Route::get('/system-info', fn() => Inertia::render('System/SystemInfo'))->name('system.info');
        Route::get('/activity-logs', fn() => Inertia::render('System/ActivityLogs'))->name('system.activity-logs');
        Route::get('/cache', fn() => Inertia::render('System/CacheManagement'))->name('system.cache');
        Route::get('/maintenance', fn() => Inertia::render('System/Maintenance'))->name('system.maintenance');
    });
});

require __DIR__.'/auth.php';
