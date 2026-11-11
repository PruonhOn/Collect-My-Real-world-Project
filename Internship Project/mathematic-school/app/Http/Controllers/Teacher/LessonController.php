<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
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

        $lessons = $course->lessons()->paginate(10);

        return view('teacher.lessons.index', compact('course', 'lessons'));
    }

    public function create(Course $course)
    {
        $this->resolveCourse($course);

        return view('teacher.lessons.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        $this->resolveCourse($course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'video_path' => ['nullable', 'file', 'mimes:mp4,mov,avi,mkv,webm', 'max:100000'],
            'pdf_path' => ['nullable', 'file', 'mimes:pdf', 'max:20000'],
            'external_resources' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:1'],
            'is_free' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('video_path')) {
            $data['video_path'] = $request->file('video_path')->store('lessons', 'public');
        }

        if ($request->hasFile('pdf_path')) {
            $data['pdf_path'] = $request->file('pdf_path')->store('lessons', 'public');
        }

        $data['is_free'] = $request->boolean('is_free');
        $data['is_published'] = $request->boolean('is_published');

        if (empty($data['order'])) {
            $data['order'] = $course->lessons()->max('order') + 1;
        }

        $course->lessons()->create($data);

        return redirect()->route('teacher.lessons.index', $course)
            ->with('success', __('messages.created_successfully'));
    }

    public function edit(Course $course, Lesson $lesson)
    {
        $this->resolveCourse($course);

        return view('teacher.lessons.edit', compact('course', 'lesson'));
    }

    public function update(Request $request, Course $course, Lesson $lesson)
    {
        $this->resolveCourse($course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'video_path' => ['nullable', 'file', 'mimes:mp4,mov,avi,mkv,webm', 'max:100000'],
            'pdf_path' => ['nullable', 'file', 'mimes:pdf', 'max:20000'],
            'external_resources' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:1'],
            'is_free' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('video_path')) {
            $data['video_path'] = $request->file('video_path')->store('lessons', 'public');
        }

        if ($request->hasFile('pdf_path')) {
            $data['pdf_path'] = $request->file('pdf_path')->store('lessons', 'public');
        }

        $data['is_free'] = $request->boolean('is_free');
        $data['is_published'] = $request->boolean('is_published');

        if (empty($data['order'])) {
            $data['order'] = $course->lessons()->max('order') + 1;
        }

        $lesson->update($data);

        return redirect()->route('teacher.lessons.index', $course)
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Course $course, Lesson $lesson)
    {
        $this->resolveCourse($course);

        $lesson->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }
}
