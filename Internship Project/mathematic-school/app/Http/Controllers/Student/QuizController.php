<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index()
    {
        $student = auth()->user()->studentProfile;

        $courseIds = $student->enrollments()->pluck('course_id');

        $quizzes = Quiz::where('is_published', true)
            ->whereHas('course', fn ($q) => $q->where('status', 'published'))
            ->whereIn('course_id', $courseIds)
            ->with(['course', 'attempts' => fn ($q) => $q->where('student_id', $student->id)])
            ->latest()
            ->paginate(15);

        return view('student.quizzes.index', compact('quizzes'));
    }
}