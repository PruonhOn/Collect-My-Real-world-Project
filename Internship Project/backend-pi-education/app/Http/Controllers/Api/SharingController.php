<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Purchase;
use App\Models\SharedAccess;
use App\Models\SharingKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SharingController extends Controller
{
    public function generate(Request $request, Book $book): JsonResponse
    {
        $user = $request->user();

        $owns = Purchase::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', Purchase::STATUS_PAID)
            ->exists();

        if (! $owns) {
            return response()->json([
                'message' => 'You must own this book before you can share it.',
            ], 403);
        }

        $activeKey = SharingKey::where('owner_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', SharingKey::STATUS_ACTIVE)
            ->first();

        if ($activeKey && ! $activeKey->isExpired() && ! $activeKey->isFull()) {
            return response()->json([
                'message' => 'An active sharing key already exists for this book.',
                'key' => $activeKey,
            ]);
        }

        $key = SharingKey::create([
            'owner_id' => $user->id,
            'book_id' => $book->id,
            'max_uses' => SharingKey::MAX_USES,
            'used_count' => 0,
            'status' => SharingKey::STATUS_ACTIVE,
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'message' => 'Sharing key generated. You can share it with up to 3 people.',
            'key' => $key,
        ], 201);
    }

    public function redeem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $key = SharingKey::where('code', $validated['code'])->first();

        if (! $key || $key->status !== SharingKey::STATUS_ACTIVE || $key->isExpired() || $key->isFull()) {
            return response()->json([
                'message' => 'This sharing key is invalid, expired, or has reached its usage limit.',
            ], 422);
        }

        $user = $request->user();

        if ($key->owner_id === $user->id) {
            return response()->json(['message' => 'You cannot redeem your own sharing key.'], 422);
        }

        $existing = SharedAccess::where('sharing_key_id', $key->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->status === SharedAccess::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'You already have access to this book via this key.',
                'shared_access' => $existing,
            ]);
        }

        $alreadyOwned = Purchase::where('user_id', $user->id)
            ->where('book_id', $key->book_id)
            ->where('status', Purchase::STATUS_PAID)
            ->exists();

        if ($alreadyOwned) {
            return response()->json(['message' => 'You already own this book.'], 422);
        }

        $sharedAccess = SharedAccess::firstOrCreate(
            [
                'sharing_key_id' => $key->id,
                'user_id' => $user->id,
                'status' => SharedAccess::STATUS_ACTIVE,
            ],
            ['book_id' => $key->book_id]
        );

        if ($sharedAccess->wasRecentlyCreated) {
            $key->increment('used_count');
        }

        return response()->json([
            'message' => 'Access granted! The book has been added to your library.',
            'shared_access' => $sharedAccess->load('book'),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $generated = SharingKey::with('book')
            ->where('owner_id', $user->id)
            ->withCount('sharedAccess')
            ->latest()
            ->get();

        $received = SharedAccess::with('book', 'sharingKey.owner')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'generated' => $generated,
            'received' => $received,
        ]);
    }

    public function revoke(Request $request, SharedAccess $sharedAccess): JsonResponse
    {
        $isOwner = $sharedAccess->sharingKey?->owner_id === $request->user()->id;
        $isUser = $sharedAccess->user_id === $request->user()->id;

        if (! $isOwner && ! $isUser) {
            return response()->json(['message' => 'You cannot revoke this access.'], 403);
        }

        $sharedAccess->update([
            'status' => SharedAccess::STATUS_REVOKED,
            'revoked_at' => now(),
        ]);

        $sharedAccess->sharingKey?->decrement('used_count');

        return response()->json(['message' => 'Access revoked.']);
    }
}
