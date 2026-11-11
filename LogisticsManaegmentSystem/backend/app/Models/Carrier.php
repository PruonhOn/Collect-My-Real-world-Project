<?php

namespace App\Models;

use App\Enums\CarrierType;
use Database\Factories\CarrierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A transport provider we buy capacity from.
 *
 * One carrier serves many modes, so `type` records the mode a given booking
 * should assume rather than limiting the carrier to a single one.
 */
#[Fillable([
    'code',
    'name',
    'scac',
    'type',
    'contact_name',
    'contact_email',
    'contact_phone',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country_code',
    'is_active',
    'notes',
])]
class Carrier extends Model
{
    /** @use HasFactory<CarrierFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CarrierType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Canonicalise the codes this entity is identified by. See Party::normalise()
     * for why this runs before validation as well as on save.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        foreach (['code', 'scac', 'country_code'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $normalised[$column] = strtoupper(trim((string) $attributes[$column]));
            }
        }

        return $normalised;
    }

    /**
     * SCAC is defined as four upper-case letters; carriers quote it in every
     * casing imaginable and the field is unique.
     */
    protected static function booted(): void
    {
        static::saving(function (self $carrier): void {
            foreach (static::normalise($carrier->getAttributes()) as $column => $value) {
                $carrier->setAttribute($column, $value);
            }
        });
    }

    /** @return HasMany<Truck, $this> */
    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }

    /** @return HasMany<Driver, $this> */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
}
