<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\StudentFee;
use App\Services\AuditService;
use App\Services\FileService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends BaseApiController
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly FileService $files,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()->with(['studentFee', 'student.user', 'processedBy']);

        if (! $request->user()->can('payments.view')) {
            $query->whereIn('student_id', $this->scopedStudentIds($request));
        }

        $query
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->integer('student_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('paid_on', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('paid_on', '<=', $request->date('to')));

        $paginator = $this->paginate($query, $request, ['transaction_id', 'student.user.name'], ['status'], 'paid_on', 'desc');

        return response()->json([
            'data' => PaymentResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function charge(Request $request, StudentFee $studentFee): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['nullable', 'string', 'max:50'],
            'gateway' => ['nullable', 'string', 'max:50'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:191'],
        ]);

        $this->assertFeeAccess($request, $studentFee);

        $parentId = $request->user()->parentProfile?->id;
        $payment = $this->payments->charge($studentFee, $validated, $parentId, $request->user()->getKey());

        $this->audit->record('payment.created', $payment, after: $payment->toArray());

        return response()->json([
            'data' => new PaymentResource($payment->load(['studentFee', 'student.user'])),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        $this->payments->confirm($payment);
        $this->audit->record('payment.confirmed', $payment, after: $payment->toArray());

        return response()->json([
            'data' => new PaymentResource($payment->load(['studentFee', 'student.user', 'receipt'])),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function refund(Request $request, Payment $payment): JsonResponse
    {
        $this->payments->refund($payment);
        $this->audit->record('payment.refunded', $payment, after: $payment->toArray());

        return response()->json([
            'data' => new PaymentResource($payment->load(['studentFee', 'student.user'])),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function receipt(Request $request, Payment $payment): BinaryFileResponse
    {
        if (! $request->user()->can('payments.view') && ! in_array($payment->student_id, $this->scopedStudentIds($request), true)) {
            abort(403);
        }

        $receipt = $payment->receipt()->first();

        if ($receipt === null) {
            abort(404, 'No receipt issued for this payment.');
        }

        $file = $receipt->file;

        if ($file === null) {
            abort(404, 'Receipt file is missing.');
        }

        return $this->files->download($file, 'receipt-'.$receipt->receipt_no.'.pdf');
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

    protected function assertFeeAccess(Request $request, StudentFee $fee): void
    {
        if ($request->user()->can('payments.process')) {
            return;
        }

        if ($request->user()->can('payments.make') && in_array($fee->student_id, $this->scopedStudentIds($request), true)) {
            return;
        }

        abort(403, 'You are not allowed to pay this fee.');
    }
}
