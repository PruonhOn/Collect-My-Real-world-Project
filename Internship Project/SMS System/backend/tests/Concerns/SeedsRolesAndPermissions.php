<?php

namespace Tests\Concerns;

use App\Enums\UserRole;
use App\Models\Guardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the roles and permissions used by the API and creates actor users
 * with the exact profiles each role expects. Kept in a trait so every test
 * class shares one source of truth for RBAC fixtures.
 */
trait SeedsRolesAndPermissions
{
    private bool $rbacSeeded = false;

    /**
     * Create every role and permission from RolePermissionSeeder. The seeder
     * itself cannot run on SQLite because it issues MySQL-only FK statements.
     */
    protected function seedRolesAndPermissions(): void
    {
        if ($this->rbacSeeded) {
            return;
        }

        foreach (RolePermissionSeeder::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $all = Permission::pluck('name');

        foreach (RolePermissionSeeder::ROLES as $role) {
            $role = Role::findOrCreate($role);

            if (RolePermissionSeeder::ROLE_PERMISSIONS[$role->name] === '*') {
                $role->syncPermissions($all);
            } else {
                $role->syncPermissions(RolePermissionSeeder::ROLE_PERMISSIONS[$role->name]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->rbacSeeded = true;
    }

    protected function adminUser(array $overrides = []): User
    {
        $this->seedRolesAndPermissions();

        return User::factory()->create($overrides)->assignRole(UserRole::Admin->value);
    }

    protected function teacherUser(array $overrides = []): User
    {
        $this->seedRolesAndPermissions();

        $user = User::factory()->create($overrides);
        Teacher::factory()->for($user, 'user')->create();
        $user->assignRole(UserRole::Teacher->value);

        return $user;
    }

    protected function staffUser(array $overrides = []): User
    {
        $this->seedRolesAndPermissions();

        $user = User::factory()->create($overrides);
        Staff::factory()->for($user, 'user')->create();
        $user->assignRole(UserRole::Staff->value);

        return $user;
    }

    protected function studentUser(array $overrides = []): User
    {
        $this->seedRolesAndPermissions();

        $user = User::factory()->create($overrides);
        Student::factory()->for($user, 'user')->create();
        $user->assignRole(UserRole::Student->value);

        return $user;
    }

    protected function parentUser(array $overrides = []): User
    {
        $this->seedRolesAndPermissions();

        $user = User::factory()->create($overrides);
        Guardian::factory()->for($user, 'user')->create();
        $user->assignRole(UserRole::Parent->value);

        return $user;
    }
}
