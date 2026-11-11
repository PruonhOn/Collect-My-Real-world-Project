<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isTeacher();
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        return $user->isSuperAdmin()
            || $user->isAdmin()
            || ($user->teacher && $record->session->classSection->teacher_id === $user->teacher->id)
            || ($user->student && $record->student_id === $user->student->id);
    }

    public function update(User $user, AttendanceRecord $record): bool
    {
        return $user->isSuperAdmin()
            || $user->isAdmin()
            || ($user->teacher && $record->session->classSection->teacher_id === $user->teacher->id);
    }
}
