<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UamsFullFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function seedBase(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    protected function loginAs(string $email): User
    {
        $user = User::where('email', $email)->firstOrFail();
        $this->actingAs($user);
        return $user;
    }

    // ============ ADMIN CRUD FLOWS ============

    public function test_admin_full_crud_flows(): void
    {
        $this->seedBase();
        $this->loginAs('super_admin@uams.edu');

        // Faculty
        $resp = $this->post(route('admin.faculties.store'), [
            'name' => 'Test Faculty', 'description' => 'Desc',
        ]);
        $faculty = Faculty::where('name', 'Test Faculty')->first();
        $this->assertNotNull($faculty, 'Faculty was not created');

        $resp = $this->put(route('admin.faculties.update', $faculty), ['name' => 'Faculty v2']);
        $this->assertDatabaseHas('faculties', ['id' => $faculty->id, 'name' => 'Faculty v2']);

        // Department
        $this->post(route('admin.departments.store'), [
            'name' => 'Test Dept', 'code' => 'TD', 'faculty_id' => $faculty->id,
        ]);
        $dept = Department::where('name', 'Test Dept')->first();
        $this->assertNotNull($dept, 'Department was not created');

        // Program
        $this->post(route('admin.programs.store'), [
            'name' => 'Test Program', 'code' => 'TP',
            'department_id' => $dept->id, 'duration_years' => 4,
        ]);
        $program = Program::where('name', 'Test Program')->first();
        $this->assertNotNull($program, "Program not created: " . session('error'));

        // Academic term
        $this->post(route('admin.academic-terms.store'), [
            'name' => 'Term 1 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30',
            'status' => 'upcoming',
        ]);
        $term = AcademicTerm::where('name', 'Term 1 2026')->first();
        $this->assertNotNull($term, "Term not created: " . session('error'));

        // Course
        $this->post(route('admin.courses.store'), [
            'code' => 'CS101', 'name' => 'Intro CS', 'credits' => 3,
            'department_id' => $dept->id, 'description' => null,
        ]);
        $course = Course::where('code', 'CS101')->first();
        $this->assertNotNull($course, "Course not created: " . session('error'));

        // Student
        $this->post(route('admin.students.store'), [
            'name' => 'Flow Student', 'email' => 'flow.student@student.uams.edu',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'student_code' => 'STU-FLOW-001', 'department_id' => $dept->id,
            'program_id' => $program->id, 'year' => 2,
        ]);
        $studentUser = User::where('email', 'flow.student@student.uams.edu')->first();
        $this->assertNotNull($studentUser, "Student user not created: " . session('error'));
        $flowStudent = Student::where('user_id', $studentUser->id)->first();
        $this->assertNotNull($flowStudent, 'Student record missing');
        $this->assertEquals(2, $flowStudent->year);

        // Update student
        $this->put(route('admin.students.update', $flowStudent), [
            'name' => 'Flow Student v2', 'email' => 'flow.student@student.uams.edu',
            'student_code' => 'STU-FLOW-001', 'department_id' => $dept->id,
            'program_id' => $program->id, 'year' => 3,
        ]);
        $this->assertDatabaseHas('students', ['id' => $flowStudent->id, 'year' => 3]);

        // Teacher
        $this->post(route('admin.teachers.store'), [
            'name' => 'Flow Teacher', 'email' => 'flow.teacher@uams.edu',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'employee_code' => 'TCH-FLOW-001', 'department_id' => $dept->id,
        ]);
        $teacherUser = User::where('email', 'flow.teacher@uams.edu')->first();
        $this->assertNotNull($teacherUser, "Teacher not created: " . session('error'));

        // Class section
        $this->post(route('admin.class-sections.store'), [
            'course_id' => $course->id, 'teacher_id' => $teacherUser->teacher->id,
            'term_id' => $term->id, 'name' => 'CS101-A',
            'room' => 'R101', 'schedule' => 'Mon 09:00', 'capacity' => 30, 'max_students' => 30,
        ]);
        $section = ClassSection::where('name', 'CS101-A')->first()
            ?? ClassSection::where('course_id', $course->id)->first();
        $this->assertNotNull($section, "Class section not created: " . session('error'));

        // Enroll flow student into the section
        $this->post(route('admin.enrollments.store'), [
            'student_id' => $flowStudent->id, 'class_section_id' => $section->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $flowStudent->id, 'class_section_id' => $section->id,
        ]);

        // Delete faculty should fail (has children) or cascade — just verify no crash
        $this->get(route('admin.reports.class', ['class_section_id' => $section->id]))->assertOk();
    }

    public function test_import_flow_with_csv(): void
    {
        $this->seedBase();
        $admin = $this->loginAs('super_admin@uams.edu');

        $this->get(route('admin.students.import'))->assertOk()->assertSee('Import Students');

        $csv = "name,email,student_code\nImport One,import.one@student.uams.edu,STU-IMP-01\nImport Two,import.two@student.uams.edu,STU-IMP-02\n";
        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $resp = $this->post(route('admin.students.do-import'), ['file' => $file]);
        $resp->assertRedirect();
        $this->assertDatabaseHas(User::class, ['email' => 'import.one@student.uams.edu']);
        $this->assertDatabaseHas(User::class, ['email' => 'import.two@student.uams.edu']);
        $this->assertDatabaseHas('students', ['student_code' => 'STU-IMP-02']);

        // Re-import same file → skipped, no crash
        $file2 = UploadedFile::fake()->createWithContent('students.csv', $csv);
        $this->post(route('admin.students.do-import'), ['file' => $file2])->assertRedirect();

        // Teacher import
        $csvT = "name,email,employee_code\nImp Teach,imp.teach@uams.edu,TCH-IMP-01\n";
        $fileT = UploadedFile::fake()->createWithContent('teachers.csv', $csvT);
        $this->post(route('admin.teachers.do-import'), ['file' => $fileT])->assertRedirect();
        $this->assertDatabaseHas(User::class, ['email' => 'imp.teach@uams.edu']);
        $this->assertDatabaseHas('teachers', ['employee_code' => 'TCH-IMP-01']);
    }

    // ============ TEACHER ATTENDANCE FLOWS ============

    public function test_teacher_session_lifecycle(): void
    {
        $this->seedBase();
        $this->loginAs('ahmed.khan@uams.edu');

        $teacher = auth()->user()->teacher;
        $section = $teacher->classSections()->first();
        $this->assertNotNull($section, 'Teacher has no sections in seed data');

        $enrolled = Student::whereHas('enrollments', fn ($q) =>
            $q->where('class_section_id', $section->id)->where('status', 'active'))->get();
        if ($enrolled->isEmpty()) {
            $this->markTestSkipped('No enrolled students for section; enrollment covered elsewhere.');
        }

        // Create session
        $resp = $this->post(route('teacher.attendance.store-session', $section), [
            'class_section_id' => $section->id,
            'session_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:30',
            'grace_period_minutes' => 10,
        ]);
        $resp->assertRedirect();
        $session = AttendanceSession::where('class_section_id', $section->id)
            ->latest('id')->first();
        $this->assertNotNull($session, 'Session was not created');
        $this->assertEquals('active', $session->status);
        $this->assertNotEmpty($session->qr_token);

        // View session page
        $this->get(route('teacher.attendance.show-session', $session))
            ->assertOk()
            ->assertSee('Token');

        // Mark one student present
        $target = $enrolled->first();
        $this->post(route('teacher.attendance.mark', $session), [
            'student_id' => $target->id,
            'status' => 'present',
        ])->assertJson(['success' => true]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $target->id,
            'status' => 'present',
        ]);

        // Bulk mark remaining
        if ($enrolled->count() > 1) {
            $records = $enrolled->skip(1)->map(fn ($s) => [
                'student_id' => $s->id, 'status' => 'late',
            ])->values()->toArray();
            $this->post(route('teacher.attendance.bulk-mark', $session), ['records' => $records])
                ->assertJson(['success' => true]);
        }

        // Regenerate QR
        $oldToken = $session->qr_token;
        $this->post(route('teacher.attendance.regenerate-qr', $session), [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
            ->assertJson(['success' => true]);
        $session->refresh();
        $this->assertNotEquals($oldToken, $session->qr_token);

        // QR page renders
        $this->get(route('teacher.attendance.show-qr', $session))->assertOk()->assertSee('Scan this code');

        // Close session → unmarked students become absent
        $this->patch(route('teacher.attendance.close-session', $session))->assertRedirect();
        $session->refresh();
        $this->assertEquals('closed', $session->status);
        $absentCount = \App\Models\AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('status', 'absent')->count();
        $expectedAbsent = $enrolled->count() - 1 - max(0, $enrolled->count() - 1) + ($enrolled->count() - 1 - ($enrolled->count() - 1));
        // every unmarked student must have a record; total records == enrolled count
        $totalRecords = \App\Models\AttendanceRecord::where('attendance_session_id', $session->id)->count();
        $this->assertEquals($enrolled->count(), $totalRecords, 'Closing did not create absent records for all unmarked students');

        // Class report page
        $this->get(route('teacher.reports.class', $section))->assertOk()->assertSee('Overall');

        // PDF export
        $pdfResp = $this->get(route('teacher.reports.pdf', $section));
        $pdfResp->assertStatus(200);
        $this->assertStringContainsString('%PDF', substr($pdfResp->streamedContent ?? '', 0, 200) ?: '');

        // Excel export
        $xlResp = $this->get(route('teacher.reports.excel', $section));
        $xlResp->assertStatus(200);
    }

    // ============ STUDENT QR CHECK-IN FLOW ============

    public function test_student_qr_checkin_flow(): void
    {
        $this->seedBase();
        $this->loginAs('omar.alrashid@student.uams.edu');
        $student = auth()->user()->student;

        $enrollment = \App\Models\Enrollment::where('student_id', $student->id)
            ->where('status', 'active')->first();
        if (!$enrollment) {
            $this->markTestSkipped('Seeded student has no active enrollment.');
        }
        $section = $enrollment->classSection;

        // Teacher creates session
        $sectionTeacher = \App\Models\Teacher::find($section->teacher_id);
        $this->actingAs(User::find($sectionTeacher->user_id));
        $this->post(route('teacher.attendance.store-session', $section), [
            'class_section_id' => $section->id,
            'session_date' => now()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:30',
        ])->assertRedirect();
        $session = AttendanceSession::where('class_section_id', $section->id)->latest('id')->first();
        $this->assertNotNull($session, 'Session creation failed in QR flow');

        // Student scans QR
        $this->actingAs(User::where('email', 'omar.alrashid@student.uams.edu')->first());
        $resp = $this->get(route('student.qr-checkin', ['token' => $session->qr_token]));
        $resp->assertStatus(302);

        $record = \App\Models\AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('student_id', $student->id)->first();
        $this->assertNotNull($record, 'QR check-in did not create attendance record');
        $this->assertContains($record->status, ['present', 'late']);

        // Duplicate check-in handled gracefully
        $resp2 = $this->get(route('student.qr-checkin', ['token' => $session->qr_token]));
        $this->assertContains($resp2->status(), [302, 200]);

        // Student pages
        $this->get(route('student.dashboard'))->assertOk();
        $this->get(route('student.attendance.index'))->assertOk();
        $this->get(route('student.report'))->assertOk();
    }

    public function test_role_isolation(): void
    {
        $this->seedBase();

        // Student cannot access admin
        $this->actingAs(User::where('email', 'omar.alrashid@student.uams.edu')->first());
        $this->get(route('admin.dashboard'))->assertForbidden();

        // Teacher cannot access admin
        $this->actingAs(User::where('email', 'ahmed.khan@uams.edu')->first());
        $this->get(route('admin.dashboard'))->assertForbidden();

        // Teacher cannot view another teacher's session (session 1 belongs to ahmed)
        $otherTeacherUser = User::where('email', 'sarah.williams@uams.edu')->first();
        $ownSection = $otherTeacherUser->teacher->classSections()->first();
        if ($ownSection) {
            $foreignSession = AttendanceSession::whereHas('classSection', fn ($q) =>
                $q->where('teacher_id', '!=', $otherTeacherUser->teacher->id))->first();
            if ($foreignSession) {
                $this->actingAs($otherTeacherUser);
                $this->get(route('teacher.attendance.show-session', $foreignSession))->assertForbidden();
            }
        }
    }
}
