<?php

use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CertificateController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\LessonController;
use App\Http\Controllers\Api\V1\ParentController;
use App\Http\Controllers\Api\V1\ProgressController;
use App\Http\Controllers\Api\V1\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::get('/categories', [CourseController::class, 'categories']);
    Route::get('/certificates/verify', [CertificateController::class, 'verify']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        // Student - Courses
        Route::get('/my/courses', [EnrollmentController::class, 'index']);
        Route::post('/enrollments', [EnrollmentController::class, 'store']);

        // Lessons
        Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])
            ->whereNumber('course')
            ->whereNumber('lesson');
        Route::post('/lessons/{lesson}/complete', [LessonController::class, 'complete']);

        // Quizzes
        Route::get('/my/quizzes', [QuizController::class, 'index']);
        Route::post('/quizzes/{quiz}/start', [QuizController::class, 'start']);
        Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit']);
        Route::get('/quizzes/{quiz}/attempts/{attempt}', [QuizController::class, 'result']);

        // Assignments
        Route::get('/my/assignments', [AssignmentController::class, 'index']);
        Route::get('/assignments/{assignment}', [AssignmentController::class, 'show']);
        Route::post('/assignments/{assignment}/submit', [AssignmentController::class, 'submit']);

        // Progress
        Route::get('/my/progress', [ProgressController::class, 'index']);
        Route::get('/my/grades', [ProgressController::class, 'grades']);

        // Certificates
        Route::get('/my/certificates', [CertificateController::class, 'index']);
        Route::get('/certificates/{certificate}', [CertificateController::class, 'show']);

        // Parent
        Route::prefix('parents')->group(function () {
            Route::get('/children', [ParentController::class, 'children']);
            Route::get('/children/{student}/progress', [ParentController::class, 'childProgress']);
            Route::get('/children/{student}/results', [ParentController::class, 'childResults']);
            Route::get('/children/{student}/certificates', [ParentController::class, 'childCertificates']);
            Route::get('/children/{student}/payments', [ParentController::class, 'childPayments']);
        });
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
