<?php

namespace App\Models;

use App\Enums\DocumentType;
use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A piece of paperwork the business holds.
 *
 * The description of a document, not the file itself: `document_versions` holds
 * the uploads, one row per attempt, and the current file is the highest
 * `version_number` among them. That split is the whole design decision here, and
 * it exists because paperwork gets amended - a bill of lading is reissued, a
 * certificate is re-scanned, somebody uploads the wrong page and has to fix it.
 * Overwriting the file would leave the register able to show only what was
 * uploaded last, which is exactly the copy that cannot be produced when the
 * question is "did we send them the correction or the original?".
 *
 * The consignment is optional, and that is the shape of most of this table rather
 * than a gap in it. The fleet insurance certificate, the ISO certificate and last
 * year's audited accounts belong to the business and not to a job. A bill of
 * lading, a packing list or a delivery order belongs to exactly one consignment
 * and is what a person opens this screen for.
 *
 * Soft-deleted, because a document register is evidence and the parent row is
 * worth keeping as a record that the document existed and when it was removed.
 * What "removed" means for the *files* is decided in `DocumentController::destroy()`,
 * which deletes them along with their version rows - a parent that outlives its own
 * files with rows still naming them would be a register full of documents that
 * exist and cannot be opened.
 */
#[Fillable([
    'document_type',
    'title',
    'reference',
    'shipment_id',
    'issued_on',
    'expires_on',
    'notes',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    use SoftDeletes;

    public const TITLE = 'title';

    public const REFERENCE = 'reference';

    public const DOCUMENT_TYPE = 'document_type';

    public const ISSUED_ON = 'issued_on';

    public const EXPIRES_ON = 'expires_on';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::DOCUMENT_TYPE => DocumentType::class,
            self::ISSUED_ON => 'date',
            self::EXPIRES_ON => 'date',
        ];
    }

    /**
     * Titles are trimmed, and nothing else is normalised.
     *
     * No upper-casing, unlike the reference numbers elsewhere in this schema: a
     * document title is prose ("Maersk bill of lading, SHP-2026-0001"), it is
     * written by a person who chose the wording on purpose, and shouting it would
     * only make the register harder to read. `reference` is trimmed but not
     * folded either, because a carrier's number is theirs and a mismatch in case
     * is how you find that you were quoted the wrong one.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        foreach ([self::TITLE, self::REFERENCE] as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $normalised[$column] = trim((string) $attributes[$column]);
            }
        }

        return $normalised;
    }

    /**
     * The consignment this paperwork is about, where it is about one.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Every copy of this document, oldest first.
     *
     * Ordered here rather than at each call site, because "version 4" only means
     * anything if it is read against the others and a listing that came back in
     * insertion order would put a re-scan of an old page above the amended bill
     * of lading that supersedes it.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number');
    }

    /**
     * The copy a download serves.
     *
     * `latestOfMany` rather than a `current_version_id` column on the parent: a
     * second column would be a second copy of the truth, and the two could disagree
     * - an amendment written without the flag, or a flag pointing at a version row
     * that was pruned. "The highest version number" is one rule that cannot be
     * out of step with the rows, and it is a HasOne so the whole list loads in one
     * query rather than one per row.
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany('version_number');
    }

    /**
     * Whether this document's validity has run out.
     *
     * Strictly before today rather than "on or after today": a certificate whose
     * expiry is today's date is still valid for the whole of today, and marking it
     * expired at midnight would start a renewal clock that nobody is late for.
     */
    public function isExpired(): bool
    {
        return $this->expires_on !== null && $this->expires_on->isBefore(today());
    }

    /**
     * Whole days until expiry. Negative once it has passed, null when it never does.
     *
     * Compared at day granularity, from local midnight to local midnight. The
     * difference matters at the edges: a certificate expiring tomorrow at 00:00 is
     * one day of warning left, not two, and rounding the hours in would report
     * "0d" the afternoon before it ran out.
     */
    public function expiresInDays(): ?int
    {
        if ($this->expires_on === null) {
            return null;
        }

        return (int) today()->diffInDays($this->expires_on->startOfDay(), false);
    }

    /**
     * Whether this document is the business's problem this month.
     *
     * Expired, or close enough to expiry that the reissue has to start now. A
     * document with no expiry date is never on this list - a packing list does not
     * lapse, and saying it "needs attention" would put every piece of cargo
     * paperwork on the same worklist as the certificates actually worth chasing.
     *
     * The threshold is `logistics.documents.expiry_warning_days` rather than a
     * literal, so the same number drives the row badge, the filter and anything
     * later that raises a notification.
     */
    public function needsAttention(): bool
    {
        if ($this->expires_on === null) {
            return false;
        }

        if ($this->isExpired()) {
            return true;
        }

        return $this->expiresInDays() <= (int) config('logistics.documents.expiry_warning_days', 30);
    }

    /**
     * Documents that have run out.
     *
     * @param  Builder<self>  $query
     */
    public function scopeExpired(Builder $query): void
    {
        $query->whereNotNull(self::EXPIRES_ON)->whereDate(self::EXPIRES_ON, '<', today());
    }

    /**
     * Documents expiring within the window and not yet past it.
     *
     * Strictly after today on the near bound, so this filter and `expired` are
     * disjoint rather than overlapping - somebody asking "what is about to lapse"
     * and getting yesterday's certificates back in the same list has to read every
     * row twice. `whereDate` on both bounds because the column is a `date` and a
     * timestamp comparison would drop the closing day.
     *
     * @param  Builder<self>  $query
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->whereNotNull(self::EXPIRES_ON)
            ->whereDate(self::EXPIRES_ON, '>', today())
            ->whereDate(self::EXPIRES_ON, '<=', today()->addDays($days));
    }

    /**
     * Every document with an expiry date at all, whatever it is.
     *
     * A scope rather than a column check at each call site because the register
     * reads by this set: everything with a date on it, ordered by how soon.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDated(Builder $query): void
    {
        $query->whereNotNull(self::EXPIRES_ON);
    }
}
