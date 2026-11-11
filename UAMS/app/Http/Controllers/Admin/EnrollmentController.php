<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnrollStudentRequest;
use App\Models\ClassSection;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('permission:manage-system');

        $query = Enrollment::with(['student.user', 'classSection.course', 'classSection.academicTerm']);

        if ($request->filled('class_section_id')) {
            $query->where('class_section_id', $request->input('class_section_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where('student_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $enrollments = $query->latest()->paginate(15)->withQueryString();

        $classSections = ClassSection::with('course')->orderBy('name')->get();
        $students = Student::with('user')->orderBy('student_code')->get();

        return view('admin.enrollments.index', compact('enrollments', 'classSections', 'students'));
    }

    public function show(Enrollment $enrollment)
    {
        $this->authorize('permission:manage-system');

        $enrollment->load([
            'student.user',
            'student.department',
            'classSection.course.program.department',
            'classSection.teacher.user',
            'classSection.academicTerm',
        ]);

        $attendanceRecords = $enrollment->attendanceRecords()
            ->with('attendanceSession')
            ->latest()
            ->get();

        return view('admin.enrollments.show', compact('enrollment', 'attendanceRecords'));
    }

    public function store(EnrollStudentRequest $request)
    {
        $this->authorize('permission:manage-system');

        try {
            $student = \App\Models\Student::findOrFail($request->input('student_id'));
            $classSection = \App\Models\ClassSection::findOrFail($request->input('class_section_id'));

            $enrollment = $this->enrollmentService->enrollStudent($student, $classSection);

            return redirect()->route('admin.enrollments.index', [
                'class_section_id' => $request->input('class_section_id'),
            ])->with('success', 'Student enrolled successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(Enrollment $enrollment)
    {
        $this->authorize('permission:manage-system');

        try {
            $this->enrollmentService->dropStudent($enrollment);

            return redirect()->route('admin.enrollments.index', [
                'class_section_id' => $enrollment->class_section_id,
            ])->with('success', 'Student dropped from section successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
