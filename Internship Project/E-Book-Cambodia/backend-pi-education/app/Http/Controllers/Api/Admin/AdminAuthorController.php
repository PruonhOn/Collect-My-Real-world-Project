<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Book;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password as PasswordRules;

class AdminAuthorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $authors = Author::query()
            ->withCount('books')
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20))
            ->withQueryString();

        return response()->json(['authors' => $authors]);
    }

    /**
     * Author profile plus every book they have published and sales figures.
     */
    public function show(Author $author): JsonResponse
    {
        $books = $author->ownedBooksQuery()
            ->with('category')
            ->withCount(['purchases' => function ($query) {
                $query->where('status', Purchase::STATUS_PAID);
            }])
            ->withSum(['purchases' => function ($query) {
                $query->where('status', Purchase::STATUS_PAID);
            }], 'amount')
            ->orderByDesc('purchases_count')
            ->get()
            ->map(fn (Book $book) => [
                'id' => $book->id,
                'title' => $book->title,
                'cover_image' => $book->cover_image,
                'is_published' => $book->is_published,
                'view_count' => (int) $book->view_count,
                'price' => $book->price,
                'category' => $book->category,
                'purchases_count' => (int) $book->purchases_count,
                'revenue' => round((float) ($book->purchases_sum_amount ?? 0), 2),
            ])
            ->values();

        return response()->json([
            'author' => $author->loadCount('books'),
            'books' => $books,
            'summary' => [
                'books' => $books->count(),
                'sold' => (int) $books->sum('purchases_count'),
                'revenue' => round((float) $books->sum('revenue'), 2),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:authors,email'],
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:2048'],
        ]);

        $data = $validated;
        unset($data['password_confirmation']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('author-photos', 'public');
        }

        $author = Author::create($data);

        return response()->json(['author' => $author], 201);
    }

    public function update(Request $request, Author $author): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:authors,email,'.$author->id],
            'password' => ['sometimes', 'confirmed', PasswordRules::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'in:active,suspended'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:2048'],
        ]);

        $data = $validated;
        unset($data['password_confirmation']);

        if (isset($data['password']) || ($data['status'] ?? null) === Author::STATUS_SUSPENDED) {
            $author->tokens()->delete();
        }

        if ($request->hasFile('photo')) {
            if ($author->photo && ! str_starts_with($author->photo, 'http')) {
                Storage::disk('public')->delete($author->photo);
            }
            $data['photo'] = $request->file('photo')->store('author-photos', 'public');
        }

        $author->update($data);

        return response()->json(['author' => $author->fresh()]);
    }

    public function destroy(Author $author): JsonResponse
    {
        $affected = $author->ownedBooksQuery()
            ->update(['author_id' => null, 'author' => null]);

        $author->delete();

        return response()->json([
            'message' => $affected > 0
                ? "Author deleted. Removed from {$affected} book(s)."
                : 'Author deleted.',
        ]);
    }

    /**
     * Suspend or activate an author. Suspending revokes all active sessions.
     */
    public function updateStatus(Request $request, Author $author): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,suspended'],
        ]);

        $author->status = $validated['status'];

        if ($validated['status'] === Author::STATUS_SUSPENDED) {
            $author->tokens()->delete();
        }

        $author->save();

        return response()->json(['author' => $author->fresh()]);
    }

    /**
     * Reset an author's password (admin-provided) and revoke active sessions.
     */
    public function resetPassword(Request $request, Author $author): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', PasswordRules::defaults()],
        ]);

        $author->password = $validated['password'];
        $author->tokens()->delete();
        $author->save();

        return response()->json(['message' => 'Password reset successfully.']);
    }

    /**
     * Manually mark the author's email as verified.
     */
    public function verifyEmail(Author $author): JsonResponse
    {
        if ($author->email_verified_at === null) {
            $author->forceFill(['email_verified_at' => now()])->save();
        }

        return response()->json(['author' => $author->fresh()]);
    }
}
