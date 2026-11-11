<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Grade;
use App\Models\ParentGuardian;
use App\Models\Payment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $parent = auth()->user()->parentProfile;

        abort_unless($parent, 404);

        $children = $parent->children()->with('enrollments.course')->get();

        $children = $children->map(function ($child) {
            $enrollments = $child->enrollments;
            $totalLessons = 0;
            $completedLessons = 0;

            foreach ($enrollments as $enrollment) {
                $totalLessons += $enrollment->course->publishedLessons()->count();
                $completedLessons += $enrollment->lessonProgress()->where('is_completed', true)->count();
            }

            $child->overall_progress = $totalLessons > 0
                ? round(($completedLessons / $totalLessons) * 100, 1)
                : 0;

            return $child;
        });

        $unread = auth()->user()->unreadNotifications()->count();

        return view('parent.dashboard', compact('parent', 'children', 'unread'));
    }
}