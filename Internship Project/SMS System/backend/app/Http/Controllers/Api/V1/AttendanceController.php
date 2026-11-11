<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Attendance\BulkAttendanceRequest;
use App\Http\Requests\V1\Attendance\StoreAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Student;
use App\Services\AttendanceService;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends BaseApiController
{
    public function __construct(
        private readonly AttendanceService $attendance,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request, Course $course): JsonResponse
    {
        if (! $this->ownsCourse($request, $course)) {
            abort(403, 'You can only view attendance for courses you teach.');
        }

        $query = $course->attendance()->with(['student.user', 'schedule'])
            ->when($request->filled('date'), fn ($q) => $q->whereDate('date', $request->date('date')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->integer('student_id')));

        $paginator = $this->paginate($query, $request, [], ['status', 'date'], 'date', 'desc');

        return response()->json([
            'data' => AttendanceResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function register(BulkAttendanceRequest $request, Course $course): JsonResponse
    {
        if (! $this->ownsCourse($request, $course)) {
            abort(403, 'You can only record attendance for courses you teach.');
        }

        $scheduleId = $request->integer('schedule_id');

        if ($scheduleId > 0 && $course->schedules()->whereKey($scheduleId)->doesntExist()) {
            return response()->json([
                'data' => null,
                'errors' => ['schedule_id' => 'Schedule does not belong to this course.'],
            ], 422);
        }

        $rows = collect($request->input('rows'))->map(fn (array $row) => [
            'student_id' => $row['student_id'],
            'status' => $row['status'],
            'remark' => $row['remark'] ?? null,
            'schedule_id' => $scheduleId > 0 ? $scheduleId : null,
        ])->all();

        $records = $this->attendance->recordBulk(
            $course,
            $request->date('date')->toDateString(),
            $rows,
            $request->user()->getKey(),
        );

        $count = $records->count();
        $this->audit->record('attendance.registered', $course, after: ['date' => $request->date('date')->toDateString(), 'count' => $count]);

        return response()->json([
            'data' => ['message' => "Attendance recorded for {$count} student(s).", 'count' => $count],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function mark(StoreAttendanceRequest $request, Course $course): JsonResponse
    {
        if (! $this->ownsCourse($request, $course)) {
            abort(403, 'You can only record attendance for courses you teach.');
        }

        $attendance = $this->attendance->record(
            $course,
            Student::findOrFail($request->integer('student_id')),
            $request->date('date')->toDateString(),
            [
                'status' => $request->string('status')->toString(),
                'remark' => $request->string('remark')->toString(),
                'schedule_id' => $request->integer('schedule_id') ?: null,
            ],
            $request->user()->getKey(),
        );

        $this->audit->record('attendance.marked', $attendance, after: $attendance->toArray());

        return response()->json([
            'data' => new AttendanceResource($attendance->load('student.user')),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $courses = $this->visibleCourses($request);

        $query = Attendance::query()
            ->whereIn('course_id', $courses->modelKeys())
            ->whereDate('date', today()->toDateString())
            ->with(['student.user', 'course']);

        $paginator = $this->paginate($query, $request, [], ['course_id', 'status'], 'date', 'desc');

        return response()->json([
            'data' => AttendanceResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    protected function ownsCourse(Request $request, Course $course): bool
    {
        if ($request->user()->can('attendance.record')) {
            return true;
        }

        return $course->teacher_id === $request->user()->teacher?->id;
    }

    protected function visibleCourses(Request $request): Collection
    {
        if ($request->user()->can('attendance.record')) {
            return Course::all();
        }

        $teacher = $request->user()->teacher;

        return $teacher?->courses ?? collect();
    }
}
