<?php

namespace Tests\Feature\Attendance;

use App\Enums\AttendanceMethod;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\AcademicTerm;
use App\Models\AttendanceRecord;
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

class AttendanceTest extends TestCase
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

    // --- Access Control Tests ---

    public function test_unauthenticated_user_cannot_access_teacher_attendance(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        $section = $this->createClassSection($course, $teacher, $term);

        $response = $this->postJson(route('teacher.attendance.store-session', $section->id), [
            'session_date' => now()->toDateString(),
            'start_time' => '09:00',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_teacher_attendance_routes(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);

        $this->actingAs($studentUser);

        $response = $this->postJson(route('teacher.attendance.store-session', $section->id), [
            'session_date' => now()->toDateString(),
            'start_time' => '09:00',
        ]);

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_manage_other_teachers_session(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser1, $teacher1] = $this->createTeacherUser($department);
        [$teacherUser2, $teacher2] = $this->createTeacherUser($department);
        $section = $this->createClassSection($course, $teacher1, $term);
        $session = $this->createActiveSession($section);

        $this->actingAs($teacherUser2);

        $response = $this->postJson(route('teacher.attendance.mark', $session->id), [
            'student_id' => 1,
            'status' => 'present',
        ]);

        $response->assertStatus(403);
    }

    // --- Teacher Session Creation ---

    public function test_teacher_can_create_attendance_session_via_service(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        $section = $this->createClassSection($course, $teacher, $term);

        $service = app(AttendanceService::class);
        $session = $service->createSession($section, [
            'session_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'grace_period_minutes' => 15,
        ]);

        $this->assertDatabaseHas('attendance_sessions', [
            'class_section_id' => $section->id,
            'session_date' => now()->toDateString(),
            'status' => SessionStatus::Active,
        ]);
        $this->assertNotNull($session->qr_token);
        $this->assertEquals($section->id, $session->class_section_id);
    }

    public function test_created_session_has_unique_qr_token(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        $section = $this->createClassSection($course, $teacher, $term);

        $service = app(AttendanceService::class);
        $session1 = $service->createSession($section, [
            'session_date' => now()->toDateString(),
            'start_time' => '09:00',
        ]);
        $session2 = $service->createSession($section, [
            'session_date' => now()->toDateString(),
            'start_time' => '10:00',
        ]);

        $this->assertNotEquals($session1->qr_token, $session2->qr_token);
    }

    // --- Mark Attendance ---

    public function test_teacher_can_mark_student_attendance_via_service(): void
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

        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $record = $service->markAttendance($session, $student, 'present', null, $teacherUser->id);

        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Present,
            'marked_by' => $teacherUser->id,
            'method' => AttendanceMethod::Manual,
        ]);
        $this->assertEquals($student->id, $record->student_id);
    }

    public function test_teacher_can_mark_student_late(): void
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

        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $service->markAttendance($session, $student, 'late', 'Traffic jam', $teacherUser->id);

        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => AttendanceStatus::Late,
            'reason' => 'Traffic jam',
        ]);
    }

    public function test_marking_updates_existing_record(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $record1 = $service->markAttendance($session, $student, 'absent', null, $teacherUser->id);
        $record2 = $service->markAttendance($session, $student, 'present', null, $teacherUser->id);

        $this->assertEquals($record1->id, $record2->id, 'Should update existing record, not create a new one');
        $this->assertDatabaseHas('attendance_records', [
            'id' => $record1->id,
            'status' => AttendanceStatus::Present,
        ]);
        $this->assertEquals(1, AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('student_id', $student->id)
            ->count());
    }

    // --- Bulk Mark Attendance ---

    public function test_teacher_can_bulk_mark_attendance_via_service(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser1, $student1] = $this->createStudentUser($department, $program, 1);
        [$studentUser2, $student2] = $this->createStudentUser($department, $program, 2);
        [$studentUser3, $student3] = $this->createStudentUser($department, $program, 3);
        $section = $this->createClassSection($course, $teacher, $term);

        foreach ([$student1, $student2, $student3] as $student) {
            Enrollment::create([
                'student_id' => $student->id,
                'class_section_id' => $section->id,
                'status' => 'active',
            ]);
        }

        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $records = $service->bulkMarkAttendance($session, [
            ['student_id' => $student1->id, 'status' => 'present'],
            ['student_id' => $student2->id, 'status' => 'absent'],
            ['student_id' => $student3->id, 'status' => 'late', 'reason' => 'Illness'],
        ], $teacherUser->id);

        $this->assertCount(3, $records);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'absent',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student3->id,
            'status' => 'late',
            'reason' => 'Illness',
        ]);
    }

    // --- Close Session ---

    public function test_teacher_can_close_session_via_service(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser1, $student1] = $this->createStudentUser($department, $program, 1);
        [$studentUser2, $student2] = $this->createStudentUser($department, $program, 2);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create(['student_id' => $student1->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $student2->id, 'class_section_id' => $section->id, 'status' => 'active']);

        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $service->markAttendance($session, $student1, 'present', null, $teacherUser->id);

        $closedSession = $service->closeSession($session);

        $this->assertEquals(SessionStatus::Closed, $closedSession->status);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'absent',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'present',
        ]);
    }

    public function test_close_session_auto_marks_absent_students(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$s1User, $s1] = $this->createStudentUser($department, $program, 1);
        [$s2User, $s2] = $this->createStudentUser($department, $program, 2);
        [$s3User, $s3] = $this->createStudentUser($department, $program, 3);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create(['student_id' => $s1->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $s2->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $s3->id, 'class_section_id' => $section->id, 'status' => 'active']);

        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $service->markAttendance($session, $s1, 'present', null, $teacherUser->id);
        $service->markAttendance($session, $s2, 'late', null, $teacherUser->id);

        $service->closeSession($session);

        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $s1->id,
            'status' => 'present',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $s2->id,
            'status' => 'late',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $s3->id,
            'status' => 'absent',
        ]);
    }

    // --- QR Check-In ---

    public function test_student_can_check_in_via_qr(): void
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

        $token = Str::random(64);
        AttendanceSession::create([
            'class_section_id' => $section->id,
            'session_date' => now()->toDateString(),
            'start_time' => now()->subHour()->format('H:i'),
            'end_time' => now()->addHour()->format('H:i'),
            'qr_token' => $token,
            'grace_period_minutes' => 15,
            'status' => SessionStatus::Active,
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.qr-checkin', $token));

        $response->assertRedirect(route('student.attendance.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'status' => 'present',
            'method' => 'qr',
        ]);
    }

    public function test_qr_check_in_fails_with_invalid_token(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$studentUser, $student] = $this->createStudentUser($department, $program);

        $response = $this->actingAs($studentUser)->get(route('student.qr-checkin', 'invalid-token-12345'));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_qr_check_in_fails_after_session_closes(): void
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

        $token = Str::random(64);
        AttendanceSession::create([
            'class_section_id' => $section->id,
            'session_date' => now()->toDateString(),
            'start_time' => now()->subHours(3)->format('H:i'),
            'end_time' => now()->subHours(2)->format('H:i'),
            'qr_token' => $token,
            'grace_period_minutes' => 15,
            'status' => SessionStatus::Closed,
        ]);

        $response = $this->actingAs($studentUser)->get(route('student.qr-checkin', $token));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_duplicate_qr_check_in_is_idempotent(): void
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

        $token = Str::random(64);
        AttendanceSession::create([
            'class_section_id' => $section->id,
            'session_date' => now()->toDateString(),
            'start_time' => now()->subHour()->format('H:i'),
            'end_time' => now()->addHour()->format('H:i'),
            'qr_token' => $token,
            'grace_period_minutes' => 15,
            'status' => SessionStatus::Active,
        ]);

        $this->actingAs($studentUser)->get(route('student.qr-checkin', $token));
        $this->actingAs($studentUser)->get(route('student.qr-checkin', $token));

        $this->assertEquals(1, AttendanceRecord::where('student_id', $student->id)
            ->where('method', 'qr')
            ->count());
    }

    // --- Audit Log ---

    public function test_attendance_audit_log_is_created_on_status_change(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $service->markAttendance($session, $student, 'absent', null, $teacherUser->id);
        $service->markAttendance($session, $student, 'present', null, $teacherUser->id);

        $this->assertDatabaseHas('attendance_audit_logs', [
            'field_changed' => 'status',
            'old_value' => 'absent',
            'new_value' => 'present',
        ]);
    }

    public function test_audit_log_not_created_on_first_mark(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $service->markAttendance($session, $student, 'present', null, $teacherUser->id);

        $this->assertDatabaseCount('attendance_audit_logs', 0);
    }

    // --- Session Stats ---

    public function test_session_stats_are_calculated_correctly(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$s1User, $s1] = $this->createStudentUser($department, $program, 1);
        [$s2User, $s2] = $this->createStudentUser($department, $program, 2);
        [$s3User, $s3] = $this->createStudentUser($department, $program, 3);
        [$s4User, $s4] = $this->createStudentUser($department, $program, 4);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create(['student_id' => $s1->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $s2->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $s3->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $s4->id, 'class_section_id' => $section->id, 'status' => 'active']);

        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $service->markAttendance($session, $s1, 'present', null, $teacherUser->id);
        $service->markAttendance($session, $s2, 'present', null, $teacherUser->id);
        $service->markAttendance($session, $s3, 'late', null, $teacherUser->id);

        $stats = $service->getSessionStats($session);

        $this->assertEquals(4, $stats['total_enrolled']);
        $this->assertEquals(2, $stats['present_count']);
        $this->assertEquals(0, $stats['absent_count']);
        $this->assertEquals(1, $stats['late_count']);
        $this->assertEquals(0, $stats['excused_count']);
        $this->assertEquals(75.0, $stats['attendance_rate']);
    }

    public function test_session_stats_after_close(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$s1User, $s1] = $this->createStudentUser($department, $program, 1);
        [$s2User, $s2] = $this->createStudentUser($department, $program, 2);
        $section = $this->createClassSection($course, $teacher, $term);

        Enrollment::create(['student_id' => $s1->id, 'class_section_id' => $section->id, 'status' => 'active']);
        Enrollment::create(['student_id' => $s2->id, 'class_section_id' => $section->id, 'status' => 'active']);

        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $service->markAttendance($session, $s1, 'present', null, $teacherUser->id);
        $service->closeSession($session);

        $stats = $service->getSessionStats($session);

        $this->assertEquals(2, $stats['total_enrolled']);
        $this->assertEquals(1, $stats['present_count']);
        $this->assertEquals(1, $stats['absent_count']);
        $this->assertEquals(50.0, $stats['attendance_rate']);
    }

    public function test_session_stats_with_zero_enrolled(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $stats = $service->getSessionStats($session);

        $this->assertEquals(0, $stats['total_enrolled']);
        $this->assertEquals(0, $stats['attendance_rate']);
    }

    // --- QR Token Regeneration ---

    public function test_qr_token_can_be_regenerated(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);
        $originalToken = $session->qr_token;

        $service = app(AttendanceService::class);
        $freshSession = $service->regenerateQrToken($session);

        $this->assertNotEquals($originalToken, $freshSession->qr_token);
        $this->assertEquals(64, strlen($freshSession->qr_token));
    }

    public function test_old_qr_token_fails_after_regeneration(): void
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

        $session = $this->createActiveSession($section);
        $oldToken = $session->qr_token;

        $service = app(AttendanceService::class);
        $service->regenerateQrToken($session);

        $response = $this->actingAs($studentUser)->get(route('student.qr-checkin', $oldToken));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('attendance_records', 0);
    }

    // --- HTTP Route Tests for Store Session ---

    public function test_teacher_can_access_store_session_route(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        $section = $this->createClassSection($course, $teacher, $term);

        $this->actingAs($teacherUser);

        $response = $this->postJson(route('teacher.attendance.store-session', $section->id), [
            'session_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $response->assertStatus(302);
    }

    // --- HTTP Route Tests for Mark ---

    public function test_teacher_can_access_mark_route(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $this->actingAs($teacherUser);

        $response = $this->postJson(route('teacher.attendance.mark', $session->id), [
            'student_id' => $student->id,
            'status' => 'present',
        ]);

        $response->assertStatus(302);
    }

    // --- HTTP Route Tests for Bulk Mark ---

    public function test_teacher_can_access_bulk_mark_route(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $this->actingAs($teacherUser);

        $response = $this->postJson(route('teacher.attendance.bulk-mark', $session->id), [
            'records' => [
                ['student_id' => $student->id, 'status' => 'present'],
            ],
        ]);

        $response->assertStatus(302);
    }

    // --- HTTP Route Tests for Close Session ---

    public function test_teacher_can_access_close_session_route(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $this->actingAs($teacherUser);

        $response = $this->postJson(route('teacher.attendance.close-session', $session->id));

        $response->assertStatus(302);
    }

    // --- Student Attendance View Tests ---

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

    public function test_student_can_view_own_attendance_record(): void
    {
        [$faculty, $department, $program, $term, $course] = $this->createAcademicStructure();
        [$teacherUser, $teacher] = $this->createTeacherUser($department);
        [$studentUser, $student] = $this->createStudentUser($department, $program);
        $section = $this->createClassSection($course, $teacher, $term);
        $session = $this->createActiveSession($section);

        $service = app(AttendanceService::class);
        $record = $service->markAttendance($session, $student, 'present', null, $teacherUser->id);

        $response = $this->actingAs($studentUser)->get(route('student.attendance.show', $record->id));

        $response->assertStatus(200);
    }

    public function test_student_cannot_view_other_students_record(): void
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
}
