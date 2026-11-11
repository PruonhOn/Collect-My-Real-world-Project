<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher();
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher()) {
            $teacher = $user->teacherProfile;

            return $teacher && $student->enrollments()
                ->whereHas('course.teachers', fn ($q) => $q->where('teacher_id', $teacher->id))
                ->exists();
        }

        if ($user->isStudent()) {
            return $user->studentProfile?->id === $student->id;
        }

        if ($user->isParent()) {
            return $user->parentProfile
                ? $user->parentProfile->children()->where('students.id', $student->id)->exists()
                : false;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Student $student): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStudent() && $user->studentProfile?->id === $student->id;
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->isAdmin();
    }
}