<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Book\StoreBookRequest;
use App\Http\Requests\Admin\Book\UpdateBookRequest;
use App\Http\Resources\AdminBookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $books = Book::query()
            ->with(['translations', 'categories', 'authors'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->whereHas('translations', function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->string('search').'%');
                });
            })
            ->when(in_array($request->string('type')->upper()->toString(), ['FREE', 'PAID']), function ($query) use ($request) {
                $query->where('type', $request->string('type')->upper());
            })
            ->when(in_array($request->string('status')->upper()->toString(), ['DRAFT', 'PUBLISHED', 'ARCHIVED']), function ($query) use ($request) {
                $query->where('status', $request->string('status')->upper());
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'books' => AdminBookResource::collection($books),
            'pagination' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ], 'Books retrieved.');
    }

    public function show(string $id): JsonResponse
    {
        $book = Book::with(['translations', 'categories', 'authors'])->find($id);

        if (! $book) {
            return $this->error('Book not found.', null, 404);
        }

        return $this->success(new AdminBookResource($book), 'Book retrieved.');
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = DB::transaction(function () use ($request) {
            $book = Book::create($request->safe()->only([
                'slug', 'type', 'price', 'status', 'language', 'cover_image',
            ]));

            $this->syncTranslations($book, $request->input('translations', []));
            $this->syncRelations($book, $request->input('category_ids', []), $request->input('author_ids', []));

            if ($book->status === Book::STATUS_PUBLISHED) {
                $book->forceFill(['published_at' => $book->published_at ?? now()])->save();
            }

            return $book;
        });

        return $this->success(
            new AdminBookResource($book->load(['translations', 'categories', 'authors'])),
            'Book created.',
            201
        );
    }

    public function update(string $id, UpdateBookRequest $request): JsonResponse
    {
        $book = Book::find($id);

        if (! $book) {
            return $this->error('Book not found.', null, 404);
        }

        DB::transaction(function () use ($book, $request) {
            $book->update($request->safe()->only([
                'slug', 'type', 'price', 'status', 'language', 'cover_image',
            ]));

            $this->syncTranslations($book, $request->input('translations', []));
            $this->syncRelations($book, $request->input('category_ids', []), $request->input('author_ids', []));

            if ($book->status === Book::STATUS_PUBLISHED && $book->published_at === null) {
                $book->forceFill(['published_at' => now()])->save();
            }
        });

        return $this->success(
            new AdminBookResource($book->load(['translations', 'categories', 'authors'])),
            'Book updated.'
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $book = Book::find($id);

        if (! $book) {
            return $this->error('Book not found.', null, 404);
        }

        $book->delete();

        return $this->success(null, 'Book deleted.');
    }

    private function syncTranslations(Book $book, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            $book->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'title' => $fields['title'],
                    'description' => $fields['description'] ?? null,
                    'content' => $fields['content'] ?? null,
                    'meta_title' => $fields['meta_title'] ?? null,
                    'meta_description' => $fields['meta_description'] ?? null,
                ]
            );
        }
    }

    private function syncRelations(Book $book, array $categoryIds, array $authorIds): void
    {
        $book->categories()->sync($categoryIds);
        $book->authors()->sync($authorIds);
    }
}
