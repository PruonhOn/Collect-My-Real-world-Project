<?php

namespace App\Models;

use App\Enums\TrackingMilestone;
use Database\Factories\ShipmentTrackingFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What we last heard about a consignment.
 *
 * One of these exists for every shipment, from the moment the shipment is raised
 * until the row is destroyed with it. It is created by the shipment, not by a
 * person: the `tracking` module grants `view` and `update` and no `create`, so
 * there is no endpoint that could make one, and the record is not optional in the
 * way a booking is. A consignment with no tracking row is not "not tracked yet",
 * * nobody has asked* - and the difference matters, because the screen this backs
 * is the one an operator opens to find out what has gone quiet.
 *
 * Everything here is nullable. A freshly raised consignment has nothing reported
 * about it, and requiring a milestone to create the row would mean inventing a
 * report that no carrier has made.
 *
 * Note what this table does *not* have: a status of its own, a copy of the ETA, or
 * a soft delete. The shipment owns its lifecycle, `revised_eta` records what the
 * carrier promised rather than what was planned, and the absence of a delete
 * permission is why there is no `deleted_at` - see the migration.
 */
#[Fillable([
    'shipment_id',
    'last_milestone',
    'current_location',
    'last_reported_at',
    'revised_eta',
    'delay_reason',
    'source',
    'notes',
])]
class ShipmentTracking extends Model
{
    /** @use HasFactory<ShipmentTrackingFactory> */
    use HasFactory;

    public const MILESTONE = 'last_milestone';

    /**
     * How long before a report counts as stale.
     *
     * Lives on the model rather than in the controller because two things need it
     * and they must not be allowed to disagree: the `stale` filter that builds the
     * worklist, and the `is_stale` flag on each row. A filter that returned rows
     * the response then labelled "not stale" would be a bug wearing a plausible
     * hat, and the reason `isDelayed` and `scopeDelayed` are written as a pair
     * applies here too.
     *
     * A constant rather than a request parameter because a threshold every caller
     * sets differently is not a threshold. Three days suits a deep-sea sailing,
     * where a carrier reporting weekly is ordinary; local cartage would want less,
     * which is the case for adding the concept rather than for accepting one per
     * request.
     */
    public const STALE_AFTER_DAYS = 3;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::MILESTONE => TrackingMilestone::class,
            'last_reported_at' => 'datetime',
            // Day precision, like the shipment's own dates: a revised arrival is a
            // date on a carrier's advice, and a time component would be a fiction
            // the column then has to be trusted about.
            'revised_eta' => 'date:Y-m-d',
        ];
    }

    /**
     * Nothing here needs canonicalising, and that is the whole implementation.
     *
     * The models that carry a `reference` or a `code` normalise it so two spellings
     * of the same document number cannot become two records before the unique
     * index gets a chance to object. This table has neither: a tracking row is
     * identified by the consignment it belongs to, and the free-text fields are
     * the carrier's own wording, which is recorded as sent rather than tidied -
     * rewriting "OCD - Over Container Delayed" into our own phrasing would lose
     * the fact that it came from them.
     *
     * Declared because RecordController::validated() calls it on every model, and
     * the alternative is a base class that has to ask whether a resource has
     * anything to normalise before every write.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        return [];
    }

    /**
     * The consignment this describes.
     *
     * Not nullable in practice. The column is a non-nullable foreign key, so
     * BelongsTo rather than HasOne-from-the-shipment cannot come back null, and
     * the relationship is declared on both sides for the same reason as every
     * other pair in the schema: the shipment screen has to be able to show the
     * last report without a second query.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Reports a delay, by either of the two things that record one.
     *
     * Deliberately not derived from the shipment's ETA. Comparing a revised date
     * against the planned one needs the shipment relation loaded, and a derived
     * flag that depends on a relation is a flag that disagrees with itself
     * depending on what happened to be eager loaded. Both of these columns mean
     * "the carrier told us something changed", which is answerable from this row
     * alone.
     *
     * The filter below mirrors this exactly. A caller listing `delayed=1` and
     * getting rows whose own `is_delayed` says false would be looking at a bug,
     * not a filter.
     */
    public function isDelayed(): bool
    {
        return $this->delay_reason !== null || $this->revised_eta !== null;
    }

    /**
     * Whether anything has ever been reported.
     *
     * The distinction the whole screen turns on: `false` here means nobody has
     * reported, which is different from a report that is merely old.
     */
    public function isUnreported(): bool
    {
        return $this->last_reported_at === null;
    }

    /**
     * Whether we have stopped hearing about this consignment.
     *
     * Two ways to go quiet, and both mean the same thing to an operator: never
     * reported on, or not reported on for long enough. A consignment that has
     * never been reported on is stale from the moment it is raised, which is why
     * this is not simply "the last report is old" - `last_reported_at` being null
     * is not a timestamp three days ago, it is no timestamp at all.
     *
     * $now is injectable so the same rule can be applied to a stored value in a
     * query and to a model in a response without one of them reaching for the
     * clock differently.
     */
    public function isStale(?DateTimeInterface $now = null): bool
    {
        if ($this->isUnreported()) {
            return true;
        }

        $cutoff = self::staleCutoff($now);

        return $this->last_reported_at < $cutoff;
    }

    /**
     * The instant before which a report counts as old.
     *
     * Static so `scopeStale` and `isStale` are computed from one expression
     * rather than two that have to be kept in step by hand.
     *
     * Written as `modify()` on the interface rather than as Carbon's `subDays()`,
     * because this accepts any `DateTimeInterface` and a caller holding a plain
     * `DateTimeImmutable` should get a cutoff rather than a type error.
     */
    public static function staleCutoff(?DateTimeInterface $now = null): DateTimeInterface
    {
        return ($now ?? now())->modify('-'.self::STALE_AFTER_DAYS.' days');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeStale(Builder $query, ?DateTimeInterface $now = null): void
    {
        $cutoff = self::staleCutoff($now);

        $query->where(function (Builder $group) use ($cutoff): void {
            $group->whereNull('last_reported_at')
                ->orWhere('last_reported_at', '<', $cutoff);
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeDelayed(Builder $query): void
    {
        // Grouped because this is an OR. Appended bare it would bind only to the
        // second condition, so `delayed=1&open_only=1` would match any tracking
        // row with a revised ETA regardless of the shipment, or any row at all if
        // the caller had filtered on something else first.
        $query->where(function (Builder $group): void {
            $group->whereNotNull('delay_reason')
                ->orWhereNotNull('revised_eta');
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeUnreported(Builder $query): void
    {
        $query->whereNull('last_reported_at');
    }

    /**
     * Reports that came in before a given moment.
     *
     * Takes the cutoff rather than a number of days so the caller decides what
     * "stale" means. Three days is right for a deep-sea sailing and absurd for a
     * local cartage, and no single default in here would be right for both.
     *
     * @param  Builder<self>  $query
     */
    public function scopeReportedBefore(Builder $query, DateTimeInterface $cutoff): void
    {
        $query->whereNotNull('last_reported_at')
            ->where('last_reported_at', '<', $cutoff);
    }

    /**
     * Only consignments that are still moving.
     *
     * A whereHas on the shipment rather than a column here, because "closed" is a
     * shipment fact. The relationship also does a second job: it excludes
     * soft-deleted shipments, so a consignment that was deleted does not leave a
     * tracking row in the worklist pointing at nothing.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereHas('shipment', fn (Builder $shipment): Builder => $shipment->open());
    }
}
