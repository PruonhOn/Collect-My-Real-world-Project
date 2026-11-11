<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, Student $student): bool
    {
        return $user->isSuperAdmin()
            || $user->isAdmin()
            || ($user->teacher && $student->enrollments()
                ->whereHas('classSection', fn ($q) => $q->where('teacher_id', $user->teacher->id))
                ->exists())
            || ($user->student && $student->id === $user->student->id);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function update(User $user, Student $student): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }
}
