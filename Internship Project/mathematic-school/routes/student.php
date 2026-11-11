<?php

use App\Http\Controllers\Student\AssignmentController;
use App\Http\Controllers\Student\CertificateController;
use App\Http\Controllers\Student\CourseController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\LessonController;
use App\Http\Controllers\Student\QuizController;
use App\Http\Controllers\Student\QuizTakingController;
use App\Http\Controllers\Student\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('student')->name('student.')->middleware(['role:student'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::get('/my-courses', [CourseController::class, 'myCourses'])->name('courses.mine');

    Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');
    Route::post('/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('lessons.complete');

    Route::get('/courses/{course}/quizzes/{quiz}/start', [QuizTakingController::class, 'start'])->name('quizzes.start');
    Route::post('/quizzes/{quiz}/submit', [QuizTakingController::class, 'submit'])->name('quizzes.submit');
    Route::get('/quizzes/{quiz}/result/{attempt}', [QuizTakingController::class, 'result'])->name('quizzes.result');
    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');

    Route::get('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'show'])->name('assignments.show');
    Route::post('/assignments/{assignment}/submit', [SubmissionController::class, 'store'])->name('assignments.submit');
    Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    Route::get('/progress', [DashboardController::class, 'progress'])->name('progress');

    Route::get('/grades', [DashboardController::class, 'grades'])->name('grades');
});
