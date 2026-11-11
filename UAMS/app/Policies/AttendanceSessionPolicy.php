<?php

namespace App\Policies;

use App\Models\AttendanceSession;
use App\Models\User;

class AttendanceSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isTeacher();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, AttendanceSession $session): bool
    {
        return $user->isSuperAdmin()
            || $user->isAdmin()
            || ($user->teacher && $session->classSection->teacher_id === $user->teacher->id);
    }
}
