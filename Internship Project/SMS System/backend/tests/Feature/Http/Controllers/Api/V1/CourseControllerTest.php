<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Student;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_index_returns_courses_for_admins(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        Course::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/courses')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'code', 'is_published']]]);
    }

    public function test_index_filters_by_published_flag(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        Course::factory()->create(['name' => 'Published Course']);
        Course::factory()->unpublished()->create(['name' => 'Draft Course']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/courses?published=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Draft Course')
            ->assertJsonPath('data.0.is_published', false);
    }

    public function test_store_creates_a_published_course_by_default(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $year = AcademicYear::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/courses', [
                'academic_year_id' => $year->id,
                'name' => 'Advanced Mathematics',
                'code' => 'MATH-101',
                'type' => CourseType::FaceToFace->value,
                'fee' => 250,
                'capacity' => 40,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Advanced Mathematics')
            ->assertJsonPath('data.is_published', true);

        $this->assertDatabaseHas('courses', ['code' => 'MATH-101', 'is_published' => 1]);
    }

    public function test_store_honors_explicit_published_flag(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $year = AcademicYear::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/courses', [
                'academic_year_id' => $year->id,
                'name' => 'Draft Course',
                'code' => 'DRAFT-01',
                'type' => CourseType::Online->value,
                'fee' => 0,
                'capacity' => 0,
                'is_published' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_published', false);

        $this->assertDatabaseHas('courses', ['code' => 'DRAFT-01', 'is_published' => 0]);
    }

    public function test_store_returns_403_without_the_manage_permission(): void
    {
        $this->seedRolesAndPermissions();
        $student = $this->studentUser();
        $year = AcademicYear::factory()->create();

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/v1/admin/courses', [
                'academic_year_id' => $year->id,
                'name' => 'Blocked Course',
                'type' => CourseType::Online->value,
                'fee' => 0,
                'capacity' => 0,
            ])
            ->assertForbidden();
    }

    public function test_store_returns_422_on_invalid_type(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $year = AcademicYear::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/courses', [
                'academic_year_id' => $year->id,
                'name' => 'Bad Type Course',
                'type' => 'hybrid',
                'fee' => 0,
                'capacity' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_show_returns_a_single_course(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $course = Course::factory()->create(['name' => 'Visible Course']);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Visible Course');
    }

    public function test_toggle_status_archives_the_course(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $course = Course::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/courses/{$course->id}/status", ['status' => CourseStatus::Archived->value])
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');

        $this->assertTrue($course->refresh()->status === CourseStatus::Archived);
    }

    public function test_toggle_status_returns_422_on_invalid_status(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $course = Course::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/courses/{$course->id}/status", ['status' => 'deleted'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.status', 'Invalid course status.');
    }

    public function test_destroy_returns_409_when_the_course_has_enrollments(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $course = Course::factory()->create();
        $student = Student::factory()->create();
        app(EnrollmentService::class)->enroll($student, $course);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/courses/{$course->id}")
            ->assertStatus(409);
    }

    public function test_destroy_soft_deletes_the_course_without_enrollments(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $course = Course::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Course deleted.');

        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_enroll_assigns_students_to_the_course(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $course = Course::factory()->create(['capacity' => 10]);
        $student = Student::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/courses/{$course->id}/enroll", ['student_ids' => [$student->id]])
            ->assertOk()
            ->assertJsonPath('data.enrolled.0', $student->id);

        $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id]);
    }
}
