<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'vehicle_id',
        'code',
        'name',
        'email',
        'phone',
        'alternate_phone',
        'license_number',
        'license_expiry',
        'date_of_birth',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'datetime',
            'date_of_birth' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driverDocuments(): HasMany
    {
        return $this->hasMany(DriverDocument::class);
    }

    public function driverSchedules(): HasMany
    {
        return $this->hasMany(DriverSchedule::class);
    }

    public function dispatchDrivers(): HasMany
    {
        return $this->hasMany(DispatchDriver::class);
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class, 'assigned_driver_id');
    }

    public function vehicleMaintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }
}
