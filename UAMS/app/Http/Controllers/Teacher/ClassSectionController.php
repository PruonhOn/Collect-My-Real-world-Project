<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicTerm;
use App\Models\ClassSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassSectionController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function index()
    {
        $user = Auth::user();
        $teacher = $user->teacher;

        abort_unless($teacher, 404);

        $activeTerm = AcademicTerm::where('is_active', true)->first();

        $classSections = $teacher->classSections()
            ->when($activeTerm, fn ($q) => $q->where('term_id', $activeTerm->id))
            ->with(['course', 'academicTerm', 'enrollments'])
            ->withCount('enrollments')
            ->get();

        return view('teacher.sections.index', [
            'teacher' => $teacher,
            'classSections' => $classSections,
            'sections' => $classSections,
            'activeTerm' => $activeTerm,
        ]);
    }

    public function show(ClassSection $classSection)
    {
        $user = Auth::user();
        $teacher = $user->teacher;

        abort_unless($teacher, 404);
        abort_unless($classSection->teacher_id === $teacher->id, 403);

        $classSection->load([
            'course',
            'academicTerm',
            'enrollments.student.user',
            'attendanceSessions',
        ]);

        $enrollments = $classSection->enrollments()
            ->where('status', 'active')
            ->with('student.user')
            ->latest()
            ->get();

        return view('teacher.sections.show', [
            'teacher' => $teacher,
            'classSection' => $classSection,
            'enrollments' => $enrollments,
            'enrolledStudents' => $enrollments,
        ]);
    }
}
