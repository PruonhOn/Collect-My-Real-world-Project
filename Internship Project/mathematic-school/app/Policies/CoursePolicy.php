<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            return $this->teacherOwns($user, $course);
        }

        if ($user->isStudent()) {
            return $course->status === 'published'
                || $course->enrollments()->where('student_id', $user->studentProfile?->id)->exists();
        }

        if ($user->isParent()) {
            return $course->enrollments()
                ->whereHas('student.parents', fn ($q) => $q->where('parents.id', $user->parentProfile?->id))
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher() && $this->teacherOwns($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }

    public function manage(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $this->teacherOwns($user, $course));
    }

    protected function teacherOwns(User $user, Course $course): bool
    {
        return $course->teachers()
            ->where('teachers.user_id', $user->id)
            ->exists();
    }
}