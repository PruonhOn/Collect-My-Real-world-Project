<?php

use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AssessmentController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\FeeController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ParentController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PublicController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\ScoreController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('public/settings', [PublicController::class, 'settings']);
Route::get('public/courses', [PublicController::class, 'courses']);
Route::get('public/categories', [PublicController::class, 'categories']);
Route::post('public/contact', [PublicController::class, 'contact']);

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    Route::get('dashboard', [DashboardController::class, 'index']);

    // Profile-scoped reads shared by every role.
    Route::get('my/notifications', [NotificationController::class, 'index']);
    Route::get('my/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('my/attendance', [AttendanceController::class, 'today']);

    // Notifications
    Route::get('notifications/{notification}', [NotificationController::class, 'show']);
    Route::put('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    // Messaging
    Route::get('messages', [MessageController::class, 'index']);
    Route::post('messages', [MessageController::class, 'store']);
    Route::get('messages/conversation/{user}', [MessageController::class, 'conversation']);

    // Announcements (read)
    Route::get('announcements', [AnnouncementController::class, 'index']);
    Route::get('announcements/{announcement}', [AnnouncementController::class, 'show']);

    // Fees & payments (scoped to own students/children unless permission granted)
    Route::get('fees', [FeeController::class, 'index']);
    Route::get('fees/summary', [FeeController::class, 'summary']);
    Route::get('fees/statement/{student}', [FeeController::class, 'statement']);
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt']);

    Route::middleware('permission:payments.make')->group(function () {
        Route::post('fees/{studentFee}/pay', [PaymentController::class, 'charge']);
    });

    // Teacher-specific endpoints
    Route::middleware('role:teacher')->group(function () {
        Route::get('my/courses', [CourseController::class, 'myCourses']);
        Route::get('my/courses/{course}/students', [CourseController::class, 'students']);
        Route::get('my/courses/{course}/schedules', [ScheduleController::class, 'index']);

        Route::get('my/courses/{course}/attendance', [AttendanceController::class, 'index']);
        Route::post('my/courses/{course}/attendance', [AttendanceController::class, 'register']);
        Route::put('my/courses/{course}/attendance', [AttendanceController::class, 'mark']);

        Route::get('my/courses/{course}/assessments', [AssessmentController::class, 'index']);
        Route::post('my/courses/{course}/assessments', [AssessmentController::class, 'store']);
        Route::get('my/courses/{course}/assessments/{assessment}/results', [AssessmentController::class, 'results']);
        Route::get('my/courses/{course}/assessments/{assessment}/scores', [ScoreController::class, 'index']);
        Route::post('my/courses/{course}/assessments/{assessment}/scores', [ScoreController::class, 'store']);
    });
});

// ==== Administration: people, courses, academics ====

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('admin')->group(function () {
    Route::middleware('permission:users.manage')->group(function () {
        Route::apiResource('users', UserController::class)->except(['create', 'edit']);
    });

    Route::middleware('permission:teachers.manage')->group(function () {
        Route::apiResource('teachers', TeacherController::class)->except(['create', 'edit']);
        Route::put('teachers/{teacher}/toggle', [TeacherController::class, 'toggle']);
        Route::put('teachers/{teacher}/courses', [TeacherController::class, 'assignCourses']);
    });

    Route::middleware('permission:staff.manage')->group(function () {
        Route::apiResource('staff', StaffController::class)->except(['create', 'edit']);
        Route::put('staff/{staff}/toggle', [StaffController::class, 'toggle']);
    });

    Route::middleware('permission:students.manage')->group(function () {
        Route::apiResource('students', StudentController::class)->except(['create', 'edit']);
        Route::put('students/{student}/toggle', [StudentController::class, 'toggle']);
    });

    Route::middleware('permission:parents.manage')->group(function () {
        Route::apiResource('parents', ParentController::class)->except(['create', 'edit']);
        Route::get('parents/{parent}/children', [ParentController::class, 'children']);
        Route::put('parents/{parent}/children', [ParentController::class, 'linkChildren']);
    });

    Route::middleware('permission:courses.manage')->group(function () {
        Route::apiResource('courses', CourseController::class)->except(['create', 'edit']);
        Route::put('courses/{course}/status', [CourseController::class, 'toggleStatus']);
        Route::post('courses/{course}/enroll', [CourseController::class, 'enroll']);
        Route::get('courses/{course}/students', [CourseController::class, 'students']);
        Route::put('courses/{course}/enrollments', [CourseController::class, 'manageEnrollment']);
        Route::delete('courses/{course}/enrollments', [CourseController::class, 'dropStudent']);
        Route::apiResource('courses.schedules', ScheduleController::class)->except(['create', 'edit']);
        Route::apiResource('courses.assessments', AssessmentController::class)->except(['create', 'edit']);
        Route::get('courses/{course}/assessments/{assessment}/results', [AssessmentController::class, 'results']);
        Route::apiResource('courses.assessments.scores', ScoreController::class)->except(['create', 'edit', 'show']);
    });

    Route::middleware('permission:courses.view')->group(function () {
        Route::get('courses', [CourseController::class, 'index']);
        Route::get('courses/{course}', [CourseController::class, 'show']);
        Route::get('courses/{course}/attendance', [AttendanceController::class, 'index']);
    });

    Route::middleware('permission:academic_years.manage')->group(function () {
        Route::apiResource('academic-years', AcademicYearController::class)->except(['create', 'edit']);
    });

    Route::middleware('permission:grades.view')->group(function () {
        Route::get('grades', [GradeController::class, 'index']);
        Route::get('grades/{grade}', [GradeController::class, 'show']);
    });

    Route::middleware('permission:fees.manage')->group(function () {
        Route::post('fees', [FeeController::class, 'store']);
        Route::put('fees/{studentFee}', [FeeController::class, 'update']);
    });

    Route::middleware('permission:payments.process')->group(function () {
        Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm']);
        Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);
    });

    Route::middleware('permission:announcements.manage')->group(function () {
        Route::post('announcements', [AnnouncementController::class, 'store']);
        Route::put('announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy']);
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('settings', [SettingsController::class, 'index']);
        Route::put('settings', [SettingsController::class, 'update']);
        Route::delete('settings', [SettingsController::class, 'delete']);
    });

    Route::middleware('permission:audit_logs.view')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index']);
    });

    Route::middleware('permission:contact_inquiries.view')->group(function () {
        Route::get('contact-inquiries', [PublicController::class, 'inquiries']);
    });

    Route::middleware('permission:reports.export')->group(function () {
        Route::get('reports', [ReportController::class, 'index']);
        Route::get('reports/supported', [ReportController::class, 'supported']);
        Route::post('reports', [ReportController::class, 'store']);
        Route::get('reports/{report}', [ReportController::class, 'show']);
        Route::get('reports/{report}/download', [ReportController::class, 'download']);
        Route::get('exports/students', [ReportController::class, 'exportStudents']);
    });
});
