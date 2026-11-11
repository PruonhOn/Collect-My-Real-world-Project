<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\CourseResource;
use App\Http\Resources\StudentFeeResource;
use App\Models\ContactInquiry;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use App\Services\AttendanceService;
use App\Services\DashboardService;
use App\Services\FeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly AttendanceService $attendance,
        private readonly FeeService $fees,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return match (true) {
            $user->can('dashboard.admin') => $this->admin($request),
            $user->can('dashboard.teacher') => $this->teacher($request),
            $user->hasRole('parent') => $this->parent($request),
            $user->student !== null => $this->student($request),
            default => $this->staff($request),
        };
    }

    public function staff(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'profile' => [
                    'name' => $user->name,
                    'role' => 'staff',
                ],
                'open_inquiries' => $request->user()->can('contact_inquiries.view')
                    ? ContactInquiry::query()->whereNull('responded_at')->count()
                    : null,
                'funds_collected_this_month' => $request->user()->can('payments.view')
                    ? (float) Payment::query()->where('status', 'confirmed')->where('paid_on', '>=', now()->startOfMonth())->sum('amount')
                    : null,
                'unread_messages' => $request->user()->receivedMessages()->whereNull('read_at')->count(),
            ],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function admin(Request $request): JsonResponse
    {
        $months = max(1, min((int) $request->integer('months', 6), 24));

        $stats = $this->dashboard->adminStats();
        $stats['fees'] = [
            'unpaid' => 0,
            'partial' => 0,
            'paid' => 0,
        ];

        return response()->json([
            'data' => [
                'stats' => $stats,
                'chart' => $this->dashboard->chartSeries($months),
            ],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function teacher(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if ($teacher === null) {
            abort(403, 'No teacher profile linked to this account.');
        }

        $stats = $this->dashboard->teacherStats($teacher);

        return response()->json([
            'data' => [
                'stats' => $stats,
                'courses' => CourseResource::collection($stats['todays_classes']),
            ],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function parent(Request $request): JsonResponse
    {
        $parent = $request->user()->parentProfile;

        if ($parent === null) {
            abort(403, 'No parent profile linked to this account.');
        }

        $children = $parent->students()->with([
            'user',
            'fees' => fn ($q) => $q->whereIn('status', ['unpaid', 'partial']),
            'courses.course',
        ])->get();

        $children->each(function (Student $child) {
            $child->setAttribute('attendance', $this->attendance->percentageForStudent($child->getKey()));
        });

        return response()->json([
            'data' => [
                'children' => $children->map(fn (Student $child) => [
                    'student' => [
                        'id' => $child->id,
                        'name' => $child->user?->name,
                        'student_code' => $child->student_code,
                        'status' => $child->status?->value,
                    ],
                    'attendance' => $child->attendance,
                    'fees' => StudentFeeResource::collection($child->fees)->resolve($request),
                ])->values(),
            ],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function student(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if ($student === null) {
            abort(403, 'No student profile linked to this account.');
        }

        $student->load(['courses' => fn ($q) => $q->withCount('assessments'), 'fees']);

        $statement = $this->fees->statement($student->getKey());

        return response()->json([
            'data' => [
                'enrolled_courses' => $student->courses->map(fn (Course $course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'code' => $course->code,
                    'type' => $course->type?->value,
                    'assessments_count' => $course->assessments_count,
                ])->values(),
                'attendance' => $this->attendance->percentageForStudent($student->getKey()),
                'fees' => [
                    'balance' => $statement['outstanding_balance'],
                    'total_billed' => $statement['total_amount'],
                    'total_paid' => $statement['total_paid'],
                ],
            ],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
