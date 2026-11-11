<?php

namespace App\Models;

use App\Models\Concerns\HasWorkflowStatus as WorkflowStatus;
use App\Services\BookStorageService;
use App\Services\PreviewPdfService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'category_id', 'author_id', 'title', 'slug', 'description', 'content', 'author', 'cover_image',
    'file_path', 'file_disk', 'file_name', 'file_hash', 'file_type', 'file_size', 'language', 'pages', 'price',
    'preview_pages',
    'preview_mode',
    'cover_disk',
    'is_free', 'is_published', 'is_vip_only', 'is_coming_soon',
    'publisher', 'subject', 'education_level', 'grade', 'publication_date', 'audience',
    'view_count', 'purchase_count',
    'status', 'review_note', 'reviewed_by', 'submitted_at', 'reviewed_at', 'published_at',
])]
class Book extends Model
{
    use HasFactory;
    use WorkflowStatus;

    /**
     * No free-page gate: a non-buyer gets nothing.
     */
    public const PREVIEW_MODE_NONE = 'none';

    /**
     * The original "free preview" gate: the leading pages are free.
     */
    public const PREVIEW_MODE_PREVIEW = 'preview';

    /**
     * The "readtest" gate: the leading pages are offered as a trial read.
     */
    public const PREVIEW_MODE_READTEST = 'readtest';

    /**
     * Every gate type an admin may pick.
     *
     * @return array<int, string>
     */
    public static function previewModes(): array
    {
        return [
            self::PREVIEW_MODE_NONE,
            self::PREVIEW_MODE_PREVIEW,
            self::PREVIEW_MODE_READTEST,
        ];
    }

    /**
     * Whether the book currently offers any free-page gate.
     */
    public function hasPreviewGate(): bool
    {
        return $this->preview_mode !== self::PREVIEW_MODE_NONE
            && (int) $this->preview_pages > 0;
    }

    /**
     * New books start without a free preview until an admin opts one in.
     */
    protected $attributes = [
        'preview_pages' => 0,
        'preview_mode' => self::PREVIEW_MODE_NONE,
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'file_size' => 'integer',
        'pages' => 'integer',
        'preview_pages' => 'integer',
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'is_vip_only' => 'boolean',
        'is_coming_soon' => 'boolean',
        'publication_date' => 'date',
        'view_count' => 'integer',
        'purchase_count' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Keep the legacy `is_published` flag in step with the workflow status.
     *
     * `is_published` is a mirror, not an independent flag: the published scope,
     * the reader and the catalog all read it, and the review workflow writes
     * `status`. Deriving the mirror here means those two can never disagree.
     *
     * An explicit `is_published` from a legacy caller is still respected, so
     * existing admin edits and factories keep working; only the gaps are
     * filled in, and `transitionTo()` always wins by setting both at once.
     */
    protected static function booted(): void
    {
        static::saving(function (Book $book): void {
            $explicit = array_key_exists('is_published', $book->getAttributes());

            if ($book->status === null) {
                return;
            }

            if (! $explicit) {
                $book->is_published = $book->status === self::STATUS_PUBLISHED;
            }

            if ($book->is_published && $book->published_at === null) {
                $book->published_at = now();
            }
        });

        // Cached preview PDFs are derived from the file and the preview
        // length, so both must invalidate them. Best effort: pruning never
        // stands in the way of saving or deleting the book itself.
        static::saved(function (Book $book): void {
            if ($book->wasChanged(['file_path', 'file_hash', 'preview_pages'])) {
                $book->pruneCachedPreviews();
            }
        });

        static::deleted(function (Book $book): void {
            $book->deleteCachedPreviews();
        });
    }

    /**
     * Drop stale cached previews after the source file or the preview length
     * changed.
     */
    public function pruneCachedPreviews(): void
    {
        try {
            app(PreviewPdfService::class)->prune($this);
        } catch (\Throwable) {
            // Best effort - see booted().
        }
    }

    /**
     * Drop every cached preview of this book.
     */
    public function deleteCachedPreviews(): void
    {
        try {
            app(PreviewPdfService::class)->pruneAll($this);
        } catch (\Throwable) {
            // Best effort - see booted().
        }
    }

    /**
     * The author this book belongs to. Named `authorRelation` because the
     * `author` column on the books table stores the display name for
     * compatibility with existing search/filter/list code.
     */
    public function authorRelation(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * The table of contents entries (chapters and nested sections) for this
     * book, flat and ordered by their reading position. Callers rebuild the
     * tree from `parent_id` when a nested structure is needed.
     */
    public function tocItems(): HasMany
    {
        return $this->hasMany(BookTocItem::class)->orderBy('position');
    }

    public function sharingKeys(): HasMany
    {
        return $this->hasMany(SharingKey::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('status', Review::STATUS_APPROVED);
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * The admin user who last reviewed this book, if any.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Restrict a query to a single editorial workflow state.
     */
    public function scopeWithStatus($query, ?string $status)
    {
        return $query->when($status, fn ($q) => $q->where('status', $status));
    }

    /**
     * Restrict a query to the books the given user has already paid for.
     * Pending and refunded purchases do not count as owning a book.
     */
    public function scopePurchasedBy($query, int $userId)
    {
        return $query->whereHas('purchases', fn ($purchases) => $purchases
            ->where('user_id', $userId)
            ->where('status', Purchase::STATUS_PAID));
    }

    /**
     * The disk a book's document actually lives on. New records are written to
     * the configured file disk; older records remember their original disk so
     * reads/deletes keep working after a storage migration.
     */
    public function fileStorageDisk(): string
    {
        return $this->file_disk ?: (string) config('books.file_disk');
    }

    /**
     * The disk a book's cover actually lives on.
     */
    public function coverStorageDisk(): string
    {
        return $this->cover_disk ?: (string) config('books.cover_disk');
    }

    public function getCoverUrlAttribute(): ?string
    {
        return app(BookStorageService::class)->coverUrl(
            $this->cover_image,
            $this->coverStorageDisk()
        );
    }

    /**
     * Stable content version for a book, derived from the stored file so that
     * front-end caches (IndexedDB) can be invalidated whenever the actual file
     * is replaced. Reads only filesystem metadata - it never loads the file.
     */
    public function contentVersion(): string
    {
        if (! $this->file_path) {
            return 'meta-'.substr(md5((string) $this->updated_at?->timestamp), 0, 16);
        }

        try {
            $disk = Storage::disk($this->fileStorageDisk());

            if (! $disk->exists($this->file_path)) {
                return 'missing-'.substr(md5((string) $this->updated_at?->timestamp), 0, 16);
            }

            $size = (int) $disk->size($this->file_path);
            $modified = (int) $disk->lastModified($this->file_path);

            return substr(md5($this->file_path.'|'.$size.'|'.$modified), 0, 16);
        } catch (\Throwable) {
            return 'meta-'.substr(md5((string) $this->updated_at?->timestamp), 0, 16);
        }
    }
}
