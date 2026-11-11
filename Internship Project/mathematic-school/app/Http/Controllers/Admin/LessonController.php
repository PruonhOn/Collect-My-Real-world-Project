<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index(Request $request)
    {
        $query = Lesson::with('course');

        if ($request->filled('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $lessons = $query->latest()->paginate(15)->withQueryString();
        $courses = Course::orderBy('title')->get();

        return view('admin.lessons.index', compact('lessons', 'courses'));
    }

    public function create(Request $request)
    {
        $courses = Course::orderBy('title')->get();
        $selectedCourse = null;
        $maxOrder = 1;

        if ($request->filled('course_id')) {
            $selectedCourse = $request->course_id;
            $maxOrder = Lesson::where('course_id', $selectedCourse)->max('order') + 1;
        }

        return view('admin.lessons.create', compact('courses', 'selectedCourse', 'maxOrder'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url'],
            'video_file' => ['nullable', 'file', 'max:102400'],
            'pdf_file' => ['nullable', 'file', 'max:10240'],
            'external_resources' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'order' => ['nullable', 'integer', 'min:1'],
            'is_free' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('video_file')) {
            $data['video_path'] = $request->file('video_file')->store('lessons/videos', 'public');
        }

        if ($request->hasFile('pdf_file')) {
            $data['pdf_path'] = $request->file('pdf_file')->store('lessons/pdfs', 'public');
        }

        $data['is_free'] = $request->boolean('is_free');
        $data['is_published'] = $request->boolean('is_published');

        unset($data['video_file'], $data['pdf_file']);
        Lesson::create($data);

        return redirect()->route('admin.lessons.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(Lesson $lesson)
    {
        $lesson->load('course', 'materials');

        return view('admin.lessons.show', compact('lesson'));
    }

    public function edit(Lesson $lesson)
    {
        $courses = Course::orderBy('title')->get();

        return view('admin.lessons.edit', compact('lesson', 'courses'));
    }

    public function update(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url'],
            'video_file' => ['nullable', 'file', 'max:102400'],
            'pdf_file' => ['nullable', 'file', 'max:10240'],
            'external_resources' => ['nullable', 'string'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'order' => ['nullable', 'integer', 'min:1'],
            'is_free' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if ($request->hasFile('video_file')) {
            $data['video_path'] = $request->file('video_file')->store('lessons/videos', 'public');
        }

        if ($request->hasFile('pdf_file')) {
            $data['pdf_path'] = $request->file('pdf_file')->store('lessons/pdfs', 'public');
        }

        $data['is_free'] = $request->boolean('is_free');
        $data['is_published'] = $request->boolean('is_published');

        unset($data['video_file'], $data['pdf_file']);
        $lesson->update($data);

        return redirect()->route('admin.lessons.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }
}
