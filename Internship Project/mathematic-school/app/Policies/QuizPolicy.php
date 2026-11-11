<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function view(User $user, Quiz $quiz): bool
    {
        $course = $quiz->course;

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

    public function take(User $user, Quiz $quiz): bool
    {
        if (! $user->isStudent() || ! $quiz->is_active) {
            return false;
        }

        return $this->view($user, $quiz);
    }

    public function manage(User $user, Quiz $quiz): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher()
            && $quiz->course->teachers()->where('teachers.user_id', $user->id)->exists();
    }
}