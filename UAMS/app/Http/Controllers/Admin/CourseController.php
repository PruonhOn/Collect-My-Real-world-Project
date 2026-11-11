<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('permission:manage-system');

        $query = Course::with('program');

        if ($request->filled('department_id')) {
            $query->whereHas('program', function ($q) use ($request) {
                $q->where('department_id', $request->input('department_id'));
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $courses = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('admin.courses.index', compact('courses', 'departments'));
    }

    public function create()
    {
        $this->authorize('permission:manage-system');

        $programs = Program::orderBy('name')->get();

        return view('admin.courses.create', compact('programs'));
    }

    public function store(StoreCourseRequest $request)
    {
        $this->authorize('permission:manage-system');

        Course::create($request->validated());

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function show(Course $course)
    {
        $this->authorize('permission:manage-system');

        $course->load(['program.department.faculty', 'classSections']);

        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $this->authorize('permission:manage-system');

        $programs = Program::orderBy('name')->get();

        return view('admin.courses.edit', compact('course', 'programs'));
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->authorize('permission:manage-system');

        $course->update($request->validated());

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $this->authorize('permission:manage-system');

        if ($course->classSections()->count() > 0) {
            return redirect()->route('admin.courses.index')
                ->with('error', 'Cannot delete course with associated class sections.');
        }

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
