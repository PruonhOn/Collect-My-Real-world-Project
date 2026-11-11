<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CourseStatus;
use App\Http\Requests\V1\Course\StoreCourseRequest;
use App\Http\Requests\V1\Course\UpdateCourseRequest;
use App\Http\Resources\CourseEnrollmentResource;
use App\Http\Resources\CourseResource;
use App\Http\Resources\StudentResource;
use App\Models\Course;
use App\Models\Student;
use App\Services\AuditService;
use App\Services\EnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class CourseController extends BaseApiController
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly AuditService $audit,
    ) {}

    /**
     * Courses assigned to the currently authenticated teacher.
     */
    public function myCourses(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if ($teacher === null) {
            abort(403, 'No teacher profile linked to this account.');
        }

        $query = Course::query()
            ->where('teacher_id', $teacher->getKey())
            ->with(['category', 'academicYear', 'subject'])
            ->withCount(['students', 'enrollments']);

        $paginator = $this->paginate($query, $request, ['name', 'code'], ['status', 'type']);

        return response()->json([
            'data' => CourseResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $type = $request->string('type');
        $query = Course::query()
            ->with(['teacher.user', 'category', 'academicYear', 'subject'])
            ->withCount(['students', 'enrollments'])
            ->when($type->isNotEmpty(), fn ($q) => $q->where('type', $type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('academic_year_id'), fn ($q) => $q->where('academic_year_id', $request->integer('academic_year_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('published'), fn ($q) => $q->where('is_published', $request->boolean('published')));

        $paginator = $this->paginate($query, $request, ['name', 'code', 'teacher.user.name'], ['status', 'type']);

        return response()->json([
            'data' => CourseResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = Course::create($request->validated());
        $course->refresh();
        $course->load(['teacher.user', 'category', 'academicYear', 'subject']);
        $this->audit->record('course.created', $course, after: $course->toArray());

        return response()->json([
            'data' => new CourseResource($course),
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function show(Course $course): JsonResponse
    {
        $course->load([
            'teacher.user',
            'category',
            'academicYear',
            'subject',
            'schedules',
            'enrollments.student.user',
            'enrollments.grade',
        ]);

        return response()->json([
            'data' => new CourseResource($course),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $before = $course->toArray();
        $course->fill($request->validated())->save();
        $course->load(['teacher.user', 'category', 'academicYear', 'subject']);
        $this->audit->record('course.updated', $course, before: $before, after: $course->toArray());

        return response()->json([
            'data' => new CourseResource($course),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function toggleStatus(Request $request, Course $course): JsonResponse
    {
        $status = CourseStatus::tryFrom((string) $request->string('status', $course->status));
        if ($status === null) {
            return response()->json([
                'data' => null,
                'errors' => ['status' => 'Invalid course status.'],
            ], 422);
        }

        $course->status = $status;
        $course->save();
        $this->audit->record('course.status_changed', $course, after: ['status' => $course->status]);

        return response()->json([
            'data' => ['message' => 'Course status updated.', 'status' => $course->status->value],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function destroy(Course $course): JsonResponse
    {
        if ($course->enrollments()->exists()) {
            return response()->json([
                'data' => ['message' => 'Course has enrollments and cannot be deleted. Archive it instead.'],
                'meta' => null,
                'errors' => null,
            ], 409);
        }

        $this->audit->record('course.deleted', $course, before: $course->toArray());
        $course->delete();

        return response()->json([
            'data' => ['message' => 'Course deleted.'],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function enroll(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')],
        ]);

        $result = [
            'enrolled' => [],
            'already_enrolled' => [],
            'failed' => [],
        ];

        foreach (array_unique(array_map('intval', $validated['student_ids'])) as $studentId) {
            $student = Student::find($studentId);

            if ($student === null) {
                $result['failed'][] = $studentId;

                continue;
            }

            try {
                $enrollment = $this->enrollments->enroll($student, $course);
                $result[($enrollment->wasRecentlyCreated ? 'enrolled' : 'already_enrolled')][] = $studentId;
            } catch (RuntimeException $e) {
                $result['failed'][] = $studentId;
            }
        }

        $this->audit->record('course.enrollments_added', $course, after: $result);

        return response()->json([
            'data' => ['message' => 'Enrollment complete.', ...$result],
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function students(Request $request, Course $course): JsonResponse
    {
        if ((string) $request->string('waitlisted') === '1') {
            $students = $course->enrollments()->where('status', 'waitlist')->with('student.user')->get()->pluck('student');
        } else {
            $students = $course->enrollments()->where('status', 'enrolled')->with('student.user')->get()->pluck('student');
        }

        return response()->json([
            'data' => StudentResource::collection($students),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function manageEnrollment(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'status' => ['required', Rule::in(['enrolled', 'waitlist', 'dropped', 'completed'])],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
        ]);

        $enrollment = $course->enrollments()->where('student_id', $validated['student_id'])->first();

        if ($enrollment === null) {
            return response()->json([
                'data' => null,
                'errors' => ['student_id' => 'Student is not enrolled in this course.'],
            ], 404);
        }

        $enrollment->status = $validated['status'];
        if ($validated['status'] === 'completed') {
            $enrollment->completed_at = now();
        }
        if (! empty($validated['grade_id'])) {
            $enrollment->grade_id = $validated['grade_id'];
        }
        $enrollment->save();

        $this->audit->record('course.enrollment_updated', $enrollment, after: $enrollment->toArray());

        return response()->json([
            'data' => new CourseEnrollmentResource($enrollment->load(['student.user', 'grade'])),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function dropStudent(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
        ]);

        $deleted = $course->enrollments()->where('student_id', $validated['student_id'])->delete();

        if ($deleted === 0) {
            return response()->json([
                'data' => null,
                'errors' => ['student_id' => 'Student is not enrolled in this course.'],
            ], 404);
        }

        $this->audit->record('course.enrollment_removed', $course, after: $validated);

        return response()->json([
            'data' => ['message' => 'Student removed from course.'],
            'meta' => null,
            'errors' => null,
        ]);
    }
}
