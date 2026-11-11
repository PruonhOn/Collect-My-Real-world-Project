<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class TeacherControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    private function teacherData(string $email = 'teacher@example.com', string $code = 'TEA-001'): array
    {
        return [
            'name' => 'Test Teacher',
            'email' => $email,
            'employee_code' => $code,
            'hire_date' => '2020-09-01',
            'qualification' => 'Master of Education',
            'specialization' => 'Mathematics',
            'bio' => 'Experienced mathematics teacher.',
        ];
    }

    public function test_index_returns_teachers_for_admins(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        Teacher::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/teachers')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'employee_code', 'name', 'email']]]);
    }

    public function test_index_returns_403_without_the_manage_permission(): void
    {
        $this->seedRolesAndPermissions();
        $student = $this->studentUser();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/admin/teachers')
            ->assertForbidden();
    }

    public function test_store_creates_teacher_user_and_assigns_teacher_role(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/teachers', $this->teacherData())
            ->assertCreated()
            ->assertJsonPath('data.employee_code', 'TEA-001')
            ->assertJsonPath('data.email', 'teacher@example.com');

        $teacher = Teacher::where('employee_code', 'TEA-001')->firstOrFail();
        $this->assertTrue($teacher->user->hasRole('teacher'));
    }

    public function test_store_returns_403_without_the_manage_permission(): void
    {
        $this->seedRolesAndPermissions();
        $staff = $this->staffUser();

        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/v1/admin/teachers', $this->teacherData())
            ->assertForbidden();
    }

    public function test_store_returns_422_on_duplicate_employee_code(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        Teacher::factory()->create(['employee_code' => 'TEA-001']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/teachers', $this->teacherData('other@example.com'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee_code']);
    }

    public function test_toggle_deactivates_and_reactivates_the_teacher(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $teacher = Teacher::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/teachers/{$teacher->id}/toggle", ['active' => false])
            ->assertOk()
            ->assertJsonPath('data.message', 'Teacher deactivated.');

        $this->assertFalse($teacher->user->refresh()->is_active);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/teachers/{$teacher->id}/toggle", ['active' => true])
            ->assertOk()
            ->assertJsonPath('data.message', 'Teacher activated.');

        $this->assertTrue($teacher->user->refresh()->is_active);
    }

    public function test_destroy_returns_422_when_the_teacher_still_teaches_courses(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $teacher = Teacher::factory()->create();
        Course::factory()->create(['teacher_id' => $teacher->id]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/teachers/{$teacher->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['teacher']);
    }

    public function test_destroy_soft_deletes_the_teacher_and_deactivates_the_user(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $teacher = Teacher::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/teachers/{$teacher->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Teacher deleted.');

        $this->assertSoftDeleted('teachers', ['id' => $teacher->id]);
        $this->assertFalse($teacher->user->refresh()->is_active);
    }
}
