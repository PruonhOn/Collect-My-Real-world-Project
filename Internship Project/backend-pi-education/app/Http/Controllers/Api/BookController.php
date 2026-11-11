<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\BookUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookController extends Controller
{
    public function __construct(private BookUploadService $uploadService) {}

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

        $sort = $request->input('sort', 'latest');
        $query->when($sort === 'latest', fn ($q) => $q->latest())
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc('price'));

        $books = $query->paginate((int) $request->input('per_page', 12))->withQueryString();

        return response()->json($books);
    }

    public function show(Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $book->load('category');
        $book->loadCount('reviews');
        $book->loadAvg('reviews', 'rating');

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
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,bmp,svg,avif,heic,tiff', 'max:5120'],
            'file' => ['nullable', 'file', 'mimes:pdf,epub', 'max:51200'],
            'language' => ['nullable', 'string', 'max:10'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
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
            'author' => ['nullable', 'string', 'max:255'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,bmp,svg,avif,heic,tiff', 'max:5120'],
            'file' => ['nullable', 'file', 'mimes:pdf,epub', 'max:51200'],
            'language' => ['nullable', 'string', 'max:10'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'is_free' => ['nullable', 'boolean'],
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
            'category_id', 'title', 'description', 'author', 'language',
            'pages', 'price', 'is_free', 'is_published',
        ];

        $data = array_intersect_key($validated, array_flip($map));

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }

        return $data;
    }
}
