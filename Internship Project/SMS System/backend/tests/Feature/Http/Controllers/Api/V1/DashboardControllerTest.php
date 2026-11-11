<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/dashboard')
            ->assertUnauthorized();
    }

    public function test_index_returns_admin_stats_and_chart_for_admins(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.total_students', 0)
            ->assertJsonStructure(['data' => ['stats', 'chart' => ['months', 'enrollments', 'payments', 'attendance']]])
            ->assertJsonCount(6, 'data.chart.months');
    }

    public function test_admin_dashboard_honors_the_months_param(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard?months=3')
            ->assertOk()
            ->assertJsonCount(3, 'data.chart.months');
    }

    public function test_index_returns_teacher_stats_and_courses_for_teachers(): void
    {
        $this->seedRolesAndPermissions();
        $teacher = $this->teacherUser();

        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.total_courses', 0)
            ->assertJsonCount(0, 'data.courses');
    }

    public function test_index_returns_403_for_a_teacher_role_without_a_teacher_profile(): void
    {
        $this->seedRolesAndPermissions();
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'No teacher profile linked to this account.');
    }

    public function test_index_returns_parent_children_with_fees_for_parents(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();
        $student = Student::factory()->create();
        $parent->parentProfile->students()->attach($student->id, ['relationship' => 'father', 'is_primary' => true]);

        $this->actingAs($parent, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonCount(1, 'data.children')
            ->assertJsonPath('data.children.0.student.id', $student->id)
            ->assertJsonPath('data.children.0.student.name', $student->user->name)
            ->assertJsonPath('data.children.0.student.student_code', $student->student_code)
            ->assertJsonStructure(['data' => ['children' => [['attendance' => ['percentage'], 'fees']]]]);
    }

    public function test_index_returns_403_for_a_parent_role_without_a_parent_profile(): void
    {
        $this->seedRolesAndPermissions();
        $parent = User::factory()->create();
        $parent->assignRole('parent');

        $this->actingAs($parent, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'No parent profile linked to this account.');
    }

    public function test_index_returns_student_dashboard_with_fees_balance_for_students(): void
    {
        $this->seedRolesAndPermissions();
        $student = $this->studentUser();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonCount(0, 'data.enrolled_courses')
            ->assertJsonStructure(['data' => ['enrolled_courses', 'attendance', 'fees' => ['balance', 'total_billed', 'total_paid']]])
            ->assertJsonPath('data.fees.balance', 0.0)
            ->assertJsonPath('data.fees.total_billed', 0.0);
    }

    public function test_index_returns_staff_dashboard_for_a_plain_user(): void
    {
        $this->seedRolesAndPermissions();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.profile.role', 'staff')
            ->assertJsonStructure(['data' => ['open_inquiries', 'funds_collected_this_month', 'unread_messages']]);
    }
}
