<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\GradeResource;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Grade::query()
            ->with(['enrollment.student.user', 'enrollment.course']);

        if ($request->user()->hasRole('teacher')) {
            $teacherId = $request->user()->teacher?->id;
            $query->whereHas('enrollment', fn ($q) => $q->whereHas('course', fn ($c) => $c->where('teacher_id', $teacherId)));
        }

        $query
            ->when($request->filled('course_id'), fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('course_id', $request->integer('course_id'))))
            ->when($request->filled('student_id'), fn ($q) => $q->whereHas('enrollment', fn ($e) => $e->where('student_id', $request->integer('student_id'))));

        $paginator = $this->paginate($query, $request, [], [], 'updated_at', 'desc');

        return response()->json([
            'data' => GradeResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function show(Request $request, Grade $grade): JsonResponse
    {
        if ($request->user()->hasRole('teacher')) {
            $teacherId = $request->user()->teacher?->id;

            if ($grade->enrollment->course->teacher_id !== $teacherId) {
                abort(403);
            }
        }

        return response()->json([
            'data' => new GradeResource($grade->load(['enrollment.student.user', 'enrollment.course'])),
            'meta' => null,
            'errors' => null,
        ]);
    }
}
