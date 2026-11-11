<?php

namespace App\Models;

use App\Enums\SiteKind;
use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A physical site: a warehouse or a factory.
 *
 * Shared by both on purpose - see the `sites` migration for why. `kind` decides
 * the API surface and the permission that guards it; the factory-only columns are
 * simply left null for a warehouse.
 */
#[Fillable([
    'kind',
    'code',
    'name',
    'contact_name',
    'contact_email',
    'contact_phone',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country_code',
    'site_type',
    'capacity_pallets',
    'is_active',
    'notes',
])]
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory, SoftDeletes;

    public const KIND = 'kind';

    /**
     * Like Party, `kind` is fillable but unreachable from a request body: it is
     * absent from the validation rules, so `validate()` drops any posted value
     * and the controller supplies the one the route dictates.
     */
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::KIND => SiteKind::class,
            'capacity_pallets' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        foreach (['code', 'country_code'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $normalised[$column] = strtoupper(trim((string) $attributes[$column]));
            }
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        static::saving(function (self $site): void {
            foreach (static::normalise($site->getAttributes()) as $column => $value) {
                $site->setAttribute($column, $value);
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOfKind(Builder $query, SiteKind $kind): void
    {
        $query->where(self::KIND, $kind->value);
    }
}
