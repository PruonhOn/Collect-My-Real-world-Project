<?php

use App\Models\SystemSetting;

if (! function_exists('school_setting')) {
    function school_setting(string $key, $default = null)
    {
        return SystemSetting::get($key, $default);
    }
}

if (! function_exists('age_groups')) {
    function age_groups(): array
    {
        return [
            '5-7' => '5–7 ឆ្នាំ',
            '8-10' => '8–10 ឆ្នាំ',
            '11-13' => '11–13 ឆ្នាំ',
            '14-16' => '14–16 ឆ្នាំ',
            '17-18' => '17–18 ឆ្នាំ',
        ];
    }
}

if (! function_exists('calculate_age')) {
    function calculate_age($dateOfBirth): ?int
    {
        if (! $dateOfBirth) {
            return null;
        }

        return \Carbon\Carbon::parse($dateOfBirth)->age;
    }
}

if (! function_exists('student_status_label')) {
    function student_status_label(string $status): string
    {
        return match ($status) {
            'active' => __('messages.active'),
            'inactive' => __('messages.inactive'),
            'graduated' => 'បញ្ចប់ការសិក្សា',
            'suspended' => 'ផ្អាកសិក្សា',
            default => $status,
        };
    }
}

if (! function_exists('course_status_label')) {
    function course_status_label(string $status): string
    {
        return match ($status) {
            'draft' => __('messages.draft'),
            'pending_review' => __('messages.pending_review'),
            'published' => __('messages.published'),
            'archived' => __('messages.archived'),
            default => $status,
        };
    }
}

if (! function_exists('payment_status_label')) {
    function payment_status_label(string $status): string
    {
        return match ($status) {
            'pending' => __('messages.pending'),
            'paid' => __('messages.paid'),
            'failed' => __('messages.failed'),
            'refunded' => __('messages.refunded'),
            default => $status,
        };
    }
}

if (! function_exists('enrollment_status_label')) {
    function enrollment_status_label(string $status): string
    {
        return match ($status) {
            'active' => __('messages.active'),
            'completed' => __('messages.completed'),
            'dropped' => 'បោះបង់',
            'pending' => __('messages.pending'),
            default => $status,
        };
    }
}

if (! function_exists('notify_user')) {
    /**
     * Send a database notification to a user (or a notifiable model).
     */
    function notify_user($notifiable, string $title, string $message, ?string $url = null): void
    {
        if ($notifiable) {
            \Illuminate\Support\Facades\Notification::send($notifiable, new \App\Notifications\SchoolNotification($title, $message, $url));
        }
    }
}

if (! function_exists('activity_notify_quiz_result')) {
    function activity_notify_quiz_result(\App\Models\QuizAttempt $attempt): void
    {
        $quiz = $attempt->quiz;
        $message = __('messages.quiz_result_message', [
            'quiz' => $quiz->title,
            'score' => $attempt->percentage . '%',
        ]);

        if ($attempt->student?->user) {
            notify_user($attempt->student->user, __('messages.quiz_result'), $message, route('student.quizzes.result', [$quiz, $attempt]));
        }

        foreach (($attempt->student->parents ?? []) as $parent) {
            if ($parent->user) {
                notify_user($parent->user, __('messages.quiz_result'), $message, route('parent.children.results', $attempt->student));
            }
        }
    }
}

if (! function_exists('activity_notify_assignment_grade')) {
    function activity_notify_assignment_grade(\App\Models\AssignmentSubmission $submission): void
    {
        $message = __('messages.assignment_grade_message', [
            'assignment' => $submission->assignment->title,
            'grade' => $submission->grade,
        ]);

        if ($submission->student?->user) {
            notify_user($submission->student->user, __('messages.assignment_result'), $message, route('student.grades'));
        }

        foreach (($submission->student->parents ?? []) as $parent) {
            if ($parent->user) {
                notify_user($parent->user, __('messages.assignment_result'), $message, route('parent.children.results', $submission->student));
            }
        }
    }
}

if (! function_exists('activity_notify_certificate')) {
    function activity_notify_certificate(\App\Models\Certificate $certificate): void
    {
        $message = __('messages.certificate_message', ['course' => $certificate->course->title]);

        if ($certificate->student?->user) {
            notify_user($certificate->student->user, __('messages.certificate_available'), $message, route('student.certificates.show', $certificate));
        }

        foreach (($certificate->student->parents ?? []) as $parent) {
            if ($parent->user) {
                notify_user($parent->user, __('messages.certificate_available'), $message, route('parent.children.certificates', $certificate->student));
            }
        }
    }
}
