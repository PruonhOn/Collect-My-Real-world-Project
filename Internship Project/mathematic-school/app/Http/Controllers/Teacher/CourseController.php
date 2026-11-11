<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    protected function resolveCourse(Course $course)
    {
        if (! $course->teachers()->where('teacher_id', auth()->user()->teacherProfile->id)->exists()) {
            abort(403, __('messages.unauthorized'));
        }

        return $course;
    }

    public function index(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;

        $query = Course::whereHas('teachers', function ($q) use ($teacher) {
            $q->where('teacher_id', $teacher->id);
        })->with('category')->withCount('enrollments');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('course_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $courses = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('teacher.courses.index', compact('courses', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $ageGroups = age_groups();

        return view('teacher.courses.create', compact('categories', 'ageGroups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'age_group' => ['nullable', 'string'],
            'level' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'requirements' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,pending_review,published,archived'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        $data['created_by'] = auth()->id();

        $course = Course::create($data);

        $course->teachers()->attach(auth()->user()->teacherProfile->id);

        return redirect()->route('teacher.courses.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(Course $course)
    {
        $this->resolveCourse($course);

        $course->load([
            'category',
            'lessons',
            'enrollments.student',
            'quizzes',
            'assignments',
        ]);

        return view('teacher.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $this->resolveCourse($course);

        $categories = Category::orderBy('name')->get();
        $ageGroups = age_groups();

        return view('teacher.courses.edit', compact('course', 'categories', 'ageGroups'));
    }

    public function update(Request $request, Course $course)
    {
        $this->resolveCourse($course);

        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'age_group' => ['nullable', 'string'],
            'level' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'requirements' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,pending_review,published,archived'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        $course->update($data);

        return redirect()->route('teacher.courses.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Course $course)
    {
        $this->resolveCourse($course);

        $course->teachers()->detach(auth()->user()->teacherProfile->id);
        $course->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    public function publish(Course $course)
    {
        $this->resolveCourse($course);

        $course->update([
            'status' => $course->status === 'published' ? 'draft' : 'published',
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }
}
