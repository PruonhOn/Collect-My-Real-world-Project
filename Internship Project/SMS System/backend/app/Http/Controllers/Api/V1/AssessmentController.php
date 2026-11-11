<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Assessment\StoreAssessmentRequest;
use App\Http\Resources\AssessmentResource;
use App\Http\Resources\ScoreResource;
use App\Models\Assessment;
use App\Models\Course;
use App\Services\AuditService;
use App\Services\GradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AssessmentController extends BaseApiController
{
    public function __construct(
        private readonly GradeService $grades,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request, Course $course): JsonResponse
    {
        $query = $course->assessments()->withCount('scores');

        $paginator = $this->paginate($query, $request, [], ['type']);

        return response()->json([
            'data' => AssessmentResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreAssessmentRequest $request, Course $course): JsonResponse
    {
        $assessment = $this->grades->createAssessment($course, $request->validated());
        $this->audit->record('assessment.created', $assessment, after: $assessment->toArray());

        return response()->json([
            'data' => new AssessmentResource($assessment),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Course $course, Assessment $assessment): JsonResponse
    {
        $assessment->loadCount('scores');

        return response()->json([
            'data' => new AssessmentResource($assessment),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, Course $course, Assessment $assessment): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:191'],
            'type' => ['sometimes', 'string', 'max:50'],
            'max_score' => ['sometimes', 'numeric', 'gt:0'],
            'weight' => ['sometimes', 'numeric', 'gte:0', 'lte:100'],
            'date' => ['sometimes', 'date'],
        ]);

        $assessment->fill($validated)->save();
        $this->grades->assertWeightsWithin($course);
        $this->audit->record('assessment.updated', $assessment, after: $assessment->toArray());

        return response()->json([
            'data' => new AssessmentResource($assessment),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function results(Course $course, Assessment $assessment): JsonResponse
    {
        if ($assessment->course_id !== $course->id) {
            abort(404, 'Assessment does not belong to this course.');
        }

        $scores = $assessment->scores()
            ->with('student.user')
            ->orderByDesc('score')
            ->get();

        return response()->json([
            'data' => ScoreResource::collection($scores),
            'meta' => [
                'summary' => [
                    'total' => $scores->count(),
                    'average' => round($scores->avg('score'), 2),
                    'max' => $scores->max('score'),
                    'min' => $scores->min('score'),
                ],
            ],
            'errors' => null,
        ]);
    }

    public function destroy(Course $course, Assessment $assessment): JsonResponse
    {
        if ($assessment->scores()->exists()) {
            throw ValidationException::withMessages([
                'assessment' => 'Assessment already has scores; delete the scores first.',
            ]);
        }

        $this->audit->record('assessment.deleted', $assessment, before: $assessment->toArray());
        $assessment->delete();

        return response()->json([
            'data' => ['message' => 'Assessment deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
