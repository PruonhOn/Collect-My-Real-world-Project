<?php

use App\Http\Controllers\Teacher\AssignmentController;
use App\Http\Controllers\Teacher\CourseController;
use App\Http\Controllers\Teacher\DashboardController;
use App\Http\Controllers\Teacher\LessonController;
use App\Http\Controllers\Teacher\QuizController;
use App\Http\Controllers\Teacher\StudentController;
use App\Http\Controllers\Teacher\SubmissionController;
use Illuminate\Support\Facades\Route;

Route::prefix('teacher')->name('teacher.')->middleware(['role:teacher'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::patch('/courses/{course}/publish', [CourseController::class, 'publish'])->name('courses.publish');

    Route::get('/courses/{course}/lessons', [LessonController::class, 'index'])->name('lessons.index');
    Route::get('/courses/{course}/lessons/create', [LessonController::class, 'create'])->name('lessons.create');
    Route::post('/courses/{course}/lessons', [LessonController::class, 'store'])->name('lessons.store');
    Route::get('/courses/{course}/lessons/{lesson}/edit', [LessonController::class, 'edit'])->name('lessons.edit');
    Route::put('/courses/{course}/lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
    Route::delete('/courses/{course}/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');

    Route::get('/courses/{course}/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/courses/{course}/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/courses/{course}/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/courses/{course}/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
    Route::put('/courses/{course}/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::delete('/courses/{course}/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');
    Route::get('/quizzes/{quiz}/results', [QuizController::class, 'results'])->name('quizzes.results');

    Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/courses/{course}/assignments/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/courses/{course}/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/courses/{course}/assignments/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::put('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/courses/{course}/assignments/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    Route::get('/courses/{course}/enrollments', [StudentController::class, 'enrollments'])->name('courses.enrollments');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');

    Route::get('/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/submissions/{submission}/grade', [SubmissionController::class, 'grade'])->name('submissions.grade');
    Route::put('/submissions/{submission}/grade', [SubmissionController::class, 'updateGrade'])->name('submissions.update-grade');
});
