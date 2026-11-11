<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClassSectionRequest;
use App\Models\AcademicTerm;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ClassSectionController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('permission:manage-system');

        $query = ClassSection::with(['course', 'teacher', 'academicTerm'])
            ->withCount('enrollments');

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->input('course_id'));
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('academic_term_id')) {
            $query->where('term_id', $request->input('academic_term_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('course', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%");
                  });
            });
        }

        $sections = $query->latest()->paginate(15)->withQueryString();
        $courses = Course::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('id')->get();
        $terms = AcademicTerm::latest('start_date')->get();

        return view('admin.class-sections.index', [
            'classSections' => $sections,
            'courses' => $courses,
            'teachers' => $teachers,
            'terms' => $terms,
        ]);
    }

    public function create()
    {
        $this->authorize('permission:manage-system');

        $courses = Course::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('id')->get();
        $terms = AcademicTerm::latest('start_date')->get();

        return view('admin.class-sections.create', compact('courses', 'teachers', 'terms'));
    }

    public function store(StoreClassSectionRequest $request)
    {
        $this->authorize('permission:manage-system');

        ClassSection::create($request->validated());

        return redirect()->route('admin.class-sections.index')
            ->with('success', 'Class section created successfully.');
    }

    public function show(ClassSection $classSection)
    {
        $this->authorize('permission:manage-system');

        $classSection->load([
            'course.program.department.faculty',
            'teacher.user',
            'academicTerm',
            'enrollments.student.user',
        ]);

        $enrolledStudents = $classSection->enrollments()
            ->with('student.user')
            ->where('status', 'active')
            ->get();

        return view('admin.class-sections.show', compact('classSection', 'enrolledStudents'));
    }

    public function edit(ClassSection $classSection)
    {
        $this->authorize('permission:manage-system');

        $courses = Course::orderBy('name')->get();
        $teachers = Teacher::with('user')->orderBy('id')->get();
        $terms = AcademicTerm::latest('start_date')->get();

        return view('admin.class-sections.edit', compact('classSection', 'courses', 'teachers', 'terms'));
    }

    public function update(StoreClassSectionRequest $request, ClassSection $classSection)
    {
        $this->authorize('permission:manage-system');

        $classSection->update($request->validated());

        return redirect()->route('admin.class-sections.index')
            ->with('success', 'Class section updated successfully.');
    }

    public function destroy(ClassSection $classSection)
    {
        $this->authorize('permission:manage-system');

        if ($classSection->enrollments()->count() > 0) {
            return redirect()->route('admin.class-sections.index')
                ->with('error', 'Cannot delete section with enrolled students.');
        }

        if ($classSection->attendanceSessions()->count() > 0) {
            return redirect()->route('admin.class-sections.index')
                ->with('error', 'Cannot delete section with attendance history.');
        }

        $classSection->delete();

        return redirect()->route('admin.class-sections.index')
            ->with('success', 'Class section deleted successfully.');
    }
}
