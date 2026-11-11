<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminBookController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Book::with(['category', 'author', 'translations']);

        if ($request->has('search') && $request->search) {
            $query->whereHas('translations', function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->has('status') && $request->status === 'draft') {
            $query->where('is_published', false);
        }

        if ($request->has('status') && $request->status === 'published') {
            $query->where('is_published', true);
        }

        if ($request->has('type')) {
            if ($request->type === 'free') {
                $query->where('is_free', true);
            } elseif ($request->type === 'paid') {
                $query->where('is_free', false);
            }
        }

        if ($request->has('category') && $request->category) {
            $query->where('category_id', $request->category);
        }

        $books = $query->latest()->paginate($request->per_page ?? 15);

        $mapped = $books->getCollection()->map(function ($book) {
            return $this->adminBookPayload($book);
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

    public function show(Request $request, int $id)
    {
        $book = Book::with(['translations', 'category', 'author'])->find($id);

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        return $this->success($this->adminBookPayload($book));
    }

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'category_id' => ['required', 'exists:categories,id'],
            'author_id' => ['nullable', 'exists:authors,id'],
            'title_km' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_km' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'content_km' => ['nullable', 'string'],
            'content_en' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_free' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'book_file' => ['nullable', 'file', 'max:51200'],
            'file_type' => ['nullable', 'string', 'in:pdf,epub'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['category_id', 'author_id', 'price', 'is_free', 'is_published', 'pages', 'file_type']);

        $data['slug'] = Str::slug($request->title_km).'-'.Str::random(5);
        $data['is_free'] = $request->boolean('is_free', false);
        $data['is_published'] = $request->boolean('is_published', false);
        $data['file_type'] = $data['file_type'] ?? 'pdf';

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
        }

        if ($request->hasFile('book_file')) {
            $file = $request->file('book_file');
            $data['file_path'] = $file->store('books/files', 'public');
            $data['file_size'] = $file->getSize();
            $data['file_type'] = $file->getClientOriginalExtension() ?: 'pdf';
        }

        $book = Book::create($data);

        $book->translations()->create([
            'locale' => 'km',
            'title' => $request->title_km,
            'description' => $request->description_km,
            'content' => $request->content_km,
        ]);

        if ($request->title_en) {
            $book->translations()->create([
                'locale' => 'en',
                'title' => $request->title_en,
                'description' => $request->description_en,
                'content' => $request->content_en,
            ]);
        }

        return $this->success($this->adminBookPayload($book->load('translations')), 'Book created successfully', 201);
    }

    public function update(Request $request, int $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'author_id' => ['nullable', 'exists:authors,id'],
            'title_km' => ['sometimes', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_km' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'content_km' => ['nullable', 'string'],
            'content_en' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_free' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
            'book_file' => ['nullable', 'file', 'max:51200'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $data = $request->only(['category_id', 'author_id', 'price', 'is_free', 'is_published', 'pages']);

        if ($request->has('is_free')) {
            $data['is_free'] = $request->boolean('is_free');
        }
        if ($request->has('is_published')) {
            $data['is_published'] = $request->boolean('is_published');
        }

        if ($request->hasFile('cover_image')) {
            if ($book->cover_image) {
                Storage::disk('public')->delete($book->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('books/covers', 'public');
        }

        if ($request->hasFile('book_file')) {
            if ($book->file_path) {
                Storage::disk('public')->delete($book->file_path);
            }
            $file = $request->file('book_file');
            $data['file_path'] = $file->store('books/files', 'public');
            $data['file_size'] = $file->getSize();
        }

        $book->update($data);

        if ($request->has('title_km')) {
            $kmTranslation = $book->translations()->where('locale', 'km')->first();
            if ($kmTranslation) {
                $kmTranslation->update([
                    'title' => $request->title_km,
                    'description' => $request->description_km,
                    'content' => $request->content_km,
                ]);
            } else {
                $book->translations()->create([
                    'locale' => 'km',
                    'title' => $request->title_km,
                    'description' => $request->description_km,
                    'content' => $request->content_km,
                ]);
            }
        }

        if ($request->has('title_en')) {
            $enTranslation = $book->translations()->where('locale', 'en')->first();
            if ($request->title_en) {
                if ($enTranslation) {
                    $enTranslation->update([
                        'title' => $request->title_en,
                        'description' => $request->description_en,
                        'content' => $request->content_en,
                    ]);
                } else {
                    $book->translations()->create([
                        'locale' => 'en',
                        'title' => $request->title_en,
                        'description' => $request->description_en,
                        'content' => $request->content_en,
                    ]);
                }
            }
        }

        return $this->success($this->adminBookPayload($book->fresh(['translations', 'category', 'author'])), 'Book updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        if ($book->cover_image) {
            Storage::disk('public')->delete($book->cover_image);
        }
        if ($book->file_path) {
            Storage::disk('public')->delete($book->file_path);
        }

        $book->delete();

        return $this->success(null, 'Book deleted successfully');
    }

    public function togglePublish(Request $request, int $id)
    {
        $book = Book::find($id);

        if (! $book) {
            return $this->error('Book not found', 404);
        }

        $book->update(['is_published' => ! $book->is_published]);

        return $this->success(['is_published' => $book->is_published], 'Book status updated');
    }

    protected function adminBookPayload(Book $book): array
    {
        $kmTranslation = $book->translations()->where('locale', 'km')->first();
        $enTranslation = $book->translations()->where('locale', 'en')->first();

        return [
            'id' => $book->id,
            'slug' => $book->slug,
            'title_km' => $kmTranslation->title ?? null,
            'title_en' => $enTranslation->title ?? null,
            'description_km' => $kmTranslation->description ?? null,
            'description_en' => $enTranslation->description ?? null,
            'category_id' => $book->category_id,
            'category' => $book->category ? [
                'id' => $book->category->id,
                'slug' => $book->category->slug,
                'name' => $book->category->name,
            ] : null,
            'author_id' => $book->author_id,
            'author' => $book->author ? [
                'id' => $book->author->id,
                'slug' => $book->author->slug,
                'name' => $book->author->name,
            ] : null,
            'cover_image' => $book->cover_image ? url('/storage/'.$book->cover_image) : null,
            'file_path' => $book->file_path,
            'file_type' => $book->file_type,
            'file_size' => $book->file_size,
            'pages' => $book->pages,
            'price' => (float) $book->price,
            'is_free' => $book->is_free,
            'is_published' => $book->is_published,
            'views_count' => $book->views_count,
            'downloads_count' => $book->downloads_count,
            'rating' => $book->averageRating(),
            'reviews_count' => $book->reviewsCount(),
            'created_at' => $book->created_at->toDateTimeString(),
        ];
    }
}