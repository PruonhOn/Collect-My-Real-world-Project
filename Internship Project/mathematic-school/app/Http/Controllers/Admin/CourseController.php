<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with(['category', 'teachers', 'enrollments'])
            ->withCount('enrollments');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('course_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('price_type')) {
            if ($request->price_type === 'free') {
                $query->where('price', 0);
            } elseif ($request->price_type === 'paid') {
                $query->where('price', '>', 0);
            }
        }

        $courses = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('admin.courses.index', compact('courses', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $teachers = Teacher::where('status', 'active')->orderBy('full_name')->get();

        return view('admin.courses.create', compact('categories', 'teachers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:50', 'unique:courses,course_code'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'age_group' => ['required', 'string'],
            'level' => ['nullable', 'string', 'max:50'],
            'duration' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'requirements' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,pending_review,published,archived'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['exists:teachers,id'],
        ]);

        $data['slug'] = Str::slug($data['title']);
        $data['created_by'] = auth()->id();

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        }

        unset($data['teacher_ids']);
        $course = Course::create($data);

        if ($request->filled('teacher_ids')) {
            $course->teachers()->sync($request->teacher_ids);
        }

        return redirect()->route('admin.courses.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(Course $course)
    {
        $course->load([
            'category',
            'teachers',
            'lessons',
            'enrollments.student',
            'quizzes',
            'assignments',
        ]);

        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $categories = Category::orderBy('name')->get();
        $teachers = Teacher::where('status', 'active')->orderBy('full_name')->get();
        $course->load('teachers');

        return view('admin.courses.edit', compact('course', 'categories', 'teachers'));
    }

    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'course_code' => ['required', 'string', 'max:50', 'unique:courses,course_code,' . $course->id],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
            'age_group' => ['required', 'string'],
            'level' => ['nullable', 'string', 'max:50'],
            'duration' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'requirements' => ['nullable', 'string'],
            'objectives' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,pending_review,published,archived'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['exists:teachers,id'],
        ]);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('courses', 'public');
        } else {
            unset($data['thumbnail']);
        }

        unset($data['teacher_ids']);
        $course->update($data);

        $course->teachers()->sync($request->teacher_ids ?? []);

        return redirect()->route('admin.courses.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    public function publish(Course $course)
    {
        $course->update([
            'status' => $course->status === 'published' ? 'draft' : 'published',
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }
}
