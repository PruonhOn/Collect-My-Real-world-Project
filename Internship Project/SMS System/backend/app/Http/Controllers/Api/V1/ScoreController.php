<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Score\BulkScoreRequest;
use App\Http\Requests\V1\Score\StoreScoreRequest;
use App\Http\Resources\ScoreResource;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\Score;
use App\Services\AuditService;
use App\Services\ScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoreController extends BaseApiController
{
    public function __construct(
        private readonly ScoreService $scores,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request, Course $course, Assessment $assessment): JsonResponse
    {
        if ($assessment->course_id !== $course->id) {
            abort(404, 'Assessment does not belong to this course.');
        }

        $query = $assessment->scores()->with('student.user');

        $paginator = $this->paginate($query, $request, ['student.user.name'], ['student_id'], 'score', 'desc');

        return response()->json([
            'data' => ScoreResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(BulkScoreRequest $request, Course $course, Assessment $assessment): JsonResponse
    {
        if ($assessment->course_id !== $course->id) {
            abort(404, 'Assessment does not belong to this course.');
        }

        $records = $this->scores->submitBulk($assessment, $request->input('rows'), $request->user()->getKey());
        $count = $records->count();
        $this->audit->record('scores.entered', $assessment, after: ['count' => $count]);

        return response()->json([
            'data' => ['message' => "Scores saved for {$count} student(s).", 'count' => $count],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(StoreScoreRequest $request, Course $course, Assessment $assessment, Score $score): JsonResponse
    {
        if ($assessment->course_id !== $course->id || $score->assessment_id !== $assessment->id) {
            abort(404, 'Score does not belong to this assessment.');
        }

        $score->score = $request->input('score');
        $score->remark = $request->string('remark')->toString();
        $score->save();

        $this->audit->record('scores.updated', $score, after: $score->toArray());

        return response()->json([
            'data' => new ScoreResource($score->load('student.user')),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Course $course, Assessment $assessment, Score $score): JsonResponse
    {
        $this->audit->record('scores.deleted', $score, before: $score->toArray());
        $score->delete();

        return response()->json([
            'data' => ['message' => 'Score deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
