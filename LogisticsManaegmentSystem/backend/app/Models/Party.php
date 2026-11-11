<?php

namespace App\Models;

use App\Enums\PartyType;
use Database\Factories\PartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A company the system trades with: a customer, a shipper or a consignee.
 *
 * One model behind three API surfaces. The surface is decided by `type`, which
 * is set by the route rather than by the request body - a client cannot create a
 * shipper by posting to /customers, because the type is not a fillable field it
 * controls.
 */
#[Fillable([
    'type',
    'code',
    'name',
    'contact_name',
    'contact_email',
    'contact_phone',
    'tax_number',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country_code',
    'is_active',
    'notes',
])]
class Party extends Model
{
    /** @use HasFactory<PartyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * `type` is fillable but is never reachable from a request body.
     *
     * The controller's validation rules do not list it, and `validate()` returns
     * only the keys it has rules for, so a posted `type` is dropped before it
     * reaches the model. What actually lands in the column is the value the
     * controller writes from the route. Making it fillable is what lets the
     * controller set it without `forceCreate`, which would disable mass-assignment
     * protection for every other column too.
     */
    public const TYPE = 'type';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            self::TYPE => PartyType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Canonicalise the codes this entity is identified by.
     *
     * Called on the request before validation AND on save, and both calls have to
     * exist. Validation alone is not enough: `unique` compares the payload against
     * what is stored, so normalising only on save means a client can post
     * `maeu` against a stored `MAEU`, sail past validation, and hit a raw
     * database integrity error - a 500 where the caller should get a 422.
     *
     * Only keys actually present are returned, so a partial update that omits
     * `code` still omits it here.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function normalise(array $attributes): array
    {
        $normalised = [];

        if (array_key_exists('code', $attributes)) {
            $normalised['code'] = strtoupper(trim((string) $attributes['code']));
        }

        if (array_key_exists('country_code', $attributes)) {
            $normalised['country_code'] = strtoupper(trim((string) $attributes['country_code']));
        }

        return $normalised;
    }

    /**
     * Guard the invariant for every write path, including factories, seeders and
     * console commands that never pass through a controller.
     */
    protected static function booted(): void
    {
        static::saving(function (self $party): void {
            foreach (static::normalise($party->getAttributes()) as $column => $value) {
                $party->setAttribute($column, $value);
            }
        });
    }

    /**
     * Restrict a query to one kind of party.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOfType(Builder $query, PartyType $type): void
    {
        $query->where(self::TYPE, $type->value);
    }

    public function typeLabel(): string
    {
        return ucfirst($this->{self::TYPE}->value);
    }
}
