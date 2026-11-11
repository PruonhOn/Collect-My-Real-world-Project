<?php

namespace App\Models;

use App\Enums\Incoterm;
use App\Enums\ShipmentStatus;
use Database\Factories\ShipmentFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One consignment: what moves, for whom, on whose account, and where it is.
 *
 * This is the first model in the system that is not master data. The difference
 * shows up in the shape rather than in any framework feature: master-data records
 * are looked up and are either active or inactive, while a shipment is a thing
 * that moves through a lifecycle. So there is no `is_active` here and a `status`
 * instead, and the reference is the human key the shipment is quoted and invoiced
 * by rather than a lookup code.
 *
 * Every partner is nullable on purpose. See the migration for why.
 */
#[Fillable([
    'reference',
    'status',
    'booking_id',
    'shipper_id',
    'consignee_id',
    'carrier_id',
    'vessel_id',
    'port_of_loading_id',
    'port_of_discharge_id',
    'origin_site_id',
    'destination_site_id',
    'truck_id',
    'driver_id',
    'incoterm',
    'incoterm_place',
    'cargo_description',
    'pieces',
    'weight_kg',
    'volume_cbm',
    'etd',
    'eta',
    'actual_departure',
    'actual_arrival',
    'notes',
])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory, SoftDeletes;

    public const REFERENCE = 'reference';

    public const STATUS = 'status';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::STATUS => ShipmentStatus::class,
            'incoterm' => Incoterm::class,
            'pieces' => 'integer',
            'weight_kg' => 'integer',
            // decimal, not float: cargo volume is measured to three places and
            // summed across a manifest, so it must not drift on a float round.
            'volume_cbm' => 'decimal:3',
            'etd' => 'date:Y-m-d',
            'eta' => 'date:Y-m-d',
            'actual_departure' => 'datetime',
            'actual_arrival' => 'datetime',
        ];
    }

    /**
     * A consignment reference is a document number, not a word.
     *
     * Trimmed and upper-cased so ` shp-104 ` and `SHP-104` are one record: the
     * same reference arriving with different whitespace or casing from a booking
     * confirmation and a manual entry must not become two shipments, and the
     * `unique` index cannot help with that.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        if (array_key_exists(self::REFERENCE, $attributes)) {
            $normalised[self::REFERENCE] = strtoupper(trim((string) $attributes[self::REFERENCE]));
        }

        return $normalised;
    }

    /**
     * Guard the invariant for every write path, including factories, seeders and
     * console commands that never pass through a controller, and open the tracking
     * record every consignment is entitled to.
     */
    protected static function booted(): void
    {
        static::saving(function (self $shipment): void {
            foreach (static::normalise($shipment->getAttributes()) as $column => $value) {
                $shipment->setAttribute($column, $value);
            }
        });

        /*
         * Created, not updated: a consignment that already existed has a tracking
         * row, and re-saving it must not resurrect a deleted one.
         *
         * In an observer rather than in ShipmentController because this is the
         * module's substitute for a create endpoint. `tracking` grants `view` and
         * `update` only, so there is deliberately no route that can make this row,
         * and anything that raises a consignment - the API, a factory, the seeder,
         * a future import - has to end up with one. A controller-level hook would
         * leave every non-HTTP path with a consignment the tracking screen silently
         * cannot show.
         *
         * No milestone or report time: the truth about a consignment nobody has
         * heard anything about yet is that nothing has been reported.
         */
        static::created(function (self $shipment): void {
            $shipment->tracking()->create();
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithStatus(Builder $query, ShipmentStatus $status): void
    {
        $query->where(self::STATUS, $status->value);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn(self::STATUS, [
            ShipmentStatus::Delivered->value,
            ShipmentStatus::Cancelled->value,
        ]);
    }

    /**
     * Records that were started but never assigned to a carrier.
     *
     * The "needs attention" list. A draft with no carrier is a record somebody
     * began and abandoned, and it is invisible on an unfiltered list.
     *
     * @param  Builder<self>  $query
     */
    public function scopeUnassigned(Builder $query): void
    {
        $query->whereNull('carrier_id');
    }

    public function isClosed(): bool
    {
        return $this->status->isClosed();
    }

    /**
     * Whether the record was started but not yet assigned anywhere.
     *
     * The same question as the scope above, asked of a single record. Both exist
     * because the list needs one and the form needs the other, and they must not
     * be allowed to disagree.
     */
    public function isUnassigned(): bool
    {
        return $this->carrier_id === null;
    }

    /**
     * Whether a consignment that has not arrived is past its estimated arrival.
     *
     * Only meaningful while the consignment is still open, and only when a date
     * was actually set: a draft with no ETA is not late, it is simply unplanned,
     * and treating a missing date as an overrun would light up every draft in the
     * system on a list filtered for problems.
     *
     * Lives here rather than on the resource because two payloads need it -
     * ShipmentResource and the tracking worklist, which nests a reduced copy of
     * the consignment - and a shipment that read `is_late: true` while its own
     * tracking row said otherwise would be a bug in the comparison rather than in
     * the data.
     *
     * Takes `$now` for the same reason `scopeLate()` does: a dashboard built for
     * a point in time must answer "late" against that point, not against the
     * clock of whichever process renders the preview.
     */
    public function isLate(?DateTimeInterface $now = null): bool
    {
        if ($this->status->isClosed() || $this->eta === null) {
            return false;
        }

        return $this->eta->startOfDay()->lte(($now ?? now())->startOfDay());
    }

    /**
     * Consignments that `isLate()` would flag, as a query.
     *
     * Exists so the rule has one home. The method above and this scope are the
     * same question asked of one record and of a list, and the dashboard needs the
     * list form: it counts late consignments to show on the front page while
     * ShipmentResource answers the same question per row. Written out separately
     * the two would drift, and the first sign of it would be a headline number
     * that nobody can reproduce by filtering the list it summarises.
     *
     * The comparison is `<=`, not `<`, and that is not a rounding habit. The
     * method compares the ETA's midnight against now, so a consignment due *today*
     * is already past its arrival time by this evening and is late. Using `<`
     * here would count it as on time, which is the one row on the list an operator
     * is most likely to be looking at.
     *
     * Takes `$now` for the same reason `ShipmentDelivery::isOverdue()` does: the
     * same rule has to be applied to a stored value in a query and to a model in a
     * response without the two reaching for the clock differently.
     *
     * @param  Builder<self>  $query
     */
    public function scopeLate(Builder $query, ?DateTimeInterface $now = null): void
    {
        $query->open()
            ->whereNotNull('eta')
            ->whereDate('eta', '<=', ($now ?? now())->format('Y-m-d'));
    }

    public function shipper(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'shipper_id');
    }

    public function consignee(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'consignee_id');
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /**
     * The booking this consignment was raised from, if there was one.
     *
     * Nullable on purpose: a consignment entered from a paper bill of lading, or
     * one where the operator already held the space, has no booking behind it and
     * is still a perfectly valid shipment. BelongsTo rather than hasOne because a
     * booking large enough to be split produces a shipment per sailing, so one
     * booking can have several of these.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function portOfLoading(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_of_loading_id');
    }

    public function portOfDischarge(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_of_discharge_id');
    }

    public function originSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'origin_site_id');
    }

    public function destinationSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'destination_site_id');
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * The current tracking position for this consignment.
     *
     * HasOne rather than the tracking row pointing one way only, so the shipment
     * screen can show the last report without a second query. One-to-one is
     * enforced by the unique index on `shipment_trackings.shipment_id` rather than
     * by anything in here: Eloquent has no way to say "at most one", so a
     * database that only had the convention would eventually hold two.
     *
     * The relation is null only for a shipment created before this column
     * existed, or one whose row was removed out of band. Every shipment made since
     * gets one in `booted()`.
     */
    public function tracking(): HasOne
    {
        return $this->hasOne(ShipmentTracking::class);
    }

    /**
     * The boxes this consignment travels in, in booking order.
     *
     * One-to-many and legitimately empty: a movement on a truck is not missing its
     * containers, it does not use them, and a hasOne or a non-nullable count would
     * have to lie about that case somewhere.
     *
     * Ordered by id rather than left unordered, because a manifest is read in the
     * order it was booked. An unordered relation would also shuffle between two
     * requests for the same record, which on a comparison screen is the kind of
     * change nobody can explain afterwards.
     *
     * @return HasMany<ShipmentContainer, $this>
     */
    public function containers(): HasMany
    {
        return $this->hasMany(ShipmentContainer::class)->orderBy('id');
    }

    /**
     * The consignment's customs filings, one per port it cleared at.
     *
     * One-to-many and legitimately empty, for the same reason `containers()` is: air
     * freight and cargo cleared at origin has no filing here, and a consignment
     * without one is not missing paperwork so much as never having needed any.
     *
     * Ordered so a transshipment reads in the order the ports were actually visited,
     * which is the order they are listed in and the order the unique index would
     * otherwise leave to chance.
     *
     * @return HasMany<ShipmentCustomsClearance, $this>
     */
    public function customsClearances(): HasMany
    {
        return $this->hasMany(ShipmentCustomsClearance::class)->orderBy('id');
    }

    /**
     * The consignment's delivery legs, oldest first.
     *
     * One-to-many and legitimately several per consignment, which is the only place
     * in this schema where a consignment owns more than one of something. A delivery
     * in parts, or a failed attempt followed by a retry, is a separate record each
     * time, and the earlier attempt is what explains the later one.
     *
     * Ordered by id so the attempts read in the order they were made. A consignment
     * with three attempts that shuffled between two requests would be impossible to
     * reason about on a screen whose entire job is showing the sequence.
     *
     * @return HasMany<ShipmentDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(ShipmentDelivery::class)->orderBy('id');
    }

    /**
     * The priced lines raised against this consignment.
     *
     * `billable()` rather than every row, because a voided charge is a withdrawn
     * quote and must not appear in a consignment's billed figure - and this
     * relation is what that figure is summed over. The scope is the model's own,
     * so the set is the same one an invoice total is recomputed from.
     *
     * @return HasMany<Charge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class)->billable();
    }

    /**
     * What the consignment cost to run, whether or not it was billed on.
     *
     * Deliberately not scoped, unlike `charges()`. An expense is a payment that
     * has already happened, so a withdrawn quote does not un-spend it, and a
     * consignment's cost has to include work that will never be recharged. It
     * also includes duty and tax, which is correct for "what did this cost" and
     * is why the margin figure filters on `ExpenseCategory::isHeldMoney()` rather
     * than relying on this relation being margin-bearing.
     *
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * The vessel leg, if the shipment is on the water.
     *
     * A carrier is not necessarily the vessel: the booking may be with an NVOCC
     * on a carrier's vessel, so the two are separate references.
     */
    public function isSeaFreight(): bool
    {
        return $this->vessel_id !== null;
    }
}
