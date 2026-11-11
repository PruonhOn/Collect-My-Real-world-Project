<?php

namespace Tests\Feature\Student;

use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\AcademicTerm;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'teacher', 'guard_name' => 'web']);
        Role::create(['name' => 'student', 'guard_name' => 'web']);
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

    protected function createActiveSession(ClassSection $section): AttendanceSession
    {
        return AttendanceSession::create([
            'class_section_id' => $section->id,
            'session_date' => now()->toDateString(),
            'start_time' => now()->subHour()->format('H:i'),
            'end_time' => now()->addHour()->format('H:i'),
            'qr_token' => Str::random(64),
            'grace_period_minutes' => 15,
            'status' => SessionStatus::Active,
        ]);
    }

    // --- Student Can View Their Attendance ---

    public function test_student_can_view_attendance_index(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create([
            'student_id' => $student->id,
            'class_section_id' => $section->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.attendance.index'));

        $response->assertStatus(200);
    }

    public function test_student_attendance_index_shows_enrolled_sections(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create([
            'student_id' => $student->id,
            'class_section_id' => $section->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.attendance.index'));

        $response->assertStatus(200);
        $response->assertSee($course->name);
    }

    // --- Student Can View Attendance Report ---

    public function test_student_can_view_attendance_report(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        Enrollment::create([
            'student_id' => $student->id,
            'class_section_id' => $section->id,
            'status' => 'active',
        ]);

        $service = app(AttendanceService::class);
        $service->markAttendance($session, $student, 'present', null, $teacherUser->id);

        $response = $this->actingAs($studentUser)->get(route('student.report'));

        $response->assertStatus(200);
    }

    // --- Student Cannot View Other Students' Attendance ---

    public function test_student_cannot_view_other_students_attendance_record(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$student1User, $student1] = $this->createStudentUser($department, $program, 1);
        [$student2User, $student2] = $this->createStudentUser($department, $program, 2);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $record = $service->markAttendance($session, $student1, 'present', null, $teacherUser->id);

        $response = $this->actingAs($student2User)->get(route('student.attendance.show', $record->id));

        $response->assertStatus(403);
    }

    public function test_student_view_returns_404_without_student_profile(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('student');

        $response = $this->actingAs($user)->get(route('student.attendance.index'));

        $response->assertStatus(404);
    }

    // --- Unauthenticated Cannot Access Student Routes ---

    public function test_unauthenticated_user_cannot_view_student_attendance(): void
    {
        $response = $this->get(route('student.attendance.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_teacher_cannot_access_student_attendance_route(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser] = $this->createTeacherUser($department);

        $response = $this->actingAs($teacherUser)->get(route('student.attendance.index'));

        $response->assertStatus(403);
    }

    // --- Attendance Data Integrity ---

    public function test_student_attendance_record_shows_correct_status(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $record = $service->markAttendance($session, $student, 'late', 'Bus was late', $teacherUser->id);

        $response = $this->actingAs($studentUser)->get(route('student.attendance.show', $record->id));

        $response->assertStatus(200);
        $response->assertSee('late');
    }

    public function test_student_with_no_sessions_shows_zero_stats(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create([
            'student_id' => $student->id,
            'class_section_id' => $section->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.attendance.index'));

        $response->assertStatus(200);
    }

    public function test_student_attendance_across_multiple_sections(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);

        $section1 = ClassSection::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
            'name' => 'Section A',
            'room' => 'Room 101',
        ]);

        $course2 = Course::create([
            'program_id' => $program->id,
            'code' => 'CS102',
            'name' => 'Data Structures',
            'credit_hours' => 3,
        ]);

        $section2 = ClassSection::create([
            'course_id' => $course2->id,
            'teacher_id' => $teacher->id,
            'term_id' => $term->id,
            'name' => 'Section B',
            'room' => 'Room 102',
        ]);

        Enrollment::create(['student_id' => $student->id, 'class_section_id' => $section1->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $student->id, 'class_section_id' => $section2->id, 'status' => 'active']);

        $session1 = AttendanceSession::create([
            'class_section_id' => $section1->id,
            'session_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'qr_token' => Str::random(64),
            'grace_period_minutes' => 15,
            'status' => SessionStatus::Active,
        ]);

        $session2 = AttendanceSession::create([
            'class_section_id' => $section2->id,
            'session_date' => now()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'qr_token' => Str::random(64),
            'grace_period_minutes' => 15,
            'status' => SessionStatus::Active,
        ]);

        $service = app(AttendanceService::class);
        $service->markAttendance($session1, $student, 'present', null, $teacherUser->id);
        $service->markAttendance($session2, $student, 'absent', null, $teacherUser->id);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'status' => 'absent',
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.attendance.index'));
        $response->assertStatus(200);
    }

    // --- Student Dashboard ---

    public function test_student_can_access_dashboard(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);

        $response = $this->actingAs($studentUser)->get(route('student.dashboard'));

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_student_dashboard(): void
    {
        $response = $this->get(route('student.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_teacher_cannot_access_student_dashboard(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser] = $this->createTeacherUser($department);

        $response = $this->actingAs($teacherUser)->get(route('student.dashboard'));

        $response->assertStatus(403);
    }
}
