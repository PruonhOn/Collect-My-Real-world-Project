<?php

namespace Tests\Feature\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use LazilyRefreshDatabase;
    use SeedsRolesAndPermissions;

    public function test_index_returns_all_users_except_the_authenticated_user(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $other = User::factory()->create(['name' => 'Other Admin']);
        $other->assignRole('admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Other Admin')
            ->assertJsonMissingPath('data.1');
    }

    public function test_index_returns_403_without_the_manage_permission(): void
    {
        $this->seedRolesAndPermissions();
        $student = $this->studentUser();

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();
    }

    public function test_index_returns_401_when_unauthenticated(): void
    {
        $this->getJson('/api/v1/admin/users')
            ->assertUnauthorized();
    }

    public function test_store_creates_user_and_assigns_roles(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name' => 'New Teacher User',
                'email' => 'teacher.user@example.com',
                'roles' => ['teacher'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.email', 'teacher.user@example.com')
            ->assertJsonPath('data.roles.0', 'teacher');

        $created = User::where('email', 'teacher.user@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole('teacher'));
        $this->assertNotEmpty($created->password);
    }

    public function test_store_returns_422_when_roles_do_not_exist(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name' => 'Invalid Role',
                'email' => 'invalid.role@example.com',
                'roles' => ['nonexistent_role'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles.0']);
    }

    public function test_store_returns_422_on_duplicate_email(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name' => 'Duplicate',
                'email' => 'taken@example.com',
                'roles' => ['admin'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_returns_403_without_the_manage_permission(): void
    {
        $this->seedRolesAndPermissions();
        $teacher = $this->teacherUser();

        $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/v1/admin/users', [
                'name' => 'Nope',
                'email' => 'nope@example.com',
                'roles' => ['admin'],
            ])
            ->assertForbidden();
    }

    public function test_show_returns_the_user_with_roles(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $target = $this->staffUser(['name' => 'Visible Staff']);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Visible Staff')
            ->assertJsonPath('data.roles.0', 'staff');
    }

    public function test_update_changes_fields_and_syncs_roles(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $target = $this->staffUser(['name' => 'Before Update']);

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/admin/users/{$target->id}", [
                'name' => 'After Update',
                'roles' => ['admin', 'staff'],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'After Update');

        $target->refresh();
        $this->assertSame('After Update', $target->name);
        $this->assertTrue($target->hasRole('admin'));
        $this->assertTrue($target->hasRole('staff'));
    }

    public function test_destroy_deletes_the_user(): void
    {
        $this->seedRolesAndPermissions();
        $admin = $this->adminUser();
        $target = $this->staffUser();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'User deleted.');

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }
}
