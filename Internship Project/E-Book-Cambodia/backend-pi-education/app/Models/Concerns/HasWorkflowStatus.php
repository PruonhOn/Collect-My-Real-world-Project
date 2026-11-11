<?php

namespace App\Models\Concerns;

use App\Models\User;

/**
 * The editorial workflow shared by author-submitted books:
 *
 *   draft -> pending -> approved | rejected -> published
 *
 * `status` is the single source of truth. Every state change goes through
 * {@see transitionTo()} so the audit columns (submitted_at, reviewed_at,
 * reviewed_by, review_note, published_at) can never drift out of sync with
 * the status itself.
 */
trait HasWorkflowStatus
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_PUBLISHED = 'published';

    /** Every valid state, in workflow order. */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_PUBLISHED,
    ];

    /**
     * States an author may still edit, resubmit or delete. Anything under
     * review or already live is frozen until an admin acts on it.
     */
    public const AUTHOR_EDITABLE_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_REJECTED,
    ];

    /**
     * Whether the author who owns this book may still change it.
     */
    public function isEditableByAuthor(): bool
    {
        return in_array($this->status, self::AUTHOR_EDITABLE_STATUSES, true);
    }

    /**
     * Whether the book is currently locked awaiting an admin decision.
     */
    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Move the book to a new workflow state, maintaining the audit columns and
     * the legacy `is_published` mirror.
     *
     * @param  string  $status  One of the STATUS_* constants.
     * @param  User|null  $reviewer  The admin taking the action, for verdicts.
     * @param  string|null  $note  Review note, or the reason for a rejection.
     */
    public function transitionTo(string $status, ?User $reviewer = null, ?string $note = null): void
    {
        abort_if(! in_array($status, self::STATUSES, true), 422, 'Unknown book status.');

        $attributes = [
            'status' => $status,
            'is_published' => $status === self::STATUS_PUBLISHED,
        ];

        if ($status === self::STATUS_PENDING) {
            // Submitting or resubmitting clears the previous verdict.
            $attributes['submitted_at'] = now();
            $attributes['review_note'] = null;
            $attributes['reviewed_at'] = null;
            $attributes['reviewed_by'] = null;
        }

        if (in_array($status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
            $attributes['reviewed_at'] = now();
            $attributes['reviewed_by'] = $reviewer?->id;
            $attributes['review_note'] = $note;
        }

        if ($status === self::STATUS_DRAFT) {
            // Pulling a book back to draft discards any earlier verdict.
            $attributes['published_at'] = null;
            $attributes['review_note'] = null;
            $attributes['reviewed_at'] = null;
            $attributes['reviewed_by'] = null;
        }

        $this->forceFill($attributes)->save();
    }
}
