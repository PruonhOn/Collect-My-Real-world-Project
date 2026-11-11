<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Course::with(['category', 'teachers'])
            ->withCount('enrollments')
            ->where('status', 'published');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('age_group')) {
            $query->where('age_group', $request->age_group);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $courses = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($courses);
    }

    public function show(Course $course): JsonResponse
    {
        abort_unless($course->status === 'published', 404);

        $course->load([
            'category',
            'teachers',
            'lessons' => fn ($q) => $q->where('is_published', true)->orderBy('order'),
            'quizzes' => fn ($q) => $q->where('is_published', true),
            'assignments' => fn ($q) => $q->where('is_published', true),
        ]);

        $course->loadCount('enrollments');

        return response()->json($course);
    }

    public function categories(): JsonResponse
    {
        $categories = Category::withCount('courses')->orderBy('name')->get();

        return response()->json($categories);
    }
}
