<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Requests\V1\Parent\StoreParentRequest;
use App\Http\Resources\ParentResource;
use App\Http\Resources\StudentResource;
use App\Models\Guardian;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ParentController extends BaseApiController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Guardian::query()->with('user')->with('students');

        $paginator = $this->paginate($query, $request, ['user.name', 'user.email']);

        return response()->json([
            'data' => ParentResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreParentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $parent = DB::transaction(function () use ($data) {
            $user = $this->users->create($data, [UserRole::Parent->value]);

            $parent = Guardian::create([
                'user_id' => $user->getKey(),
                'occupation' => $data['occupation'] ?? null,
            ]);

            if (! empty($data['students'])) {
                $parent->students()->sync(array_map('intval', $data['students']));
            }

            return $parent->load(['user', 'students.user']);
        });

        $this->audit->record('parent.created', $parent, after: $parent->toArray());

        return response()->json([
            'data' => new ParentResource($parent),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Guardian $parent): JsonResponse
    {
        $parent->load(['user', 'students.user']);

        return response()->json([
            'data' => new ParentResource($parent),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, Guardian $parent): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'phone' => ['nullable', 'string', 'max:40'],
            'occupation' => ['nullable', 'string', 'max:191'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->users->update($parent->user, $validated);
        $parent->fill(['occupation' => $validated['occupation'] ?? $parent->occupation])->save();
        $parent->load('user');
        $this->audit->record('parent.updated', $parent, after: $parent->toArray());

        return response()->json([
            'data' => new ParentResource($parent),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function children(Guardian $parent): JsonResponse
    {
        $parent->load('students.user');

        return response()->json([
            'data' => StudentResource::collection($parent->students),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function linkChildren(Request $request, Guardian $parent): JsonResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')],
        ]);

        $parent->students()->sync(array_map('intval', $validated['student_ids']));
        $this->audit->record('parent.children_updated', $parent, after: $validated);

        return response()->json([
            'data' => ['message' => 'Student links updated.', 'student_ids' => $validated['student_ids']],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Guardian $parent): JsonResponse
    {
        $this->audit->record('parent.deleted', $parent, before: $parent->toArray());
        $parent->user()->update(['is_active' => false]);
        $parent->delete();

        return response()->json([
            'data' => ['message' => 'Parent deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
