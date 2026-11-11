<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\AcademicTermController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ClassSectionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\ClassSectionController as TeacherSectionController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\ReportController as TeacherReportController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\AttendanceController as StudentAttendanceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Home - send users straight into the system
Route::get('/', function () {
    $user = auth()->user();
    if ($user) {
        if ($user->isSuperAdmin() || $user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        }
        if ($user->isStudent()) {
            return redirect()->route('student.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// QR Check-in route (accessible by authenticated students only)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/student/qr-checkin/{token}', [StudentAttendanceController::class, 'qrCheckIn'])->name('student.qr-checkin');
});

// Admin routes
Route::middleware(['auth', 'verified', 'active', 'role:super_admin|admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Academic Structure
    Route::resource('faculties', FacultyController::class);
    Route::resource('departments', DepartmentController::class);
    Route::resource('programs', ProgramController::class);
    Route::resource('courses', CourseController::class);
    Route::resource('academic-terms', AcademicTermController::class);

    // User Management
    Route::get('/students/import', [StudentController::class, 'import'])->name('students.import');
    Route::post('/students/import', [StudentController::class, 'doImport'])->name('students.do-import');
    Route::resource('students', StudentController::class);

    Route::get('/teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
    Route::post('/teachers/import', [TeacherController::class, 'doImport'])->name('teachers.do-import');
    Route::resource('teachers', TeacherController::class);

    // Class Sections & Enrollment
    Route::resource('class-sections', ClassSectionController::class);
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
    Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

    // Reports
    Route::get('/reports/student', [AdminReportController::class, 'studentReport'])->name('reports.student');
    Route::get('/reports/class', [AdminReportController::class, 'classReport'])->name('reports.class');
    Route::get('/reports/department', [AdminReportController::class, 'departmentReport'])->name('reports.department');
    Route::get('/reports/term', [AdminReportController::class, 'termReport'])->name('reports.term');
    Route::get('/reports/student/{student}/pdf', [AdminReportController::class, 'exportStudentReportPdf'])->name('reports.student.pdf');
    Route::get('/reports/class/{classSection}/excel', [AdminReportController::class, 'exportClassReportExcel'])->name('reports.class.excel');
});

// Teacher routes
Route::middleware(['auth', 'verified', 'active', 'role:teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/sections', [TeacherSectionController::class, 'index'])->name('sections.index');
    Route::get('/sections/{classSection}', [TeacherSectionController::class, 'show'])->name('sections.show');

    // Attendance Management
    Route::get('/sections/{classSection}/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/sections/{classSection}/attendance/sessions', [AttendanceController::class, 'storeSession'])->name('attendance.store-session');
    Route::get('/attendance/sessions/{session}', [AttendanceController::class, 'showSession'])->name('attendance.show-session');
    Route::post('/attendance/sessions/{session}/mark', [AttendanceController::class, 'mark'])->name('attendance.mark');
    Route::post('/attendance/sessions/{session}/bulk-mark', [AttendanceController::class, 'bulkMark'])->name('attendance.bulk-mark');
    Route::post('/attendance/sessions/{session}/close', [AttendanceController::class, 'closeSession'])->name('attendance.close-session');
    Route::post('/attendance/sessions/{session}/regenerate-qr', [AttendanceController::class, 'regenerateQr'])->name('attendance.regenerate-qr');
    Route::get('/attendance/sessions/{session}/qr', [AttendanceController::class, 'showQr'])->name('attendance.show-qr');

    // Reports
    Route::get('/reports/{classSection}', [TeacherReportController::class, 'classReport'])->name('reports.class');
    Route::get('/reports/{classSection}/pdf', [TeacherReportController::class, 'exportPdf'])->name('reports.pdf');
    Route::get('/reports/{classSection}/excel', [TeacherReportController::class, 'exportExcel'])->name('reports.excel');
});

// Student routes
Route::middleware(['auth', 'verified', 'active', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{attendanceRecord}', [StudentAttendanceController::class, 'show'])->name('attendance.show');
    Route::get('/report', [StudentAttendanceController::class, 'report'])->name('report');
});

// Profile routes (all authenticated users)
Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Dashboard redirect - routes user to their role-specific dashboard
Route::middleware(['auth', 'verified', 'active'])->get('/dashboard', function () {
    $user = auth()->user();
    if ($user->isSuperAdmin() || $user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->isTeacher()) {
        return redirect()->route('teacher.dashboard');
    }
    if ($user->isStudent()) {
        return redirect()->route('student.dashboard');
    }
    return redirect()->route('home');
})->name('dashboard');

require __DIR__.'/auth.php';
