<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\BookUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function __construct(private BookUploadService $uploadService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Course::query()->where('is_published', true);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $courses = $query->withCount('lessons')->get();

        return response()->json(['courses' => $courses]);
    }

    public function show(Course $course): JsonResponse
    {
        abort_unless($course->is_published, 404);

        return response()->json([
            'course' => $course->load('lessons'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data = [
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(4)),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? 0,
            'is_published' => $validated['is_published'] ?? true,
        ];

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->uploadService->storeCover($request->file('cover_image'), 'courses');
        }

        $course = Course::create($data);

        return response()->json(['course' => $course], 201);
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data = [];

        foreach (['title', 'description', 'price', 'is_published'] as $field) {
            if (array_key_exists($field, $validated)) {
                $data[$field] = $validated[$field];
            }
        }

        if (isset($validated['title'])) {
            $data['slug'] = Str::slug($validated['title']).'-'.Str::lower(Str::random(4));
        }

        if ($request->hasFile('cover_image')) {
            $this->uploadService->deleteCover($course->cover_image);
            $data['cover_image'] = $this->uploadService->storeCover($request->file('cover_image'), 'courses');
        }

        $course->update($data);

        return response()->json(['course' => $course->fresh()]);
    }

    public function destroy(Course $course): JsonResponse
    {
        $this->uploadService->deleteCover($course->cover_image);
        $course->delete();

        return response()->json(['message' => 'Course deleted.']);
    }
}
