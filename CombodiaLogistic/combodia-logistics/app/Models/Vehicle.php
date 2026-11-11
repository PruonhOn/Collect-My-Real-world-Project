<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'vehicle_type_id',
        'code',
        'plate_number',
        'brand',
        'model',
        'year',
        'color',
        'capacity_weight',
        'capacity_volume',
        'fuel_type',
        'status',
        'insurance_expiry',
        'registration_expiry',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'capacity_weight' => 'decimal:2',
            'capacity_volume' => 'decimal:2',
            'insurance_expiry' => 'datetime',
            'registration_expiry' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function vehicleMaintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class, 'assigned_vehicle_id');
    }
}
