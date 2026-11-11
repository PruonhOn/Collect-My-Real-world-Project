<?php

namespace App\Models;

use Database\Factories\PortFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A port, terminal or inland load/discharge point.
 *
 * Identified by UN/LOCODE, which is the key every carrier quotation, bill of
 * lading and customs declaration uses. Geocoordinates are stored because
 * distance and route cost are calculated from them.
 */
#[Fillable([
    'un_locode',
    'name',
    'country_code',
    'port_type',
    'latitude',
    'longitude',
    'timezone',
    'is_active',
    'notes',
])]
class Port extends Model
{
    /** @use HasFactory<PortFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast to float so the JSON envelope emits 1.2644 rather than the
            // "1.2644" string a raw decimal column would otherwise produce.
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
        ];
    }

    /**
     * UN/LOCODE and ISO country codes are case-insensitive by definition.
     * See Party::normalise() for why this also runs before validation.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        foreach (['un_locode', 'country_code'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $normalised[$column] = strtoupper(trim((string) $attributes[$column]));
            }
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        static::saving(function (self $port): void {
            foreach (static::normalise($port->getAttributes()) as $column => $value) {
                $port->setAttribute($column, $value);
            }
        });
    }
}
