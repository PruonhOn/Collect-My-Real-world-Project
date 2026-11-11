<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function __construct(protected StudentService $studentService)
    {
    }

    public function index(Request $request)
    {
        $query = Student::with(['parents', 'enrollments']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('age')) {
            $query->where('age', $request->age);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->latest()->paginate(15)->withQueryString();

        return view('admin.students.index', compact('students'));
    }

    public function create()
    {
        $parents = ParentGuardian::orderBy('full_name')->get();

        return view('admin.students.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:male,female'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $student = $this->studentService->create($data);

        if ($request->filled('parent_id')) {
            $student->parents()->attach($request->parent_id, ['relation' => $request->relation ?? 'អាណាព្យាបាល']);
        }

        return redirect()->route('admin.students.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(Student $student)
    {
        $student->load([
            'parents',
            'enrollments.course',
            'lessonProgress.lesson',
            'grades',
            'certificates.course',
        ]);

        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $parents = ParentGuardian::orderBy('full_name')->get();

        return view('admin.students.edit', compact('student', 'parents'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:male,female'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,graduated,suspended'],
        ]);

        $data['age'] = calculate_age($data['date_of_birth']);
        $this->studentService->validateAge($data['age']);

        $student->update($data);

        if ($request->has('parent_id')) {
            $student->parents()->syncWithoutDetaching([$request->parent_id => ['relation' => $request->relation ?? 'អាណាព្យាបាល']]);
        }

        return redirect()->route('admin.students.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    public function toggle(Student $student)
    {
        $student->update(['status' => $student->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', __('messages.updated_successfully'));
    }
}
