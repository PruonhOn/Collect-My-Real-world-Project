<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\AccessService;
use App\Services\BookUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookController extends Controller
{
    public function __construct(
        private BookUploadService $uploadService,
        private AccessService $accessService,
    ) {}

    /**
     * Admin listing: returns ALL books (published + drafts) with category.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Book::query()->with('category');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        $sort = $request->input('sort', 'latest');
        $query->when($sort === 'latest', fn ($q) => $q->latest())
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc('price'));

        $books = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        return response()->json($books);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Book::query()
            ->with('category')
            ->published();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        if ($request->input('free') === '1') {
            $query->where('is_free', true);
        }

        if ($request->input('free') === '0') {
            $query->where('is_free', false);
        }

        $sort = $request->input('sort', 'latest');
        $query->when($sort === 'latest', fn ($q) => $q->latest())
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc('price'));

        $books = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        return response()->json($books);
    }

    /**
     * Distinct author names for dropdown selection in the admin book form.
     */
    public function authors(): JsonResponse
    {
        $authors = Book::query()
            ->whereNotNull('author')
            ->where('author', '!=', '')
            ->distinct()
            ->orderBy('author')
            ->pluck('author')
            ->values();

        return response()->json(['authors' => $authors]);
    }

    public function show(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $book->load('category');
        $book->loadCount('reviews');
        $book->loadAvg('reviews', 'rating');
        $book->increment('view_count');

        $access = null;

        $user = auth('sanctum')->user();
        if ($user) {
            $access = [
                'can_read' => $this->accessService->canRead($user, $book),
                'owned' => ! $book->is_free && $this->accessService->hasAccess($user, $book->id),
            ];
        }

        return response()->json([
            'book' => $book,
            'related' => Book::where('category_id', $book->category_id)
                ->where('id', '!=', $book->id)
                ->published()
                ->take(4)
                ->get(),
            'ratings' => [
                'avg' => round((float) $book->reviews_avg_rating, 2) ?: 0,
                'count' => (int) $book->reviews_count,
            ],
            'reviews' => $book->reviews()
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get(),
            'access' => $access,
        ]);
    }

    /**
     * Single-book fetch for the admin (drafts included), mirroring adminIndex.
     */
    public function adminShow(Book $book): JsonResponse
    {
        return response()->json([
            'book' => $book->load('category'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'publication_date' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,bmp,svg,avif,heic,tiff', 'max:5120'],
            'file' => ['nullable', 'file', 'mimes:pdf,epub', 'max:51200'],
            'language' => ['nullable', 'string', 'max:10'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'is_vip_only' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data = $this->toBookArray($validated);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->uploadService->storeCover($request->file('cover_image'));
        }

        if ($request->hasFile('file')) {
            $stored = $this->uploadService->storeBookFile(
                $request->file('file'),
                $data['language'] ?? 'km'
            );
            $data = array_merge($data, $stored);

            if (! array_key_exists('pages', $data)) {
                $count = $this->uploadService->countPdfPages($request->file('file'));
                if ($count !== null) {
                    $data['pages'] = $count;
                }
            }
        }

        $book = Book::create($data);

        return response()->json(['book' => $book->load('category')], 201);
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'education_level' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'publication_date' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,bmp,svg,avif,heic,tiff', 'max:5120'],
            'file' => ['nullable', 'file', 'mimes:pdf,epub', 'max:51200'],
            'language' => ['nullable', 'string', 'max:10'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
            'is_vip_only' => ['nullable', 'boolean'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data = $this->toBookArray($validated);

        if ($request->hasFile('cover_image')) {
            $this->uploadService->deleteCover($book->cover_image);
            $data['cover_image'] = $this->uploadService->storeCover($request->file('cover_image'));
        }

        if ($request->hasFile('file')) {
            $this->uploadService->deleteBookFile($book->file_path);
            $stored = $this->uploadService->storeBookFile(
                $request->file('file'),
                $data['language'] ?? $book->language
            );
            $data = array_merge($data, $stored);

            if (! array_key_exists('pages', $data)) {
                $count = $this->uploadService->countPdfPages($request->file('file'));
                if ($count !== null) {
                    $data['pages'] = $count;
                }
            }
        }

        if (isset($data['title']) && $data['title'] !== $book->title) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }

        $book->update($data);

        return response()->json(['book' => $book->fresh()->load('category')]);
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->uploadService->deleteCover($book->cover_image);
        $this->uploadService->deleteBookFile($book->file_path);
        $book->delete();

        return response()->json(['message' => 'Book deleted.']);
    }

    private function toBookArray(array $validated): array
    {
        $map = [
            'category_id', 'title', 'description', 'content', 'author', 'language',
            'pages', 'price', 'is_free', 'is_published', 'is_vip_only',
            'publisher', 'subject', 'education_level', 'grade', 'publication_date',
        ];

        $data = array_intersect_key($validated, array_flip($map));

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }

        return $data;
    }
}
