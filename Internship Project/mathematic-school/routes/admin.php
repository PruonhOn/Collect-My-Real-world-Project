<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\ParentController as AdminParentController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {

    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('students', StudentController::class);
    Route::resource('parents', AdminParentController::class);
    Route::resource('teachers', TeacherController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('courses', CourseController::class);
    Route::resource('lessons', LessonController::class);
    Route::resource('enrollments', EnrollmentController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('certificates', CertificateController::class);
    Route::resource('announcements', AnnouncementController::class);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('courses/{course}/lessons/create', [LessonController::class, 'create'])->name('lessons.create-for-course');

    Route::patch('students/{student}/toggle', [StudentController::class, 'toggle'])->name('students.toggle');
    Route::patch('courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');
    Route::patch('payments/{payment}/status', [PaymentController::class, 'updateStatus'])->name('payments.status');
});
