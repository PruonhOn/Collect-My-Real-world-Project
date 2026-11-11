<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\V1\Contact\StoreInquiryRequest;
use App\Http\Resources\CourseResource;
use App\Models\Category;
use App\Models\ContactInquiry;
use App\Models\Course;
use App\Services\AuditService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends BaseApiController
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditService $audit,
    ) {}

    public function settings(): JsonResponse
    {
        return response()->json([
            'data' => $this->settings->publicSettings(),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function courses(Request $request): JsonResponse
    {
        $query = Course::query()
            ->where('is_published', true)
            ->where('status', 'active')
            ->with(['category', 'academicYear', 'teacher.user'])
            ->withCount(['students', 'enrollments'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->boolean('featured'), fn ($q) => $q->where('featured', true))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')));

        $paginator = $this->paginate($query, $request, ['name', 'code'], [], 'created_at', 'desc');

        return response()->json([
            'data' => CourseResource::collection($paginator),
            'meta' => ['pagination' => $this->paginationMeta($paginator)],
            'errors' => null,
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->withCount(['courses' => fn ($q) => $q->where('is_published', true)->where('status', 'active')])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'courses_count' => $category->courses_count,
            ])->values(),
            'meta' => null,
            'errors' => null,
        ]);
    }

    public function contact(StoreInquiryRequest $request): JsonResponse
    {
        $inquiry = ContactInquiry::create($request->validated());
        $this->audit->record('contact.inquiry', $inquiry, after: $inquiry->toArray());

        return response()->json([
            'data' => ['message' => 'Inquiry submitted successfully.'],
            'meta' => null,
            'errors' => null,
        ], 201);
    }

    public function inquiries(Request $request): JsonResponse
    {
        if (! $request->user()->can('contact_inquiries.view')) {
            abort(403);
        }

        $inquiries = ContactInquiry::query()->orderByDesc('created_at')->paginate(25);

        return response()->json([
            'data' => $inquiries->getCollection()->map(fn (ContactInquiry $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'email' => $i->email,
                'phone' => $i->phone,
                'subject' => $i->subject,
                'message' => $i->message,
                'created_at' => $i->created_at?->toISOString(),
            ])->values(),
            'meta' => ['pagination' => $this->paginationMeta($inquiries)],
            'errors' => null,
        ]);
    }
}
