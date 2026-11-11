<?php

namespace App\Http\Controllers\Api;

use App\Services\BookAccessService;
use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Bookmark;
use App\Models\ReadingProgress;
use App\Models\Review;
use Illuminate\Http\Request;

class BookController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Book::with(['category', 'author'])
            ->where('is_published', true);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('category') && $request->category) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('author') && $request->author) {
            $query->whereHas('author', function ($q) use ($request) {
                $q->where('slug', $request->author);
            });
        }

        if ($request->has('type')) {
            if ($request->type === 'free') {
                $query->where('is_free', true);
            } elseif ($request->type === 'paid') {
                $query->where('is_free', false);
            }
        }

        $sort = $request->sort ?? 'latest';
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'most_viewed':
                $query->orderByDesc('views_count');
                break;
            case 'popular':
                $query->withCount('reviews')->orderByDesc('reviews_count');
                break;
            default:
                $query->latest();
        }

        $books = $query->paginate($request->per_page ?? 12);

        $mapped = $books->getCollection()->map(function ($book) {
            return $this->bookPayload($book);
        });

        return $this->success([
            'books' => $mapped,
            'pagination' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug)
    {
        $book = Book::with(['category', 'author', 'translations', 'reviews' => function ($q) {
            $q->with('user:id,name,avatar')->latest();
        }])->where('slug', $slug)->where('is_published', true)->first();

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        $book->increment('views_count');

        $data = $this->bookPayload($book);
        $data['reviews'] = $book->reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user_name' => $review->user->name,
                'user_avatar' => $review->user->avatar,
                'created_at' => $review->created_at->toDateTimeString(),
            ];
        });

        $user = $request->user();

        if ($user) {
            $data['bookmarked'] = Bookmark::where('user_id', $user->id)
                ->where('book_id', $book->id)->exists();

            $progress = ReadingProgress::where('user_id', $user->id)
                ->where('book_id', $book->id)->first();

            if ($progress) {
                $data['progress'] = $progress;
            }

            $accessService = app(BookAccessService::class);
            $data['can_read'] = $accessService->canAccess($user, $book);
            $data['purchased'] = $user->hasPurchased($book);
        } else {
            $data['bookmarked'] = false;
            $data['can_read'] = $book->isFree();
            $data['purchased'] = false;
        }

        return $this->success($data);
    }

    public function related(Request $request, string $slug)
    {
        $book = Book::where('slug', $slug)->first();

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        $books = Book::with(['category', 'author'])
            ->where('category_id', $book->category_id)
            ->where('id', '!=', $book->id)
            ->where('is_published', true)
            ->limit(6)
            ->get()
            ->map(fn ($b) => $this->bookPayload($b));

        return $this->success($books);
    }

    public function sample(Request $request, $id)
    {
        $book = Book::with(['translations'])->where('id', $id)->where('is_published', true)->first();

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        return $this->success([
            'id' => $book->id,
            'title' => $book->title,
            'is_free' => $book->is_free,
            'pages' => $this->samplePages($book->content),
        ], 'Book sample');
    }

    protected function samplePages(?string $content): array
    {
        $content = trim((string) $content);

        if ($content === '') {
            return [];
        }

        $words = preg_split('/\s+/u', $content);
        $words = array_values(array_filter($words));

        $wordsPerPage = 180;
        $maxPages = 5;
        $pages = [];
        $current = '';
        $count = 0;

        foreach ($words as $word) {
            if ($count >= $wordsPerPage && $current !== '') {
                if (count($pages) >= $maxPages) {
                    break;
                }
                $pages[] = trim($current);
                $current = '';
                $count = 0;
            }
            $current .= ($current === '' ? '' : ' ').$word;
            $count++;
        }

        if (trim($current) !== '') {
            $pages[] = trim($current);
        }

        return array_slice($pages, 0, $maxPages);
    }

    public function read(Request $request, $id)
    {
        $book = Book::where('id', $id)->where('is_published', true)->first();

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        $user = $request->user();

        if (! $book->isFree()) {
            if (! $user) {
                return $this->error('Please login to read this book', 401);
            }

            $accessService = app(BookAccessService::class);
            if (! $accessService->canAccess($user, $book)) {
                return $this->error('You need to purchase this book to read it', 403);
            }
        }

        if (! $book->file_path) {
            return $this->success([
                'id' => $book->id,
                'title' => $book->title,
                'content' => $book->content,
            ], 'Book content');
        }

        $path = storage_path('app/public/'.$book->file_path);

        if (! file_exists($path)) {
            return $this->success([
                'id' => $book->id,
                'title' => $book->title,
                'content' => $book->content,
            ], 'Book content (no file available)');
        }

        $book->increment('downloads_count');

        return $this->success([
            'id' => $book->id,
            'title' => $book->title,
            'content' => $book->content,
            'file_url' => '/storage/'.$book->file_path,
            'file_type' => $book->file_type,
            'file_size' => $book->file_size,
        ], 'Book ready to read');
    }

    public function toggleBookmark(Request $request, $id)
    {
        $book = Book::where('id', $id)->where('is_published', true)->first();

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        $existing = Bookmark::where('user_id', $request->user()->id)
            ->where('book_id', $book->id)->first();

        if ($existing) {
            $existing->delete();

            return $this->success(['bookmarked' => false], 'Removed from bookmarks');
        }

        Bookmark::create([
            'user_id' => $request->user()->id,
            'book_id' => $book->id,
        ]);

        return $this->success(['bookmarked' => true], 'Added to bookmarks');
    }

    public function bookmarks(Request $request)
    {
        $books = Book::with(['category', 'author'])
            ->whereHas('bookmarks', fn ($q) => $q->where('user_id', $request->user()->id))
            ->where('is_published', true)
            ->latest()
            ->get()
            ->map(fn ($b) => $this->bookPayload($b));

        return $this->success($books);
    }

    protected function bookPayload(Book $book): array
    {
        $locale = app()->getLocale();

        $translations = $book->relationLoaded('translations') ? $book->translations : $book->translations()->get();
        $translation = $translations->firstWhere('locale', $locale) ?? $translations->firstWhere('locale', 'km');

        return [
            'id' => $book->id,
            'slug' => $book->slug,
            'title' => $translation->title ?? $book->slug,
            'description' => $translation->description ?? null,
            'cover_image' => $book->cover_image ? url('/storage/'.$book->cover_image) : null,
            'file_type' => $book->file_type,
            'pages' => $book->pages,
            'price' => (float) $book->price,
            'is_free' => $book->is_free,
            'views_count' => $book->views_count,
            'downloads_count' => $book->downloads_count,
            'category' => $book->category ? [
                'id' => $book->category->id,
                'slug' => $book->category->slug,
                'name' => $book->category->name,
            ] : null,
            'author' => $book->author ? [
                'id' => $book->author->id,
                'slug' => $book->author->slug,
                'name' => $book->author->name,
            ] : null,
            'rating' => $book->averageRating(),
            'reviews_count' => $book->reviewsCount(),
            'created_at' => $book->created_at->toDateTimeString(),
        ];
    }

    public function storeReview(Request $request, $id)
    {
        $book = Book::where('id', $id)->where('is_published', true)->first();

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $review = Review::updateOrCreate(
            ['user_id' => $request->user()->id, 'book_id' => $book->id],
            ['rating' => $request->rating, 'comment' => $request->comment]
        );

        return $this->success([
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'rating_avg' => $book->averageRating(),
        ], 'Review saved successfully');
    }
}
