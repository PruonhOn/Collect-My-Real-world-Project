<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $payment->student_id === $user->studentProfile?->id;
        }

        if ($user->isParent()) {
            return $payment->student
                ->parents()
                ->where('parents.id', $user->parentProfile?->id)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isParent() || $user->isStudent();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->isAdmin();
    }
}