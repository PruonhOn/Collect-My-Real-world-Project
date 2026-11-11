<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class SignedStreamService
{
    /**
     * How long a signed stream link stays valid. Kept short so that an
     * intercepted link cannot be abused as a permanent public URL.
     */
    public const TTL_SECONDS = 300;

    /**
     * Build a short-lived signed URL for a book stream. The link is bound to
     * the book id, the requesting user (or "guest" for free books) and an
     * expiry timestamp, so it can never be re-used on another book or outlive
     * its lifetime.
     */
    public function generate(int $bookId, int $expires, ?int $userId = null): string
    {
        $signature = $this->signatureFor($bookId, $userId, $expires);
        $userPart = (string) ($userId ?? 'guest');

        return "/api/v1/reader/{$bookId}/stream?expires={$expires}&user={$userPart}&signature={$signature}";
    }

    /**
     * Validate a signed stream request. Returns the user the link was issued
     * to, or null for guest-signed free-book links. Aborts with 401 when the
     * signature is missing, tampered with, or expired.
     */
    public function verifyRequest(Request $request, Book $book): ?User
    {
        $expires = (int) $request->query('expires', 0);
        $signature = (string) $request->query('signature', '');
        $userPart = (string) $request->query('user', '');

        if ($expires <= 0 || $signature === '' || $userPart === '') {
            throw new AuthenticationException('This book stream link is invalid.');
        }

        if ($expires < now()->getTimestamp()) {
            throw new AuthenticationException('This book stream link has expired.');
        }

        if ($userPart === 'guest') {
            return null;
        }

        if (! ctype_digit($userPart)) {
            throw new AuthenticationException('This book stream link is invalid.');
        }

        $userId = (int) $userPart;

        if (! hash_equals($this->signatureFor($book->id, $userId, $expires), $signature)) {
            throw new AuthenticationException('This book stream link is invalid.');
        }

        $user = User::find($userId);

        if (! $user) {
            throw new AuthenticationException('This book stream link is invalid.');
        }

        return $user;
    }

    private function signatureFor(int $bookId, ?int $userId, int $expires): string
    {
        $userPart = (string) ($userId ?? 'guest');

        return hash_hmac('sha256', "book:{$bookId}|user:{$userPart}|expires:{$expires}", (string) config('app.key'));
    }
}
