<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportUsersRequest;
use App\Http\Requests\StoreTeacherRequest;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use App\Services\ImportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ImportService $importService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('permission:manage-system');

        $query = Teacher::with('user');

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $teachers = $query->latest()->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('admin.teachers.index', compact('teachers', 'departments'));
    }

    public function create()
    {
        $this->authorize('permission:manage-system');

        $departments = Department::orderBy('name')->get();

        return view('admin.teachers.create', compact('departments'));
    }

    public function store(StoreTeacherRequest $request)
    {
        $this->authorize('permission:manage-system');

        DB::beginTransaction();

        try {
            $userData = $request->only(['name', 'email', 'password']);
            $userData['password'] = Hash::make($userData['password'] ?? 'password');
            $userData['role'] = UserRole::Teacher;
            $userData['email_verified_at'] = now();

            $user = User::create($userData);

            $teacherData = $request->only([
                'employee_code',
                'department_id',
                'phone',
                'specialization',
                'designation',
                'qualification',
            ]);
            $teacherData['user_id'] = $user->id;

            Teacher::create($teacherData);

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.teachers.create')
                ->with('error', 'Failed to create teacher: ' . $e->getMessage());
        }
    }

    public function show(Teacher $teacher)
    {
        $this->authorize('permission:manage-system');

        $teacher->load([
            'user',
            'department.faculty',
            'classSections.course',
            'classSections.academicTerm',
        ]);

        $classSections = $teacher->classSections()
            ->with(['course', 'academicTerm'])
            ->withCount('enrollments')
            ->latest()
            ->get();

        return view('admin.teachers.show', compact('teacher', 'classSections'));
    }

    public function edit(Teacher $teacher)
    {
        $this->authorize('permission:manage-system');

        $teacher->load('user');
        $departments = Department::orderBy('name')->get();

        return view('admin.teachers.edit', compact('teacher', 'departments'));
    }

    public function update(StoreTeacherRequest $request, Teacher $teacher)
    {
        $this->authorize('permission:manage-system');

        DB::beginTransaction();

        try {
            $teacher->user->update($request->only(['name', 'email']));

            $teacher->update($request->only([
                'employee_code',
                'department_id',
                'phone',
                'specialization',
                'designation',
                'qualification',
            ]));

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.teachers.edit', $teacher)
                ->with('error', 'Failed to update teacher: ' . $e->getMessage());
        }
    }

    public function destroy(Teacher $teacher)
    {
        $this->authorize('permission:manage-system');

        if ($teacher->classSections()->count() > 0) {
            return redirect()->route('admin.teachers.index')
                ->with('error', 'Cannot delete teacher with assigned class sections.');
        }

        DB::beginTransaction();

        try {
            $teacher->delete();
            $teacher->user()->delete();

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('admin.teachers.index')
                ->with('error', 'Failed to delete teacher.');
        }
    }

    public function import()
    {
        $this->authorize('permission:manage-system');

        return view('admin.teachers.import');
    }

    public function doImport(ImportUsersRequest $request)
    {
        $this->authorize('permission:manage-system');

        try {
            $result = $this->importService->importTeachers($request->file('file'));

            $message = "Import completed: {$result['imported']} imported, {$result['skipped']} skipped.";
            if (!empty($result['errors'])) {
                $message .= ' Errors: ' . implode(' | ', array_slice($result['errors'], 0, 5));
            }

            return redirect()->route('admin.teachers.import')
                ->with($result['imported'] > 0 ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.teachers.import')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
