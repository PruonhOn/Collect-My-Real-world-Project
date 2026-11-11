<?php

namespace App\Models;

use App\Enums\ContainerStatus;
use Database\Factories\ShipmentContainerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A piece of equipment a consignment travels in.
 *
 * One of these exists for each container on a consignment, from the moment the
 * number is booked to the moment the box is handed over. A road movement has none,
 * which is a legitimate state rather than an incomplete record: a consignment on a
 * truck is not missing its containers, it does not use them.
 *
 * The row records a *pairing* - this box, on this consignment, in this state - and
 * not the box's history. A container is a physical asset that goes round again, so
 * the same `container_number` legitimately appears on a different consignment
 * months later. That is why the unique index is on the pair and the number is not
 * globally unique; see the migration for the reasoning.
 *
 * Because of that, there is no soft delete here even though `shipments` has one.
 * A container row is not independently referenced by anything - no invoice quotes
 * it, no document is attached to it - and archiving a consignment should archive
 * its equipment, not leave rows the archive screen cannot explain. The one
 * exception is `shipment_id` itself, which cascades on a hard delete because
 * there is then nothing left for a row to describe.
 */
#[Fillable([
    'shipment_id',
    'container_number',
    'iso_code',
    'type',
    'status',
    'seal_number',
    'tare_weight_kg',
    'max_gross_weight_kg',
    'gross_weight_kg',
    'notes',
])]
class ShipmentContainer extends Model
{
    /** @use HasFactory<ShipmentContainerFactory> */
    use HasFactory;

    public const NUMBER = 'container_number';

    public const STATUS = 'status';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::STATUS => ContainerStatus::class,
            'tare_weight_kg' => 'integer',
            'max_gross_weight_kg' => 'integer',
            'gross_weight_kg' => 'integer',
        ];
    }

    /**
     * A container number is an asset identifier, not a word.
     *
     * Trimmed and upper-cased, and this is load-bearing in a way the equivalent on
     * `Shipment::normalise` is not. The unique index here is a composite pair
     * rather than a global one, so it is a much closer target: a booking
     * confirmation arriving with ` mscu1234567 ` and a manual entry with
     * `MSCU1234567` would both pass the same consignment's unique check and
     * create two rows for one box, which reads on the manifest as a shipment with a
     * spare container nobody can account for.
     *
     * Normalising on save as well as before validation, for the reason the
     * controller's `validated()` merges this in: the comparison has to be against
     * the value about to be stored, not the casing that happened to arrive.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        foreach ([self::NUMBER, 'seal_number'] as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $normalised[$column] = strtoupper(trim((string) $attributes[$column]));
            }
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        /*
         * On the model rather than in the controller, because a container number
         * arriving in different casing must not become two rows whoever wrote it -
         * a seeder, a factory, an import - and the same argument the shipment
         * applies to its reference.
         */
        static::saving(function (self $container): void {
            foreach (static::normalise($container->getAttributes()) as $column => $value) {
                $container->setAttribute($column, $value);
            }
        });
    }

    /**
     * The consignment this box is loaded on.
     *
     * Non-nullable, so this cannot come back null. Declared on both sides for the
     * same reason as every other pair in the schema: the shipment screen has to be
     * able to show its manifest without a second query.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Whether the box is carrying nothing.
     *
     * A real state rather than a null: an empty container is the single most common
     * thing a carrier reports, because a box waiting at a depot for cargo is a
     * detention charge ticking away, and a module that could not say "empty" would
     * have no way to show that.
     */
    public function isEmpty(): bool
    {
        return $this->status === ContainerStatus::Empty;
    }

    /**
     * Whether this box is stopped and somebody has to do something about it.
     *
     * The worklist the module opens on. Mirrors `ContainerStatus::needsAttention()`
     * exactly - a filter returning rows whose own flag says false would be a bug
     * rather than a filter.
     */
    public function needsAttention(): bool
    {
        return $this->status?->needsAttention() ?? false;
    }

    /**
     * Whether the box has been handed over and nothing more is expected of it.
     *
     * Only `delivered`. A damaged box is not finished: the consignment still has to
     * arrive, quite possibly in a replacement.
     */
    public function isClosed(): bool
    {
        return $this->status?->isFinal() ?? false;
    }

    /**
     * The load inside the box, against the rating printed on the door.
     *
     * A method rather than a column because it is a comparison of two fields on
     * this row and both are nullable: an unweighed box has no gross weight and
     * saying "0% full" would be a confident answer to a question nobody asked.
     * Null means there is not enough recorded to say, which is the honest answer
     * and is also the only one that cannot be wrong.
     */
    public function utilisationPercent(): ?int
    {
        if ($this->gross_weight_kg === null || $this->max_gross_weight_kg === null) {
            return null;
        }

        if ($this->max_gross_weight_kg === 0) {
            return null;
        }

        // Against the rating rather than the payload: the tare has already been
        // lifted off by whoever weighed the loaded box, so the remaining capacity is
        // the rating less the gross, not less the net.
        return (int) round(
            (($this->max_gross_weight_kg - $this->gross_weight_kg) / $this->max_gross_weight_kg) * 100
        );
    }

    /**
     * Boxes that are not moving.
     *
     * A whereHas on the shipment for the same reason ShipmentTracking::scopeOpen:
     * the relation also excludes soft-deleted consignments, so a consignment that
     * was archived does not leave its stuck boxes in a worklist pointing at
     * something the operator cannot open.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereHas('shipment', fn (Builder $shipment): Builder => $shipment->open());
    }

    /**
     * The worklist: a box that is held, damaged, or otherwise needs a decision.
     *
     * Derived from the enum rather than stored as a flag, so that correcting a
     * status cannot leave a row disagreeing with itself.
     *
     * @param  Builder<self>  $query
     */
    public function scopeNeedsAttention(Builder $query): void
    {
        // whereIn, not `where` with an array as the operator. Laravel's two-argument
        // where() only widens an array value to an IN under a version-specific path,
        // and when it does not the array is bound as a single parameter instead -
        // which silently produced `status = 'customs_hold'` and quietly dropped
        // damaged boxes off the worklist. Every other status filter in this schema
        // is an explicit whereIn for the same reason.
        $query->whereIn(self::STATUS, [
            ContainerStatus::CustomsHold->value,
            ContainerStatus::Damaged->value,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithStatus(Builder $query, ContainerStatus $status): void
    {
        $query->where(self::STATUS, $status->value);
    }
}
