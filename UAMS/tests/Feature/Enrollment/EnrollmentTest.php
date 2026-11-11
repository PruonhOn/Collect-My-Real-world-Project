<?php

namespace Tests\Feature\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\AcademicTerm;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);

        Gate::before(function ($user, $ability) {
            if (str_starts_with($ability, 'permission:')) {
                $permissionName = substr($ability, 11);
                return $user->hasPermissionTo($permissionName) ? true : null;
            }
        });
    }

    protected function createAcademicStructure(): array
    {
        $faculty = Faculty::create(['name' => 'Faculty of Engineering']);
        $department = Department::create(['faculty_id' => $faculty->id, 'name' => 'Computer Science']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'BSc CS', 'code' => 'CS']);
        $term = AcademicTerm::create([
            'name' => 'Fall 2026',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(4)->toDateString(),
            'is_active' => true,
        ]);
        $course = Course::create([
            'program_id' => $program->id,
            'code' => 'CS101',
            'name' => 'Intro to CS',
            'credit_hours' => 3,
        ]);

        return [$faculty, $department, $program, $term, $course];
    }

    protected function createAdminUser(): User
    {
        $managePermission = Permission::firstOrCreate(['name' => 'manage-system', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('admin');
        $user->givePermissionTo('manage-system');

        return $user;
    }

    protected function createTeacherUser(Department $department): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Teacher,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('teacher');

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'employee_code' => 'T' . Str::random(6),
        ]);

        return [$user, $teacher];
    }

    protected function createStudentUser(Department $department, Program $program, int $counter = 1): array
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'student_code' => 'S' . str_pad($counter, 4, '0', STR_PAD_LEFT),
            'department_id' => $department->id,
            'program_id' => $program->id,
            'year' => 1,
        ]);

        return [$user, $student];
    }

    protected function createClassSection(Course $course, Teacher $teacher, AcademicTerm $term): ClassSection
    {
        return ClassSection::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
            'name' => 'Section A',
            'room' => 'Room 101',
        ]);
    }

    // --- Admin Can Enroll Student ---

    public function test_admin_can_enroll_student_via_service(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        $service = app(EnrollmentService::class);
        $enrollment = $service->enrollStudent($student, $section);

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_section_id' => $section->id,
            'status' => EnrollmentStatus::Active,
        ]);
        $this->assertEquals($student->id, $enrollment->student_id);
        $this->assertEquals($section->id, $enrollment->class_section_id);
    }

    public function test_admin_can_enroll_student_via_http(): void
    {
        $adminUser = $this->createAdminUser();
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        $this->actingAs($adminUser);

        $response = $this->postJson(route('admin.enrollments.store'), [
            'student_id' => $student->id,
            'class_section_id' => $section->id,
        ]);

        $response->assertStatus(302);
    }

    // --- Cannot Enroll Student Twice ---

    public function test_cannot_enroll_student_twice_in_same_section(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        $service = app(EnrollmentService::class);
        $service->enrollStudent($student, $section);

        $this->expectException(\InvalidArgumentException::class);
        $service->enrollStudent($student, $section);
    }

    public function test_duplicate_enrollment_via_http_returns_validation_error(): void
    {
        $adminUser = $this->createAdminUser();
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create([
            'student_id' => $student->id,
            'class_section_id' => $section->id,
            'status' => 'active',
        ]);

        $this->actingAs($adminUser);

        $response = $this->postJson(route('admin.enrollments.store'), [
            'student_id' => $student->id,
            'class_section_id' => $section->id,
        ]);

        $response->assertStatus(422);
    }

    // --- Admin Can Drop Student ---

    public function test_admin_can_drop_student_via_service(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        $service = app(EnrollmentService::class);
        $enrollment = $service->enrollStudent($student, $section);
        $dropped = $service->dropStudent($enrollment);

        $this->assertEquals(EnrollmentStatus::Dropped, $dropped->status);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'class_section_id' => $section->id,
            'status' => EnrollmentStatus::Dropped,
        ]);
    }

    public function test_admin_can_drop_student_via_http(): void
    {
        $adminUser = $this->createAdminUser();
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_section_id' => $section->id,
            'status' => 'active',
        ]);

        $this->actingAs($adminUser);

        $response = $this->deleteJson(route('admin.enrollments.destroy', $enrollment->id));

        $response->assertStatus(302);
    }

    // --- Enrolled Students Appear in Section Roster ---

    public function test_enrolled_students_appear_in_section_roster(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$s1User, $s1] = $this->createStudentUser($department, $program, 1);
        [$s2User, $s2] = $this->createStudentUser($department, $program, 2);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create(['student_id' => $s1->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $s2->id, 'class_section_id' => $section->id, 'status' => 'active']);

        $service = app(EnrollmentService::class);
        $roster = $service->getEnrolledStudents($section);

        $this->assertCount(2, $roster);
        $this->assertTrue($roster->contains('student_id', $s1->id));
        $this->assertTrue($roster->contains('student_id', $s2->id));
    }

    public function test_dropped_students_not_in_roster(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$s1User, $s1] = $this->createStudentUser($department, $program, 1);
        [$s2User, $s2] = $this->createStudentUser($department, $program, 2);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create(['student_id' => $s1->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $s2->id, 'class_section_id' => $section->id, 'status' => 'dropped']);

        $service = app(EnrollmentService::class);
        $roster = $service->getEnrolledStudents($section);

        $this->assertCount(1, $roster);
        $this->assertTrue($roster->contains('student_id', $s1->id));
    }

    public function test_enrolled_student_appears_in_class_section_model(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$s1User, $s1] = $this->createStudentUser($department, $program, 1);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create(['student_id' => $s1->id, 'class_section_id' => $section->id, 'status' => 'active']);

        $enrolledStudents = $section->enrolledStudents();

        $this->assertCount(1, $enrolledStudents);
        $this->assertEquals($s1->id, $enrolledStudents->first()->id);
    }

    // --- Access Control ---

    public function test_unauthenticated_user_cannot_enroll(): void
    {
        $response = $this->postJson(route('admin.enrollments.store'), [
            'student_id' => 1,
            'class_section_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_teacher_cannot_enroll_students(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        $this->actingAs($teacherUser);

        $response = $this->postJson(route('admin.enrollments.store'), [
            'student_id' => $student->id,
            'class_section_id' => $section->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_student_cannot_enroll_themselves(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        $this->actingAs($studentUser);

        $response = $this->postJson(route('admin.enrollments.store'), [
            'student_id' => $student->id,
            'class_section_id' => $section->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_enrollment_index_requires_admin_role(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser] = $this->createTeacherUser($department);

        $this->actingAs($teacherUser);

        $response = $this->getJson(route('admin.enrollments.index'));

        $response->assertStatus(403);
    }

    // --- Validation ---

    public function test_enrollment_requires_valid_student_id(): void
    {
        $adminUser = $this->createAdminUser();

        $this->actingAs($adminUser);

        $response = $this->postJson(route('admin.enrollments.store'), [
            'student_id' => 99999,
            'class_section_id' => 1,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('student_id');
    }

    public function test_enrollment_requires_valid_class_section_id(): void
    {
        $adminUser = $this->createAdminUser();
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$studentUser, $student] = $this->createStudentUser($department, $program);

        $this->actingAs($adminUser);

        $response = $this->postJson(route('admin.enrollments.store'), [
            'student_id' => $student->id,
            'class_section_id' => 99999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('class_section_id');
    }
}
