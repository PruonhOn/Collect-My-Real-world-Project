<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Fee\StoreFeeRequest;
use App\Http\Resources\StudentFeeResource;
use App\Models\Student;
use App\Models\StudentFee;
use App\Services\AuditService;
use App\Services\FeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeeController extends BaseApiController
{
    public function __construct(
        private readonly FeeService $fees,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = StudentFee::query()->with(['student.user', 'course']);

        if (! $request->user()->can('fees.view')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->whereIn('id', $this->scopedStudentIds($request));
            });
        }

        $query
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->integer('student_id')))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->when($request->filled('fee_status'), fn ($q) => $q->where('status', $request->input('fee_status')))
            ->when($request->boolean('overdue'), fn ($q) => $q->where('due_date', '<', now())->whereIn('status', ['unpaid', 'partial']));

        $paginator = $this->paginate($query, $request, ['title', 'student.user.name', 'student.student_code'], ['status'], 'due_date', 'asc');

        return response()->json([
            'data' => StudentFeeResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreFeeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $fee = $this->fees->createInvoice(Student::findOrFail($validated['student_id']), [
            'course_id' => $validated['course_id'] ?? null,
            'academic_year_id' => $validated['academic_year_id'] ?? null,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'due_date' => $validated['due_date'] ?? null,
        ]);

        $this->audit->record('fee.created', $fee, after: $fee->toArray());

        return response()->json([
            'data' => new StudentFeeResource($fee->load(['student.user', 'course'])),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Request $request, StudentFee $studentFee): JsonResponse
    {
        if (! $this->canAccessFee($request, $studentFee)) {
            abort(403);
        }

        return response()->json([
            'data' => new StudentFeeResource($studentFee->load(['student.user', 'course', 'payments'])),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, StudentFee $studentFee): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:191'],
            'amount' => ['sometimes', 'numeric', 'gt:0', 'gte:paid_amount'],
            'due_date' => ['nullable', 'date'],
        ]);

        $before = $studentFee->toArray();
        $studentFee->fill($validated)->save();
        $this->fees->refreshStatus($studentFee);
        $this->audit->record('fee.updated', $studentFee, before: $before, after: $studentFee->toArray());

        return response()->json([
            'data' => new StudentFeeResource($studentFee->load(['student.user', 'course'])),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function statement(Request $request, Student $student): JsonResponse
    {
        if (! $request->user()->can('fees.view') && ! in_array($student->id, $this->scopedStudentIds($request), true)) {
            abort(403);
        }

        $student->load(['user', 'courses', 'fees.payments']);

        $totalBilled = $student->fees->sum('amount');
        $totalPaid = $student->fees->sum('paid_amount');
        $balance = $totalBilled - $totalPaid;

        return response()->json([
            'data' => [
                'student' => $student->only(['id', 'student_code']),
                'student_name' => $student->user->name,
                'enrolled_courses' => $student->courses->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'fees' => StudentFeeResource::collection($student->fees)->resolve($request),
                'summary' => [
                    'total_billed' => round($totalBilled, 2),
                    'total_paid' => round($totalPaid, 2),
                    'balance' => round($balance, 2),
                ],
            ],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $query = StudentFee::query();

        if (! $request->user()->can('fees.view')) {
            $query->whereHas('student', fn ($q) => $q->whereIn('id', $this->scopedStudentIds($request)));
        }

        $rows = (clone $query)->selectRaw('status, COUNT(*) as count, SUM(amount) as billed, SUM(paid_amount) as paid')
            ->groupBy('status')->get()->keyBy('status');

        $overdue = (clone $query)
            ->where('due_date', '<', now())
            ->whereIn('status', ['unpaid', 'partial'])
            ->get()
            ->sum(fn (StudentFee $fee) => $fee->balance());

        return response()->json([
            'data' => [
                'by_status' => [
                    'unpaid' => $rows->get('unpaid'),
                    'partial' => $rows->get('partial'),
                    'paid' => $rows->get('paid'),
                    'waived' => $rows->get('waived'),
                ],
                'overdue_balance' => round((float) $overdue, 2),
            ],
            'meta' => null,
            'errors' => null,
        ]);
    }

    protected function scopedStudentIds(Request $request): array
    {
        $user = $request->user();

        if ($user->student) {
            return [$user->student->id];
        }

        if ($user->parentProfile) {
            return $user->parentProfile->students()->pluck('students.id')->all();
        }

        return [];
    }

    protected function canAccessFee(Request $request, StudentFee $fee): bool
    {
        if ($request->user()->can('fees.view')) {
            return true;
        }

        return in_array($fee->student_id, $this->scopedStudentIds($request), true);
    }
}
