<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportUsersRequest;
use App\Http\Requests\StoreStudentRequest;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ImportService $importService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('permission:manage-system');

        $query = Student::with('user');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('student_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $students = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'departments'));
    }

    public function create()
    {
        $this->authorize('permission:manage-system');

        $departments = Department::orderBy('name')->get();
        $programs = Program::orderBy('name')->get();

        return view('admin.students.create', compact('departments', 'programs'));
    }

    public function store(StoreStudentRequest $request)
    {
        $this->authorize('permission:manage-system');

        DB::beginTransaction();

        try {
            $userData = $request->only(['name', 'email', 'password']);
            $userData['password'] = Hash::make($userData['password'] ?? 'password');
            $userData['role'] = UserRole::Student;
            $userData['email_verified_at'] = now();

            $user = User::create($userData);

            $studentData = $request->only([
                'student_code',
                'department_id',
                'program_id',
                'year',
            ]);
            $studentData['user_id'] = $user->id;

            Student::create($studentData);

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.students.create')
                ->with('error', 'Failed to create student: ' . $e->getMessage());
        }
    }

    public function show(Student $student)
    {
        $this->authorize('permission:manage-system');

        $student->load([
            'user',
            'department.faculty',
            'enrollments.classSection.course',
            'enrollments.classSection.academicTerm',
        ]);

        $enrollments = $student->enrollments()
            ->with(['classSection.course', 'classSection.academicTerm'])
            ->latest()
            ->get();

        $attendanceSummary = $student->attendanceRecords()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $totalRecords = $attendanceSummary->sum();
        $presentCount = $attendanceSummary->get('present', 0);
        $attendancePercentage = $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 1) : 0;

        return view('admin.students.show', compact('student', 'enrollments', 'attendanceSummary', 'attendancePercentage'));
    }

    public function edit(Student $student)
    {
        $this->authorize('permission:manage-system');

        $student->load('user');
        $departments = Department::orderBy('name')->get();
        $programs = Program::orderBy('name')->get();

        return view('admin.students.edit', compact('student', 'departments', 'programs'));
    }

    public function update(StoreStudentRequest $request, Student $student)
    {
        $this->authorize('permission:manage-system');

        DB::beginTransaction();

        try {
            $student->user->update($request->only(['name', 'email']));

            $student->update($request->only([
                'student_code',
                'department_id',
                'program_id',
                'year',
            ]));

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.students.edit', $student)
                ->with('error', 'Failed to update student: ' . $e->getMessage());
        }
    }

    public function destroy(Student $student)
    {
        $this->authorize('permission:manage-system');

        if ($student->enrollments()->where('status', 'active')->count() > 0) {
            return redirect()->route('admin.students.index')
                ->with('error', 'Cannot delete student with active enrollments.');
        }

        DB::beginTransaction();

        try {
            $student->delete();
            $student->user()->delete();

            DB::commit();

            return redirect()->route('admin.students.index')
                ->with('success', 'Student deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.students.index')
                ->with('error', 'Failed to delete student.');
        }
    }

    public function import()
    {
        $this->authorize('permission:manage-system');

        return view('admin.students.import');
    }

    public function doImport(ImportUsersRequest $request)
    {
        $this->authorize('permission:manage-system');

        try {
            $result = $this->importService->importStudents($request->file('file'));

            $message = "Import completed: {$result['imported']} imported, {$result['skipped']} skipped.";
            if (!empty($result['errors'])) {
                $message .= ' Errors: ' . implode(' | ', array_slice($result['errors'], 0, 5));
            }

            return redirect()->route('admin.students.import')
                ->with($result['imported'] > 0 ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.students.import')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
