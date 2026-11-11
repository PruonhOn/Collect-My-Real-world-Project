<?php

namespace App\Models;

use Database\Factories\TruckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A road vehicle in the fleet.
 *
 * `capacity_kg` and `capacity_pallets` are both nullable and both real: a van is
 * usually quoted by weight, a box truck by pallet positions, and refusing to
 * record a vehicle with only one of the two would push the missing figure into
 * free-text notes.
 */
#[Fillable([
    'plate_number',
    'code',
    'carrier_id',
    'truck_type',
    'capacity_kg',
    'capacity_pallets',
    'make',
    'model',
    'year',
    'is_active',
    'notes',
])]
class Truck extends Model
{
    /** @use HasFactory<TruckFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity_kg' => 'integer',
            'capacity_pallets' => 'integer',
            // `year` is a string column because MySQL YEAR and PHP integer casts
            // disagree at the edges; validated as a 4-digit year on the way in.
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        return array_key_exists('plate_number', $attributes)
            ? ['plate_number' => strtoupper(trim((string) $attributes['plate_number']))]
            : [];
    }

    protected static function booted(): void
    {
        static::saving(function (self $truck): void {
            foreach (static::normalise($truck->getAttributes()) as $column => $value) {
                $truck->setAttribute($column, $value);
            }
        });
    }

    /** @return BelongsTo<Carrier, $this> */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /** @return HasMany<Driver, $this> */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
}
