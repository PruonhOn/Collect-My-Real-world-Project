<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Requests\V1\Staff\StoreStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StaffController extends BaseApiController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Staff::query()->with('user');

        $paginator = $this->paginate($query, $request, ['employee_code', 'position', 'user.name', 'user.email']);

        return response()->json([
            'data' => StaffResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $data = $request->validated();

        $staff = DB::transaction(function () use ($data) {
            $user = $this->users->create($data, [UserRole::Staff->value]);

            return Staff::create([
                'user_id' => $user->getKey(),
                'employee_code' => $data['employee_code'],
                'position' => $data['position'] ?? null,
                'department' => $data['department'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
            ])->load('user');
        });

        $this->audit->record('staff.created', $staff, after: $staff->toArray());

        return response()->json([
            'data' => new StaffResource($staff),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        return response()->json([
            'data' => new StaffResource($staff->load('user')),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(Request $request, Staff $staff): JsonResponse
    {
        $validated = $request->validate([
            'position' => ['nullable', 'string', 'max:191'],
            'department' => ['nullable', 'string', 'max:191'],
            'hire_date' => ['nullable', 'date'],
            'employee_code' => ['sometimes', 'string', 'max:50', Rule::unique('staff', 'employee_code')->ignore($staff->id)],
        ]);

        $staff->fill($validated)->save();
        $staff->load('user');
        $this->audit->record('staff.updated', $staff, after: $staff->toArray());

        return response()->json([
            'data' => new StaffResource($staff),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function toggle(Request $request, Staff $staff): JsonResponse
    {
        $active = (bool) $request->boolean('active', ! $staff->user->is_active);
        $this->users->setActive($staff->user, $active);
        $this->audit->record($active ? 'staff.activated' : 'staff.deactivated', $staff, after: ['is_active' => $active]);

        return response()->json([
            'data' => ['message' => $active ? 'Staff activated.' : 'Staff deactivated.', 'is_active' => $active],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Staff $staff): JsonResponse
    {
        $this->audit->record('staff.deleted', $staff, before: $staff->toArray());
        $staff->user()->update(['is_active' => false]);
        $staff->delete();

        return response()->json([
            'data' => ['message' => 'Staff deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
