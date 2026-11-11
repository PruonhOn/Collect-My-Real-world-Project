<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    private function studentData(string $email = 'student@example.com', string $code = 'STU-001'): array
    {
        return [
            'name' => 'Test Student',
            'email' => $email,
            'student_code' => $code,
            'khmer_name' => 'សិស្សសាកល្បង',
            'gender' => 'female',
            'birth_date' => '2010-05-01',
            'contact_phone' => '+85512345670',
            'guardian_name' => 'Parent Guard',
            'guardian_phone' => '+85512345671',
            'address' => 'Phnom Penh',
        ];
    }

    public function test_index_returns_students_for_admins(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        Student::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/students')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'student_code', 'name', 'email']]]);
    }

    public function test_index_supports_searching_by_user_name(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        $alice = User::factory()->create(['name' => 'Alice Smith']);
        $bob = User::factory()->create(['name' => 'Bob Brown']);
        Student::factory()->for($alice, 'user')->create(['student_code' => 'STU-ALICE']);
        Student::factory()->for($bob, 'user')->create(['student_code' => 'STU-BOB']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/students?search=Alice')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student_code', 'STU-ALICE');
    }

    public function test_index_filters_by_student_status(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        Student::factory()->create(['student_code' => 'STU-ACT']);
        Student::factory()->create(['student_code' => 'STU-GRAD', 'status' => 'graduated']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/students?filter[status]=graduated')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.student_code', 'STU-GRAD');
    }

    public function test_index_returns_403_without_the_manage_permission(): void
    {
        $this->seedRolesAndPermissions();
        $teacher = $this->teacherUser();

        $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/v1/admin/students')
            ->assertForbidden();
    }

    public function test_store_creates_student_user_and_assigns_student_role(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/students', $this->studentData())
            ->assertCreated()
            ->assertJsonPath('data.student_code', 'STU-001')
            ->assertJsonPath('data.email', 'student@example.com');

        $student = Student::where('student_code', 'STU-001')->firstOrFail();
        $this->assertTrue($student->user->hasRole('student'));
        $this->assertSame('active', $student->status?->value);
    }

    public function test_store_returns_422_on_duplicate_student_code(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        Student::factory()->create(['student_code' => 'STU-001']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/students', $this->studentData('other@example.com'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_code']);
    }

    public function test_store_returns_403_without_the_manage_permission(): void
    {
        $this->seedRolesAndPermissions();
        $parent = $this->parentUser();

        $this->actingAs($parent, 'sanctum')
            ->postJson('/api/v1/admin/students', $this->studentData())
            ->assertForbidden();
    }

    public function test_toggle_deactivates_and_reactivates_the_student(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $student = Student::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/students/{$student->id}/toggle", ['active' => false])
            ->assertOk()
            ->assertJsonPath('data.message', 'Student deactivated.');

        $this->assertFalse($student->user->refresh()->is_active);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/students/{$student->id}/toggle", ['active' => true])
            ->assertOk()
            ->assertJsonPath('data.message', 'Student activated.');

        $this->assertTrue($student->user->refresh()->is_active);
    }

    public function test_destroy_soft_deletes_the_student_and_deactivates_the_user(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $student = Student::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/students/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Student deleted.');

        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertFalse($student->user->refresh()->is_active);
    }
}
