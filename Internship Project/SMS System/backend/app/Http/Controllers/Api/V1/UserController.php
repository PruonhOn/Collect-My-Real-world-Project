<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\User\StoreUserRequest;
use App\Http\Requests\V1\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class UserController extends BaseApiController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with(['roles', 'teacher', 'student', 'staff', 'parentProfile'])
            ->whereNot('id', $request->user()->getKey());

        $paginator = $this->paginate($query, $request, ['name', 'email'], ['is_active']);

        return response()->json([
            'data' => UserResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated(), $request->input('roles', []));

        $this->audit->record('user.created', $user, after: $user->toArray());

        return response()->json([
            'data' => new UserResource($user->load(['roles'])),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->load(['roles', 'teacher', 'student', 'staff', 'parentProfile', 'avatar'])),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $before = $user->toArray();
        $this->users->update($user, $request->validated());
        $this->audit->record('user.updated', $user, before: $before, after: $user->refresh()->toArray());

        return response()->json([
            'data' => new UserResource($user->load(['roles'])),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->getKey() === $request->user()->getKey()) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        $this->audit->record('user.deleted', $user, before: $user->toArray());
        $user->delete();

        return response()->json([
            'data' => ['message' => 'User deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
