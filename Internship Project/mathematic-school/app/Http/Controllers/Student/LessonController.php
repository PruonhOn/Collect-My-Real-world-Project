<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(protected ProgressService $progressService)
    {
    }

    public function show(Course $course, Lesson $lesson)
    {
        abort_unless($course->status === 'published', 404);

        $student = auth()->user()->studentProfile;

        abort_if(! $lesson->is_published, 404);

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        abort_unless($enrollment, 403, __('messages.unauthorized'));

        $lesson->load('materials');

        $course->load(['lessons' => function ($q) {
            $q->where('is_published', true)->orderBy('order');
        }, 'quizzes' => function ($q) {
            $q->where('is_published', true);
        }]);

        $completedIds = $student->lessonProgress()
            ->whereIn('lesson_id', $course->lessons->pluck('id'))
            ->where('is_completed', true)
            ->pluck('lesson_id')
            ->all();

        $currentCompleted = $student->lessonProgress()
            ->where('lesson_id', $lesson->id)
            ->where('is_completed', true)
            ->exists();

        return view('student.lessons.show', compact('course', 'lesson', 'enrollment', 'completedIds', 'currentCompleted'));
    }

    public function complete(Lesson $lesson)
    {
        $student = auth()->user()->studentProfile;

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $lesson->course_id)
            ->first();

        abort_unless($enrollment, 403, __('messages.unauthorized'));

        $this->progressService->completeLesson($student->id, $lesson->id);

        return redirect()->back()
            ->with('success', __('messages.saved_successfully'));
    }
}