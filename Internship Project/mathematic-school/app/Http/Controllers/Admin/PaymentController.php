<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ParentGuardian;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['student', 'course', 'parentGuardian']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', fn ($sq) => $sq->where('full_name', 'like', "%{$search}%"))
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        $payments = $query->latest()->paginate(15)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function create()
    {
        $students = Student::orderBy('full_name')->get();
        $courses = Course::orderBy('title')->get();
        $enrollments = Enrollment::orderBy('id', 'desc')->get();

        return view('admin.payments.create', compact('students', 'courses', 'enrollments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'parent_id' => ['nullable', 'exists:parents,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'enrollment_id' => ['nullable', 'exists:enrollments,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['nullable', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,failed,refunded'],
            'paid_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if (empty($data['paid_date']) && $data['status'] === 'paid') {
            $data['paid_date'] = now();
        }

        Payment::create($data);

        return redirect()->route('admin.payments.index')
            ->with('success', __('messages.created_successfully'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['student', 'course', 'parentGuardian', 'enrollment']);

        return view('admin.payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $students = Student::orderBy('full_name')->get();
        $courses = Course::orderBy('title')->get();

        return view('admin.payments.edit', compact('payment', 'students', 'courses'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'parent_id' => ['nullable', 'exists:parents,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['nullable', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,paid,failed,refunded'],
            'paid_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment->update($data);

        return redirect()->route('admin.payments.index')
            ->with('success', __('messages.updated_successfully'));
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return back()->with('success', __('messages.deleted_successfully'));
    }

    public function updateStatus(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,failed,refunded'],
        ]);

        $payment->update([
            'status' => $data['status'],
            'paid_date' => $data['status'] === 'paid' ? now() : $payment->paid_date,
        ]);

        return back()->with('success', __('messages.updated_successfully'));
    }
}
