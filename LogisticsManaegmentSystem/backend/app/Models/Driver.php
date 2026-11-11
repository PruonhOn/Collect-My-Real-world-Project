<?php

namespace App\Models;

use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A driver.
 *
 * Expiry dates are real date columns rather than notes, because "licences
 * expiring in the next 30 days" is a report the operations team runs.
 */
#[Fillable([
    'code',
    'name',
    'license_number',
    'phone',
    'email',
    'nationality',
    'carrier_id',
    'truck_id',
    'license_expiry',
    'medical_expiry',
    'is_active',
    'notes',
])]
class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'medical_expiry' => 'date',
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

        if (array_key_exists('code', $attributes)) {
            $normalised['code'] = strtoupper(trim((string) $attributes['code']));
        }

        if (array_key_exists('nationality', $attributes)) {
            $normalised['nationality'] = strtoupper(trim((string) $attributes['nationality']));
        }

        return $normalised;
    }

    protected static function booted(): void
    {
        static::saving(function (self $driver): void {
            foreach (static::normalise($driver->getAttributes()) as $column => $value) {
                $driver->setAttribute($column, $value);
            }
        });
    }

    /**
     * Drivers whose licence or medical certificate has lapsed or is about to.
     *
     * @param  Builder<self>  $query
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $cutoff = now()->addDays($days)->toDateString();

        $query->where(function (Builder $self) use ($cutoff): void {
            $self->whereDate('license_expiry', '<=', $cutoff)
                ->orWhereDate('medical_expiry', '<=', $cutoff);
        });
    }

    /** @return BelongsTo<Carrier, $this> */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    /** @return BelongsTo<Truck, $this> */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }
}
