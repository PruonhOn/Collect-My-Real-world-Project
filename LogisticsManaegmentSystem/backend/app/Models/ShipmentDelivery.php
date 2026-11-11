<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Database\Factories\ShipmentDeliveryFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The last leg of a consignment: collected, driven, handed over.
 *
 * A consignment can have several of these, and that is the design rather than an
 * oversight. A consignment is routinely delivered in parts, and a failed attempt
 * that is retried is a second row rather than an edit of the first - the failure is
 * the reason the retry happened and it has to stay on the record for anybody asking
 * why the delivery took a week.
 *
 * The status is ours, the dates are facts, and the vehicle and driver are somebody
 * else's fleet recorded here. All three are nullable: a delivery booked with a
 * carrier and a slot has no vehicle assigned until a run is planned, and a consignment
 * is not less real for it.
 *
 * No soft delete, and the reason is the permission set: a `cancelled` status is what
 * a called-off delivery gets, which is both a record and an answer. There is nothing
 * else to add for that to be worth hiding.
 */
#[Fillable([
    'shipment_id',
    'reference',
    'status',
    'truck_id',
    'driver_id',
    'scheduled_at',
    'picked_up_at',
    'delivered_at',
    'contact_name',
    'contact_phone',
    'delivery_address',
    'pod_reference',
    'failure_reason',
    'notes',
])]
class ShipmentDelivery extends Model
{
    /** @use HasFactory<ShipmentDeliveryFactory> */
    use HasFactory;

    public const REFERENCE = 'reference';

    public const STATUS = 'status';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::STATUS => DeliveryStatus::class,
            'scheduled_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * A delivery reference is a document number, not a word.
     *
     * Trimmed and upper-cased for the same reason `Shipment::normalise` does it: a
     * carrier's job sheet and a manual entry will not agree on casing, and the
     * unique index on the column will not help with a case-insensitive match. The
     * prefix comes from `logistics.numbering.delivery` and is `DLV`, but it is
     * supplied by the client rather than generated - see `normalise()`'s caller in
     * RecordController for why the merged form is what validation compares against.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        if (array_key_exists(self::REFERENCE, $attributes) && $attributes[self::REFERENCE] !== null) {
            $normalised[self::REFERENCE] = strtoupper(trim((string) $attributes[self::REFERENCE]));
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        static::saving(function (self $delivery): void {
            foreach (static::normalise($delivery->getAttributes()) as $column => $value) {
                $delivery->setAttribute($column, $value);
            }
        });
    }

    /**
     * The consignment being delivered.
     *
     * Non-nullable, so this cannot come back null. Declared on both sides for the
     * same reason as every other pair in the schema.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * The vehicle on the leg, if one has been assigned.
     *
     * Nullable for two separate reasons, both real: a delivery can be booked with a
     * carrier and a slot before any particular vehicle is planned, and a vehicle can
     * leave the fleet afterwards. Neither should blank out a record of a delivery that
     * actually happened.
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Whether the cargo is on the road in a vehicle now.
     *
     * Narrower than "not finished", and the difference is the point of having
     * `picking_up` at all: a collection that has not happened is a demurrage problem
     * at the terminal, and one that has is an appointment problem at the door.
     */
    public function isMoving(): bool
    {
        return $this->status?->isMoving() ?? false;
    }

    /**
     * Whether this delivery is over, one way or the other.
     *
     * Handed over or called off. Not `failed` - a failed attempt is emphatically not
     * the end, and the consignment is still in the vehicle.
     */
    public function isClosed(): bool
    {
        return $this->status?->isFinal() ?? false;
    }

    /**
     * Whether an attempt has been made and did not work.
     *
     * Kept apart from `isClosed()` because the two drive opposite actions: a closed
     * delivery needs nothing, a failed one needs a phone call and a rescheduled slot.
     */
    public function hasFailed(): bool
    {
        return $this->status === DeliveryStatus::Failed;
    }

    /**
     * Whether the consignment has not been handed over yet.
     *
     * A failed attempt counts, and that is the whole point of keeping it: the cargo
     * is still with the driver and the delivery is genuinely outstanding, however
     * many attempts have been made.
     */
    public function isOutstanding(): bool
    {
        return ! $this->isClosed();
    }

    /**
     * Whether the slot has passed and the cargo still has not been handed over.
     *
     * Derived from the schedule rather than stored, so a rescheduled slot cannot leave
     * a row disagreeing with itself about whether it is late.
     *
     * A delivery with no slot at all is not overdue. Nothing has been missed, and
     * treating a booking as late before it has a time is how a worklist ends up full
     * of rows that were never actually late.
     *
     * A failed attempt is overdue in neither sense, and it is deliberately the one
     * outstanding state excluded here. Outstanding says the cargo has not been handed
     * over; overdue says a slot passed that was still going to happen. A failed
     * attempt's slot did come due and the driver turned up - the row needs a new slot
     * booked, not chasing, and leaving it on the late list buries the rows where
     * nobody turned up at all under rows that were handled on the day.
     *
     * `$now` is injectable so the same rule can be applied to a stored value in a
     * query and to a model in a response without the two reaching for the clock
     * differently - the arrangement `ShipmentTracking` uses for its staleness.
     */
    public function isOverdue(?DateTimeInterface $now = null): bool
    {
        if (! $this->isOutstanding() || $this->hasFailed() || $this->scheduled_at === null) {
            return false;
        }

        return $this->scheduled_at < ($now ?? now());
    }

    /**
     * Only deliveries that are still on the books.
     *
     * Keyed on this row's own status rather than the consignment's, which is the one
     * deliberate departure from `ShipmentTracking::scopeOpen` and the other two
     * modules. A consignment can be marked `delivered` while this row still says
     * `scheduled` because the two are updated by different people, and hiding the
     * row on the strength of the consignment's status would bury the disagreement
     * rather than surface it.
     *
     * The relation is constrained all the same, but for the narrow reason the
     * tracking worklist gives: an archived consignment's deliveries are not rows
     * pointing at something the operator can open, and the relationship's soft
     * delete scope is what excludes them. Deliberately not `whereHas('shipment',
     * open())` - the delivery, not the consignment, is what is still on the books.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereHas('shipment')
            ->whereNotIn(self::STATUS, [
                DeliveryStatus::Delivered->value,
                DeliveryStatus::Cancelled->value,
            ]);
    }

    /**
     * The worklist: a slot that passed and nothing was handed over.
     *
     * Excludes failed attempts for the reason `isOverdue()` gives, and the exclusion
     * is repeated here rather than left to the caller: the flag on a row and the list
     * it appears in have to agree, and a scope that quietly disagreed with the method
     * would put a row on a page that then said it was not overdue.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query, ?DateTimeInterface $now = null): void
    {
        $query->open()
            ->where(self::STATUS, '!=', DeliveryStatus::Failed->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', $now ?? now());
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithStatus(Builder $query, DeliveryStatus $status): void
    {
        $query->where(self::STATUS, $status->value);
    }
}
