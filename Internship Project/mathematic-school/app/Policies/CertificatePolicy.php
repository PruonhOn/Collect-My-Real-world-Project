<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function view(User $user, Certificate $certificate): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $certificate->student_id === $user->studentProfile?->id;
        }

        if ($user->isParent()) {
            return $certificate->student
                ->parents()
                ->where('parents.id', $user->parentProfile?->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return $user->isAdmin();
    }
}