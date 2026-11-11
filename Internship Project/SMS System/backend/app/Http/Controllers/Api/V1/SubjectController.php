<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends BaseApiController
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Subject::query()->with('category')->orderBy('name');

        return response()->json([
            'data' => SubjectResource::collection($query->get()),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:subjects,name'],
            'code' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $subject = Subject::create($validated);
        $this->audit->record('subject.created', $subject, after: $subject->toArray());

        return response()->json([
            'data' => new SubjectResource($subject->load('category')),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Subject $subject): JsonResponse
    {
        return response()->json([
            'data' => new SubjectResource($subject->load('category')),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, Subject $subject): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:191', 'unique:subjects,name,'.$subject->id],
            'code' => ['nullable', 'string', 'max:50'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $subject->fill($validated)->save();
        $this->audit->record('subject.updated', $subject, after: $subject->toArray());

        return response()->json([
            'data' => new SubjectResource($subject->load('category')),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Subject $subject): JsonResponse
    {
        if ($subject->courses()->exists()) {
            return response()->json([
                'data' => ['message' => 'Subject is used by courses and cannot be deleted.'],
                'meta' => null,
                'errors' => null,
            ], 409);
        }

        $this->audit->record('subject.deleted', $subject, before: $subject->toArray());
        $subject->delete();

        return response()->json([
            'data' => ['message' => 'Subject deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
