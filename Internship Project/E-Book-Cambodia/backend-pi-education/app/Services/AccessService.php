<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Purchase;
use App\Models\SharedAccess;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AccessService
{
    /**
     * Words per page the reader derives from plain-text content. Mirrors
     * `splitContentIntoPages()` in the frontend so a preview cut ends exactly
     * where the reader's page break falls.
     */
    private const TEXT_WORDS_PER_PAGE = 250;

    /**
     * How much of a book the given viewer may read: the whole document
     * (`full`), only the leading free pages (`preview`), or nothing at all
     * (`none`). Guests and signed-in users without full access both fall back
     * to the free pages when the book offers a gate (free preview or readtest).
     */
    public function readScope(?User $user, Book $book): string
    {
        if (! $this->requiresAccessCheck($book)) {
            return 'full';
        }

        if ($user !== null && $this->canRead($user, $book)) {
            return 'full';
        }

        return $book->hasPreviewGate() ? 'preview' : 'none';
    }

    /**
     * The portion of a book's plain-text content a viewer in the given scope
     * may receive: everything for `full`, nothing for `none`, and the leading
     * preview pages for `preview`.
     *
     * @param  'full'|'preview'|'none'  $scope
     */
    public function visibleContent(Book $book, string $scope, string $content): string
    {
        if ($scope === 'full') {
            return $content;
        }

        if ($scope === 'none') {
            return '';
        }

        return $this->previewContent($book, $content);
    }

    /**
     * Cut plain-text content down to the first `preview_pages` pages, using
     * the same page rules as the reader: explicit `===` section breaks when
     * present, otherwise 250-word pages.
     */
    private function previewContent(Book $book, string $content): string
    {
        $text = trim($content);

        if ($text === '') {
            return '';
        }

        $previewPages = max(1, (int) $book->preview_pages);

        $sections = preg_split('/\n{1,}\s*={3,}\s*\n{1,}/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sections = array_values(array_filter(array_map('trim', $sections), fn (string $section) => $section !== ''));

        if (count($sections) > 1) {
            return implode("\n===\n", array_slice($sections, 0, $previewPages));
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_slice($words, 0, $previewPages * self::TEXT_WORDS_PER_PAGE));
    }

    /**
     * Determine whether a user can read/access a book.
     * Access is granted via: paid purchase or active shared access.
     */
    public function hasAccess(User $user, ?int $bookId): bool
    {
        if (! $bookId) {
            return false;
        }

        $purchase = Purchase::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->where('status', Purchase::STATUS_PAID)
            ->exists();

        if ($purchase) {
            return true;
        }

        return SharedAccess::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->where('status', SharedAccess::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * Whether reading this book must be gated behind an access check at all.
     * VIP-only books are always gated, even when flagged as free, because an
     * active VIP membership is the only thing that unlocks them.
     */
    public function requiresAccessCheck(Book $book): bool
    {
        return ! $book->is_free || $book->is_vip_only;
    }

    /**
     * The user has access only if they own the book or have active shared access.
     * Free books need no access; active VIP membership covers all paid books.
     *
     * VIP-only books are the exception and are decided first: an active VIP
     * membership is the *only* way in, so neither the free flag, a purchase,
     * nor a sharing key unlocks them for anyone else.
     */
    public function canRead(User $user, Book $book): bool
    {
        if ($book->is_vip_only) {
            return VipService::isActive($user);
        }

        if ($book->is_free) {
            return true;
        }

        if (VipService::isActive($user)) {
            return true;
        }

        return $this->hasAccess($user, $book->id);
    }

    /**
     * Whether the user currently has an active VIP membership.
     */
    public function isVip(User $user): bool
    {
        return VipService::isActive($user);
    }

    /**
     * Books the user currently has lawful access to (owned + shared).
     */
    public function accessibleBookIds(User $user): array
    {
        $owned = Purchase::where('user_id', $user->id)
            ->where('status', Purchase::STATUS_PAID)
            ->whereNotNull('book_id')
            ->pluck('book_id');

        $shared = SharedAccess::where('user_id', $user->id)
            ->where('status', SharedAccess::STATUS_ACTIVE)
            ->pluck('book_id');

        return $owned->merge($shared)->unique()->all();
    }

    /**
     * Flag every book in a listing that the signed-in user already bought, so
     * catalogue UIs can mark owned books without a request per book. Sets
     * `is_purchased` to false for everyone else, including guests, who own
     * nothing.
     *
     * @param  Collection<int, Book>|LengthAwarePaginator  $books
     */
    public function attachPurchaseFlags($books, ?int $userId): void
    {
        $collection = $books instanceof LengthAwarePaginator ? $books->getCollection() : $books;

        $purchasedBookIds = [];

        if ($userId && $collection->isNotEmpty()) {
            $purchasedBookIds = Purchase::query()
                ->where('user_id', $userId)
                ->where('status', Purchase::STATUS_PAID)
                ->whereIn('book_id', $collection->pluck('id'))
                ->pluck('book_id')
                ->all();
        }

        $collection->transform(function (Book $book) use ($purchasedBookIds) {
            $book->setAttribute('is_purchased', in_array($book->id, $purchasedBookIds, true));

            return $book;
        });

        if ($books instanceof LengthAwarePaginator) {
            $books->setCollection($collection);
        }
    }
}
