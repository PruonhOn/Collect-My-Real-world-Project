<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    protected function createUser(array $attributes = [], ?string $role = null): User
    {
        $user = User::create(array_merge([
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'role' => $role ?? 'student',
            'is_active' => true,
        ], $attributes));

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
    }

    protected function createStudent(?User $user = null): Student
    {
        $user = $user ?? $this->createUser(['role' => 'student'], 'student');

        return Student::create([
            'user_id' => $user->id,
            'student_id' => 'STU-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'full_name' => $user->name,
            'date_of_birth' => '2015-01-01',
            'age' => 11,
            'gender' => 'male',
            'status' => 'active',
        ]);
    }

    protected function createParent(?User $user = null): ParentGuardian
    {
        $user = $user ?? $this->createUser(['role' => 'parent'], 'parent');

        return ParentGuardian::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'phone' => $this->faker->phoneNumber(),
            'email' => $user->email,
            'is_active' => true,
        ]);
    }

    protected function createTeacher(?User $user = null): Teacher
    {
        $user = $user ?? $this->createUser(['role' => 'teacher'], 'teacher');

        return Teacher::create([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'specialization' => 'Mathematics',
        ]);
    }

    protected function createPublishedCourse(): Course
    {
        $category = Category::create([
            'name' => $this->faker->unique()->word(),
            'slug' => $this->faker->unique()->slug(),
        ]);

        return Course::create([
            'category_id' => $category->id,
            'title' => $this->faker->sentence(4),
            'course_code' => 'CRS-' . strtoupper($this->faker->unique()->bothify('???###')),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->paragraph(),
            'age_group' => '8-10',
            'level' => 'beginner',
            'duration' => 12,
            'price' => 10,
            'status' => 'published',
        ]);
    }

    protected function createEnrollment(Student $student, Course $course): Enrollment
    {
        return Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
            'completion_percentage' => 0,
        ]);
    }

    protected function bearerAuth(User $user): array
    {
        $token = $user->createToken('test-token')->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token];
    }
}
