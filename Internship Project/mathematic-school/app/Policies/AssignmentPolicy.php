<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function view(User $user, Assignment $assignment): bool
    {
        $course = $assignment->course;

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

    public function submit(User $user, Assignment $assignment): bool
    {
        if (! $user->isStudent()) {
            return false;
        }

        return $assignment->deadline->isFuture()
            && $this->view($user, $assignment);
    }

    public function manage(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher()
            && $assignment->course->teachers()->where('teachers.user_id', $user->id)->exists();
    }
}