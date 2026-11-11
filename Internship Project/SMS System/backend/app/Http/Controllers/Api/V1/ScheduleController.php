<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Course\StoreScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Course;
use App\Models\Schedule;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends BaseApiController
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Course $course, Request $request): JsonResponse
    {
        $query = $course->schedules();

        $paginator = $this->paginate($query, $request, [], ['day_of_week'], 'day_of_week', 'asc');

        return response()->json([
            'data' => ScheduleResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreScheduleRequest $request, Course $course): JsonResponse
    {
        $schedule = $course->schedules()->create($request->validated());
        $this->audit->record('schedule.created', $schedule, after: $schedule->toArray());

        return response()->json([
            'data' => new ScheduleResource($schedule),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Course $course, Schedule $schedule): JsonResponse
    {
        if ($schedule->course_id !== $course->id) {
            return response()->json([
                'data' => null,
                'errors' => ['schedule' => 'Schedule does not belong to this course.'],
            ], 404);
        }

        return response()->json([
            'data' => new ScheduleResource($schedule),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, Course $course, Schedule $schedule): JsonResponse
    {
        $validated = $request->validate([
            'day_of_week' => ['sometimes', 'integer', 'between:1,7'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:100'],
        ]);

        if ($schedule->course_id !== $course->id) {
            return response()->json([
                'data' => null,
                'errors' => ['schedule' => 'Schedule does not belong to this course.'],
            ], 404);
        }

        $schedule->fill($validated)->save();
        $this->audit->record('schedule.updated', $schedule, after: $schedule->toArray());

        return response()->json([
            'data' => new ScheduleResource($schedule),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Course $course, Schedule $schedule): JsonResponse
    {
        $this->audit->record('schedule.deleted', $schedule, before: $schedule->toArray());
        $schedule->delete();

        return response()->json([
            'data' => ['message' => 'Schedule deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
