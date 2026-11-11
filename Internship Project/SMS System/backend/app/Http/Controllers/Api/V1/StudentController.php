<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Requests\V1\Student\StoreStudentRequest;
use App\Http\Requests\V1\Student\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends BaseApiController
{
    public function __construct(
        private readonly UserService $users,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Student::query()
            ->with('user')
            ->withCount('courses')
            ->with('parents.user');

        $paginator = $this->paginate($query, $request, ['student_code', 'khmer_name', 'user.name', 'user.email'], ['status']);

        return response()->json([
            'data' => StudentResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $student = DB::transaction(function () use ($data) {
            $user = $this->users->create($data, [UserRole::Student->value]);

            return Student::create([
                'user_id' => $user->getKey(),
                'student_code' => $data['student_code'],
                'khmer_name' => $data['khmer_name'] ?? null,
                'gender' => $data['gender'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
                'guardian_name' => $data['guardian_name'] ?? null,
                'guardian_phone' => $data['guardian_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'active',
                'enrolled_on' => $data['enrolled_on'] ?? now()->toDateString(),
            ])->load('user');
        });

        $this->audit->record('student.created', $student, after: $student->toArray());

        return response()->json([
            'data' => new StudentResource($student),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Student $student): JsonResponse
    {
        $student->load(['user', 'parents.user', 'courses' => fn ($q) => $q->with('academicYear')->withCount('students'), 'enrollments.grade']);

        return response()->json([
            'data' => new StudentResource($student),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $student) {
            $this->users->update($student->user, $data);

            $student->fill(array_merge(
                $data,
                [
                    'student_code' => $data['student_code'] ?? $student->student_code,
                    'khmer_name' => $data['khmer_name'] ?? $student->khmer_name,
                ],
            ))->save();
        });

        $student->load('user');
        $this->audit->record('student.updated', $student, after: $student->toArray());

        return response()->json([
            'data' => new StudentResource($student),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function toggle(Request $request, Student $student): JsonResponse
    {
        $active = (bool) $request->boolean('active', ! $student->user->is_active);
        $this->users->setActive($student->user, $active);
        $this->audit->record($active ? 'student.activated' : 'student.deactivated', $student, after: ['is_active' => $active]);

        return response()->json([
            'data' => ['message' => $active ? 'Student activated.' : 'Student deactivated.', 'is_active' => $active],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Student $student): JsonResponse
    {
        $this->audit->record('student.deleted', $student, before: $student->toArray());
        $student->user()->update(['is_active' => false]);
        $student->delete();

        return response()->json([
            'data' => ['message' => 'Student deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
