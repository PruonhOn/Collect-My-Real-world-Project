<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    protected function resolveCourse(Course $course)
    {
        if (! $course->teachers()->where('teacher_id', auth()->user()->teacherProfile->id)->exists()) {
            abort(403, __('messages.unauthorized'));
        }

        return $course;
    }

    public function index(Course $course)
    {
        $this->resolveCourse($course);

        $assignments = $course->assignments()
            ->withCount('submissions')
            ->paginate(10);

        return view('teacher.assignments.index', compact('course', 'assignments'));
    }

    public function create(Course $course)
    {
        $this->resolveCourse($course);

        $lessons = $course->lessons()->get();

        return view('teacher.assignments.create', compact('course', 'lessons'));
    }

    public function store(Request $request, Course $course)
    {
        $this->resolveCourse($course);

        $data = $request->validate([
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'total_marks' => ['nullable', 'numeric', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['is_published'] = $request->boolean('is_published');

        $course->assignments()->create($data);

        return redirect()->route('teacher.assignments.index', $course)
            ->with('success', __('messages.created_successfully'));
    }

    public function edit(Course $course, Assignment $assignment)
    {
        $this->resolveCourse($course);

        $lessons = $course->lessons()->get();

        return view('teacher.assignments.edit', compact('course', 'assignment', 'lessons'));
    }

    public function update(Request $request, Course $course, Assignment $assignment)
    {
        $this->resolveCourse($course);

        $data = $request->validate([
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'total_marks' => ['nullable', 'numeric', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['is_published'] = $request->boolean('is_published');

        $assignment->update($data);

        return redirect()->route('teacher.assignments.index', $course)
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Course $course, Assignment $assignment)
    {
        $this->resolveCourse($course);

        $assignment->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }
}
