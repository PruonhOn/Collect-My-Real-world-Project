<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Lesson;

class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        $course = $lesson->course;

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            return $course->teachers()->where('teachers.user_id', $user->id)->exists();
        }

        if ($user->isStudent()) {
            return $course->status === 'published'
                && $course->enrollments()
                    ->where('student_id', $user->studentProfile?->id)
                    ->whereIn('status', ['active', 'completed'])
                    ->exists();
        }

        return false;
    }

    public function manage(User $user, Lesson $lesson): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher()
            && $lesson->course->teachers()->where('teachers.user_id', $user->id)->exists();
    }
}