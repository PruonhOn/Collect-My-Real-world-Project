<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);

        Permission::create(['name' => 'manage-system', 'guard_name' => 'web']);

        Gate::before(function ($user, $ability) {
            if (str_starts_with($ability, 'permission:')) {
                $permissionName = substr($ability, 11);
                return $user->hasPermissionTo($permissionName) ? true : null;
            }
        });
    }

    protected function createAdminUser(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->givePermissionTo('manage-system');

        return $user;
    }

    protected function createTeacherUser(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Teacher,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('teacher');

        return $user;
    }

    protected function createStudentUser(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('student');

        return $user;
    }

    protected function createTeacherWithProfile(Department $department): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Teacher,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('teacher');
        Teacher::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'employee_code' => 'T' . Str::random(6),
        ]);
    }

    protected function createStudentWithProfile(Department $department, Program $program, int $counter = 1): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('student');
        Student::create([
            'user_id' => $user->id,
            'student_code' => 'S' . str_pad($counter, 4, '0', STR_PAD_LEFT),
            'department_id' => $department->id,
            'program_id' => $program->id,
            'year' => 1,
        ]);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_dashboard(): void
    {
        $managePermission = Permission::where('name', 'manage-system')->first();
        $superAdminRole = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo($managePermission);

        $user = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('super_admin');
        $user->givePermissionTo('manage-system');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertStatus(200);
    }

    public function test_teacher_cannot_access_admin_dashboard(): void
    {
        $teacher = $this->createTeacherUser();

        $response = $this->actingAs($teacher)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = $this->createStudentUser();

        $response = $this->actingAs($student)->get(route('admin.dashboard'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_displays_correct_student_count(): void
    {
        $admin = $this->createAdminUser();

        $faculty = Faculty::create(['name' => 'Faculty of Engineering']);
        $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'BSc CS', 'code' => 'CS']);

        for ($i = 0; $i < 5; $i++) {
            $this->createStudentWithProfile($department, $program, $i + 1);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $this->assertEquals(5, Student::count());
    }

    public function test_dashboard_displays_correct_teacher_count(): void
    {
        $admin = $this->createAdminUser();

        $faculty = Faculty::create(['name' => 'Faculty of Engineering']);
        $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science']);

        for ($i = 0; $i < 3; $i++) {
            $this->createTeacherWithProfile($department);
        }

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $this->assertEquals(3, Teacher::count());
    }

    public function test_inactive_admin_redirected_to_login(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
            'is_active' => false,
        ]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_access_faculties_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.faculties.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_departments_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.departments.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_programs_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.programs.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_courses_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.courses.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_students_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.students.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_teachers_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.teachers.index'));

        $response->assertStatus(200);
    }

    public function test_admin_can_access_enrollments_index(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)->get(route('admin.enrollments.index'));

        $response->assertStatus(200);
    }

    public function test_teacher_cannot_access_faculties_index(): void
    {
        $teacher = $this->createTeacherUser();

        $response = $this->actingAs($teacher)->get(route('admin.faculties.index'));

        $response->assertStatus(403);
    }

    public function test_student_cannot_access_students_index(): void
    {
        $student = $this->createStudentUser();

        $response = $this->actingAs($student)->get(route('admin.students.index'));

        $response->assertStatus(403);
    }
}
