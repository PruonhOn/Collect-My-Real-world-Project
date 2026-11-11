<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\Incoterm;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One request for space with a carrier, and the answer that came back.
 *
 * A booking comes before the shipment it produces. An operator raises one
 * against a shipper's instruction, sends it to the carrier, and only once the
 * carrier confirms is there a consignment to track - so the booking is the
 * parent and the shipments are what comes out of it.
 *
 * Structurally this is the same shape as Shipment, and deliberately so: the same
 * partners, the same routing, the same cargo. What differs is intent and
 * lifecycle. A booking records what was *asked for* - hence `requested_etd`
 * rather than `etd` - and a booking that is turned down or lapses is still a
 * real record of the request, which is why this table exists at all rather than
 * being a status on shipments.
 *
 * Like a shipment, a booking is not active or inactive: it is confirmed or it is
 * not, so there is a `status` and no `is_active`. Every partner is nullable on
 * purpose; see the migration for why.
 */
#[Fillable([
    'reference',
    'status',
    'shipper_id',
    'consignee_id',
    'carrier_id',
    'vessel_id',
    'port_of_loading_id',
    'port_of_discharge_id',
    'origin_site_id',
    'destination_site_id',
    'incoterm',
    'incoterm_place',
    'cargo_description',
    'pieces',
    'weight_kg',
    'volume_cbm',
    'requested_etd',
    'requested_eta',
    'notes',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    public const REFERENCE = 'reference';

    public const STATUS = 'status';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::STATUS => BookingStatus::class,
            'incoterm' => Incoterm::class,
            'pieces' => 'integer',
            'weight_kg' => 'integer',
            // decimal, not float, for the same reason as a shipment: this is
            // summed against a booking's shipments to check the split adds up.
            'volume_cbm' => 'decimal:3',
            // Y-m-d, not a full timestamp. A requested sailing is a day, and a
            // time component invites a timezone bug for a field that has none.
            'requested_etd' => 'date:Y-m-d',
            'requested_eta' => 'date:Y-m-d',
        ];
    }

    /**
     * A booking reference is a document number, not a word.
     *
     * Trimmed and upper-cased for the same reason as a shipment reference: the
     * same number arrives from a carrier's confirmation email, a phone call and
     * a manual entry, and ` bkg-77 ` must not become a second booking when
     * `BKG-77` is already on file. The `unique` index cannot help with that.
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
     * console commands that never pass through a controller.
     */
    protected static function booted(): void
    {
        static::saving(function (self $booking): void {
            foreach (static::normalise($booking->getAttributes()) as $column => $value) {
                $booking->setAttribute($column, $value);
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeWithStatus(Builder $query, BookingStatus $status): void
    {
        $query->where(self::STATUS, $status->value);
    }

    /**
     * Requests that are still live: everything not cancelled or expired.
     *
     * The counterpart to a status filter, and the list a bookings screen opens
     * with. Derived from the statuses rather than enumerated, so it stays right
     * the day a status is added.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn(self::STATUS, [
            BookingStatus::Cancelled->value,
            BookingStatus::Expired->value,
        ]);
    }

    /**
     * Requests that have been sent but not yet answered.
     *
     * The follow-up list. A booking sitting in `requested` is somebody's
     * outstanding task, and on an unfiltered list it is indistinguishable from
     * the drafts nobody has sent yet.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAwaitingConfirmation(Builder $query): void
    {
        $query->where(self::STATUS, BookingStatus::Requested->value);
    }

    /**
     * Requests that were started but never sent to a carrier.
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
     * The same question as the scope above, asked of a single record, so the list
     * and the form cannot disagree.
     */
    public function isUnassigned(): bool
    {
        return $this->carrier_id === null;
    }

    /**
     * Whether this booking has produced any shipment yet.
     */
    public function hasShipments(): bool
    {
        return $this->shipments()->exists();
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

    /**
     * The consignments raised from this booking.
     *
     * More than one is normal, not a modelling mistake: a booking covers a
     * quantity of space, and a booking large enough to be split is raised as a
     * shipment per sailing. This is the reason `shipments.booking_id` exists on
     * the shipment side.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }
}
