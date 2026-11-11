<?php

namespace App\Models;

use App\Enums\CustomsStatus;
use Database\Factories\ShipmentCustomsClearanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * How a consignment stands with the customs authority at one port.
 *
 * One row per consignment per port, so a transshipment has one row per port it
 * cleared at. That is the reason the table is keyed on the pair rather than on the
 * consignment alone, and it is what lets a refusal be recorded without erasing the
 * consignment's other customs history: a filing rejected at Colombo is a second
 * row for Colombo, not a rewrite of the Singapore one.
 *
 * Nothing here is mandatory beyond the consignment and the port. A clearance record
 * is opened the moment somebody needs to chase something, and the truth about a
 * filing that has not gone in yet is that it has not gone in yet. `pending` is the
 * default rather than a required choice for that reason.
 *
 * Deliberately carries no money. Duty assessed and duty paid are real, and they
 * belong to the finance module, which owns the currency and the arithmetic - see
 * the migration for the reasoning.
 */
#[Fillable([
    'shipment_id',
    'port_id',
    'status',
    'entry_number',
    'broker',
    'submitted_at',
    'cleared_at',
    'hold_reason',
    'notes',
])]
class ShipmentCustomsClearance extends Model
{
    /** @use HasFactory<ShipmentCustomsClearanceFactory> */
    use HasFactory;

    public const STATUS = 'status';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::STATUS => CustomsStatus::class,
            'submitted_at' => 'datetime',
            'cleared_at' => 'datetime',
        ];
    }

    /**
     * There is nothing to canonicalise.
     *
     * `entry_number` is the tempting candidate and is deliberately left alone. The
     * shipment's own `reference` and the container's number are identifiers we issue
     * or that follow one format, so folding case and whitespace on them is
     * unambiguously right. A customs entry number belongs to the authority and
     * arrives however it arrives - `1234/2026`, `1234-2026`, ` 1234/2026 ` - and
     * which of those are the same filing is a question for the authority's own
     * numbering rules, not one to guess at on the way into the database. It is
     * recorded as sent.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        return [];
    }

    /**
     * The consignment this filing belongs to.
     *
     * Non-nullable, so this cannot come back null. Declared on both sides for the
     * same reason as every other pair in the schema.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * The authority this filing was made with.
     *
     * The only relation on this model that can be null on a live row: a port can be
     * removed from the lookup table without a cargo manifest gaining a hole in it,
     * because a port's absence says nothing about whether the clearance happened.
     * See the migration.
     */
    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    /**
     * Whether the goods are still with the authority.
     *
     * The state before any release, which is the answer to "what is stuck at the
     * border". Wider than `isClosed()` on purpose - see CustomsStatus::isHeld().
     */
    public function isHeld(): bool
    {
        return $this->status?->isHeld() ?? false;
    }

    /**
     * Whether the release has been carried out and nothing more is expected.
     *
     * Only `completed`, and not `approved`: cleared on paper is not the same as
     * collected, and the gap between them is where the quay charges start.
     */
    public function isClosed(): bool
    {
        return $this->status?->isFinal() ?? false;
    }

    /**
     * Whether a rejection has to be chased.
     *
     * The counterpart to the containers worklist. A refusal is not a stage to wait
     * out, it is somebody's problem, so it belongs on the same list as a box held
     * at a border and an operator should not have to check two filters to find out
     * whether anything is stuck.
     */
    public function needsAttention(): bool
    {
        return $this->status?->needsAttention() ?? false;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithStatus(Builder $query, CustomsStatus $status): void
    {
        $query->where(self::STATUS, $status->value);
    }

    /**
     * Filings somebody has to chase.
     *
     * Narrowed to consignments that are still moving, for the same reason the
     * container worklist is: a consignment that was cancelled a month ago is not
     * waiting for a customs answer, and a row that says it is would sit on the list
     * forever.
     *
     * Only a refusal, which is the same set `CustomsStatus::needsAttention()` returns
     * and for the same reason the two are kept identical: this is a filter a user
     * applies and then reads the rows of, so a row that came back carrying
     * `needs_attention: false` would make the list untrustworthy. A filing that has
     * not happened yet is on `scopeHeld` instead - it is stuck rather than wrong, and
     * putting it here would put it on both lists for no gain.
     *
     * @param  Builder<self>  $query
     */
    public function scopeNeedsAttention(Builder $query): void
    {
        $query->whereHas('shipment', fn (Builder $shipment): Builder => $shipment->open())
            ->where(self::STATUS, CustomsStatus::Rejected->value);
    }

    /**
     * The goods are still with the authority, regardless of the consignment.
     *
     * Kept separate from `scopeNeedsAttention` because the two answer different
     * questions and a caller legitimately wants either: "what is waiting on a
     * signature" is not "what is wrong", and a submission that is merely in the
     * queue is not a problem anybody has hit yet.
     *
     * @param  Builder<self>  $query
     */
    public function scopeHeld(Builder $query): void
    {
        $query->whereIn(self::STATUS, [
            CustomsStatus::Pending->value,
            CustomsStatus::Submitted->value,
            CustomsStatus::UnderReview->value,
        ]);
    }

    /**
     * Only consignments that are still moving.
     *
     * The relation also excludes soft-deleted consignments, so an archived
     * consignment does not leave its held filings in a worklist pointing at
     * something the operator cannot open.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereHas('shipment', fn (Builder $shipment): Builder => $shipment->open());
    }
}
