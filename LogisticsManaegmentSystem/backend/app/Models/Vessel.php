<?php

namespace App\Models;

use Database\Factories\VesselFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A ship.
 *
 * IMO number is the only stable identity: names get renamed, flags get reflagged
 * and classes get re-certified, but an IMO number stays with the hull for life.
 */
#[Fillable([
    'name',
    'imo_number',
    'mmsi',
    'flag_country',
    'vessel_type',
    'length_meters',
    'beam_meters',
    'capacity_teu',
    'is_active',
    'notes',
])]
class Vessel extends Model
{
    /** @use HasFactory<VesselFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'length_meters' => 'integer',
            'beam_meters' => 'integer',
            'capacity_teu' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * IMO numbers and MMSIs are digit strings with no case, but a client will
     * happily send either with surrounding whitespace, which the unique index
     * would happily treat as a different vessel. See Party::normalise() for why
     * this also runs before validation.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        foreach (['imo_number', 'mmsi'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $normalised[$column] = trim((string) $attributes[$column]);
            }
        }

        if (array_key_exists('flag_country', $attributes)) {
            $normalised['flag_country'] = strtoupper(trim((string) $attributes['flag_country']));
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        static::saving(function (self $vessel): void {
            foreach (static::normalise($vessel->getAttributes()) as $column => $value) {
                $vessel->setAttribute($column, $value);
            }
        });
    }
}
