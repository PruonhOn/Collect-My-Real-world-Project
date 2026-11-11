<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;

class ReportService
{
    public function studentReport(Student $student, ?int $termId = null): array
    {
        $query = AttendanceRecord::where('student_id', $student->id)
            ->whereHas('attendanceSession', function ($q) use ($termId) {
                $q->whereHas('classSection', function ($q2) use ($termId) {
                    if ($termId) {
                        $q2->where('term_id', $termId);
                    }
                });
            })
            ->join('attendance_sessions', 'attendance_records.attendance_session_id', '=', 'attendance_sessions.id')
            ->join('class_sections', 'attendance_sessions.class_section_id', '=', 'class_sections.id')
            ->join('courses', 'class_sections.course_id', '=', 'courses.id')
            ->selectRaw('
                courses.name as course_name,
                count(*) as total_sessions,
                sum(case when attendance_records.status = "present" then 1 else 0 end) as present_count,
                sum(case when attendance_records.status = "absent" then 1 else 0 end) as absent_count,
                sum(case when attendance_records.status = "late" then 1 else 0 end) as late_count,
                sum(case when attendance_records.status = "excused" then 1 else 0 end) as excused_count
            ')
            ->groupBy('courses.id', 'courses.name')
            ->get();

        return $query->map(function ($row) {
            $total = $row->total_sessions;
            $attended = $row->present_count + $row->late_count + $row->excused_count;
            $percentage = $total > 0 ? round(($attended / $total) * 100, 2) : 0;

            return [
                'course_name' => $row->course_name,
                'total_sessions' => (int) $row->total_sessions,
                'present' => (int) $row->present_count,
                'absent' => (int) $row->absent_count,
                'late' => (int) $row->late_count,
                'excused' => (int) $row->excused_count,
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    public function classReport(ClassSection $classSection): array
    {
        $students = AttendanceRecord::whereHas('attendanceSession', function ($q) use ($classSection) {
            $q->where('class_section_id', $classSection->id);
        })
            ->join('students', 'attendance_records.student_id', '=', 'students.id')
            ->selectRaw('
                students.id as student_id,
                students.student_code,
                users.name as student_name,
                count(*) as total_sessions,
                sum(case when attendance_records.status = "present" then 1 else 0 end) as present_count,
                sum(case when attendance_records.status = "absent" then 1 else 0 end) as absent_count,
                sum(case when attendance_records.status = "late" then 1 else 0 end) as late_count,
                sum(case when attendance_records.status = "excused" then 1 else 0 end) as excused_count
            ')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->groupBy('students.id', 'students.student_code', 'users.name')
            ->get();

        return $students->map(function ($row) {
            $total = $row->total_sessions;
            $attended = $row->present_count + $row->late_count + $row->excused_count;
            $percentage = $total > 0 ? round(($attended / $total) * 100, 2) : 0;

            return [
                'student_name' => $row->student_name,
                'student_code' => $row->student_code,
                'total_sessions' => (int) $row->total_sessions,
                'present' => (int) $row->present_count,
                'absent' => (int) $row->absent_count,
                'late' => (int) $row->late_count,
                'excused' => (int) $row->excused_count,
                'percentage' => $percentage,
            ];
        })->toArray();
    }

    public function departmentReport(Department $department, ?int $termId = null): array
    {
        $courses = Course::where('department_id', $department->id)
            ->with(['classSections' => function ($q) use ($termId) {
                if ($termId) {
                    $q->where('term_id', $termId);
                }
            }])
            ->get();

        $report = [];

        foreach ($courses as $course) {
            foreach ($course->classSections as $classSection) {
                $totalSessions = AttendanceSession::where('class_section_id', $classSection->id)
                    ->count();

                $stats = AttendanceRecord::whereHas('attendanceSession', function ($q) use ($classSection) {
                    $q->where('class_section_id', $classSection->id);
                })
                    ->selectRaw('
                        status,
                        count(*) as count
                    ')
                    ->groupBy('status')
                    ->pluck('count', 'status');

                $present = $stats->get('present', 0);
                $absent = $stats->get('absent', 0);
                $late = $stats->get('late', 0);
                $excused = $stats->get('excused', 0);
                $total = $present + $absent + $late + $excused;
                $percentage = $total > 0 ? round((($present + $late + $excused) / $total) * 100, 2) : 0;

                $report[] = [
                    'course_name' => $course->name,
                    'class_section_id' => $classSection->id,
                    'total_sessions' => $totalSessions,
                    'total_records' => $total,
                    'present' => (int) $present,
                    'absent' => (int) $absent,
                    'late' => (int) $late,
                    'excused' => (int) $excused,
                    'attendance_rate' => $percentage,
                ];
            }
        }

        return $report;
    }

    public function termReport(AcademicTerm $term): array
    {
        $classSections = ClassSection::where('term_id', $term->id)->get();

        $totalSessions = AttendanceSession::whereIn('class_section_id', $classSections->pluck('id'))
            ->count();

        $stats = AttendanceRecord::whereHas('attendanceSession', function ($q) use ($classSections) {
            $q->whereIn('class_section_id', $classSections->pluck('id'));
        })
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $present = $stats->get('present', 0);
        $absent = $stats->get('absent', 0);
        $late = $stats->get('late', 0);
        $excused = $stats->get('excused', 0);
        $total = $present + $absent + $late + $excused;
        $attendanceRate = $total > 0 ? round((($present + $late + $excused) / $total) * 100, 2) : 0;

        return [
            'term_name' => $term->name,
            'total_class_sections' => $classSections->count(),
            'total_sessions' => $totalSessions,
            'total_records' => $total,
            'present' => (int) $present,
            'absent' => (int) $absent,
            'late' => (int) $late,
            'excused' => (int) $excused,
            'attendance_rate' => $attendanceRate,
        ];
    }
}
