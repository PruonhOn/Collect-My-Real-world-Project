<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Requests\V1\Teacher\StoreTeacherRequest;
use App\Http\Requests\V1\Teacher\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TeacherController extends BaseApiController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Teacher::query()->with('user')->withCount('courses');

        $paginator = $this->paginate($query, $request, ['employee_code', 'user.name', 'user.email'], ['user.is_active']);

        return response()->json([
            'data' => TeacherResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $data = $request->validated();

        $teacher = DB::transaction(function () use ($data) {
            $user = $this->users->create($data, [UserRole::Teacher->value]);
            $teacher = Teacher::create([
                'user_id' => $user->getKey(),
                'employee_code' => $data['employee_code'],
                'hire_date' => $data['hire_date'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'specialization' => $data['specialization'] ?? null,
                'bio' => $data['bio'] ?? null,
            ]);

            return $teacher->load('user');
        });

        $this->audit->record('teacher.created', $teacher, after: $teacher->toArray());

        return response()->json([
            'data' => new TeacherResource($teacher),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Teacher $teacher): JsonResponse
    {
        $teacher->load(['user', 'courses' => fn ($q) => $q->withCount('students')]);

        return response()->json([
            'data' => new TeacherResource($teacher),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(UpdateTeacherRequest $request, Teacher $teacher): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $teacher) {
            $this->users->update($teacher->user, $data);

            $teacher->fill([
                'employee_code' => $data['employee_code'] ?? $teacher->employee_code,
                'hire_date' => $data['hire_date'] ?? $teacher->hire_date,
                'qualification' => $data['qualification'] ?? $teacher->qualification,
                'specialization' => $data['specialization'] ?? $teacher->specialization,
                'bio' => $data['bio'] ?? $teacher->bio,
            ])->save();
        });

        $teacher->load('user');
        $this->audit->record('teacher.updated', $teacher, after: $teacher->toArray());

        return response()->json([
            'data' => new TeacherResource($teacher),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function toggle(Request $request, Teacher $teacher): JsonResponse
    {
        $active = (bool) $request->boolean('active', ! $teacher->user->is_active);

        $this->users->setActive($teacher->user, $active);
        $this->audit->record($active ? 'teacher.activated' : 'teacher.deactivated', $teacher, after: ['is_active' => $active]);

        return response()->json([
            'data' => ['message' => $active ? 'Teacher activated.' : 'Teacher deactivated.', 'is_active' => $active],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function assignCourses(Request $request, Teacher $teacher): JsonResponse
    {
        $validated = $request->validate([
            'course_ids' => ['required', 'array'],
            'course_ids.*' => ['integer', Rule::exists('courses', 'id')],
        ]);

        $teacher->courses()->sync(array_map('intval', $validated['course_ids']));
        $this->audit->record('teacher.courses_assigned', $teacher, after: $validated);

        return response()->json([
            'data' => ['message' => 'Course assignments updated.', 'course_ids' => $validated['course_ids']],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Teacher $teacher): JsonResponse
    {
        if ($teacher->courses()->exists()) {
            throw ValidationException::withMessages([
                'teacher' => 'Reassign this teacher\'s courses before deleting the teacher record.',
            ]);
        }

        $this->audit->record('teacher.deleted', $teacher, before: $teacher->toArray());
        $teacher->user()->update(['is_active' => false]);
        $teacher->delete();

        return response()->json([
            'data' => ['message' => 'Teacher deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
