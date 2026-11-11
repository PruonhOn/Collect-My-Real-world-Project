<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAttendanceSessionRequest;
use App\Http\Requests\MarkAttendanceRequest;
use App\Http\Requests\BulkMarkAttendanceRequest;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Student;
use App\Services\AttendanceService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        protected AttendanceService $attendanceService,
        protected NotificationService $notificationService,
    ) {}

    protected function authorizeTeacherOwnsSection(ClassSection $section): void
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher && $section->teacher_id === $teacher->id, 403);
    }

    protected function authorizeTeacherOwnsSession(AttendanceSession $session): void
    {
        $teacher = Auth::user()->teacher;
        abort_unless($teacher && $session->classSection && $session->classSection->teacher_id === $teacher->id, 403);
    }

    public function index(ClassSection $classSection)
    {
        $this->authorizeTeacherOwnsSection($classSection);

        $classSection->load(['course', 'academicTerm']);

        $sessions = $classSection->attendanceSessions()
            ->withCount([
                'attendanceRecords as present_count' => fn ($q) => $q->where('status', 'present'),
                'attendanceRecords as absent_count' => fn ($q) => $q->where('status', 'absent'),
                'attendanceRecords as late_count' => fn ($q) => $q->where('status', 'late'),
                'attendanceRecords as excused_count' => fn ($q) => $q->where('status', 'excused'),
            ])
            ->latest()
            ->paginate(15);

        return view('teacher.attendance.index', compact('classSection', 'sessions'));
    }

    public function storeSession(StoreAttendanceSessionRequest $request, ClassSection $classSection)
    {
        $this->authorizeTeacherOwnsSection($classSection);

        $session = $this->attendanceService->createSession($classSection, $request->validated());

        return redirect()
            ->route('teacher.attendance.show-session', $session)
            ->with('success', 'Attendance session created successfully.');
    }

    public function showSession(AttendanceSession $session)
    {
        $this->authorizeTeacherOwnsSession($session);

        $session->load([
            'classSection.course',
            'classSection.academicTerm',
            'attendanceRecords.student.user',
        ]);

        $enrolledStudents = $session->classSection->enrollments()
            ->where('status', 'active')
            ->with('student.user')
            ->get();

        $attendanceMap = $session->attendanceRecords->keyBy('student_id');

        $presentStudents = collect();
        $absentStudents = collect();
        $lateStudents = collect();
        $excusedStudents = collect();
        $unmarkedStudents = collect();

        foreach ($enrolledStudents as $enrollment) {
            $student = $enrollment->student;
            $record = $attendanceMap->get($student->id);

            if (!$record) {
                $unmarkedStudents->push($student);
                continue;
            }

            match ($record->status) {
                'present' => $presentStudents->push($student),
                'absent' => $absentStudents->push($student),
                'late' => $lateStudents->push($student),
                'excused' => $excusedStudents->push($student),
                default => $unmarkedStudents->push($student),
            };
        }

        $totalEnrolled = $enrolledStudents->count();
        $attendanceRate = $totalEnrolled > 0
            ? round(($presentStudents->count() + $lateStudents->count()) / $totalEnrolled * 100, 1)
            : 0;

        $qrUrl = $session->qr_token
            ? url("/student/qr-checkin/{$session->qr_token}")
            : null;

        $qrSvg = $session->qr_token && $session->status === 'active'
            ? QrCode::format('svg')->size(250)->generate($qrUrl)
            : null;

        return view('teacher.attendance.session', [
            'session' => $session,
            'classSection' => $session->classSection,
            'enrolledStudents' => $enrolledStudents,
            'presentStudents' => $presentStudents,
            'absentStudents' => $absentStudents,
            'lateStudents' => $lateStudents,
            'excusedStudents' => $excusedStudents,
            'unmarkedStudents' => $unmarkedStudents,
            'totalEnrolled' => $totalEnrolled,
            'attendanceRate' => $attendanceRate,
            'qrUrl' => $qrUrl,
            'qrSvg' => $qrSvg,
        ]);
    }

    public function mark(MarkAttendanceRequest $request, AttendanceSession $session)
    {
        $this->authorizeTeacherOwnsSession($session);

        $teacher = Auth::user()->teacher;

        $student = Student::findOrFail($request->student_id);

        $record = $this->attendanceService->markAttendance(
            $session,
            $student,
            $request->status,
            $request->input('reason'),
            $teacher?->id
        );

        if ($request->status === 'absent') {
            $this->notificationService->notifyStudentAbsent($record);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully.',
                'record' => $record->load('student.user'),
            ]);
        }

        return redirect()
            ->route('teacher.attendance.show-session', $session)
            ->with('success', 'Attendance marked successfully.');
    }

    public function bulkMark(BulkMarkAttendanceRequest $request, AttendanceSession $session)
    {
        $this->authorizeTeacherOwnsSession($session);

        $teacher = Auth::user()->teacher;

        $records = $this->attendanceService->bulkMarkAttendance(
            $session,
            $request->records,
            $teacher?->id ?? auth()->id()
        );

        foreach ($records as $record) {
            if ($record->status === 'absent') {
                $this->notificationService->notifyStudentAbsent($record);
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Bulk attendance marked successfully.',
                'count' => count($records),
            ]);
        }

        return redirect()
            ->route('teacher.attendance.show-session', $session)
            ->with('success', count($records) . ' attendance records updated.');
    }

    public function closeSession(AttendanceSession $session)
    {
        $this->authorizeTeacherOwnsSession($session);

        $this->attendanceService->closeSession($session);

        return redirect()
            ->route('teacher.attendance.show-session', $session)
            ->with('success', 'Session closed successfully. Unmarked students have been marked as absent.');
    }

    public function regenerateQr(AttendanceSession $session)
    {
        $this->authorizeTeacherOwnsSession($session);

        $updated = $this->attendanceService->regenerateQrToken($session);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'qr_token' => $updated->qr_token,
                'qr_url' => url("/student/qr-checkin/{$updated->qr_token}"),
            ]);
        }

        return redirect()
            ->route('teacher.attendance.show-qr', $session)
            ->with('success', 'QR code regenerated successfully.');
    }

    public function showQr(AttendanceSession $session)
    {
        $this->authorizeTeacherOwnsSession($session);

        $session->load(['classSection.course', 'classSection.academicTerm']);

        $qrUrl = url("/student/qr-checkin/{$session->qr_token}");

        $qrImage = QrCode::format('svg')->size(300)->generate($qrUrl);

        return view('teacher.attendance.qr-code', [
            'session' => $session,
            'classSection' => $session->classSection,
            'qrUrl' => $qrUrl,
            'qrImage' => $qrImage,
        ]);
    }
}
