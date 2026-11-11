<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $student = $enrollment->student;

        if ($user->isStudent()) {
            return $student->id === $user->studentProfile?->id;
        }

        if ($user->isParent()) {
            return $student->parents()->where('parents.id', $user->parentProfile?->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStudent();
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin();
    }
}