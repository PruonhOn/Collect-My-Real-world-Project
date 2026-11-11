<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'plate_number',
        'make',
        'model',
        'year',
        'color',
        'capacity_weight',
        'capacity_volume',
        'fuel_type',
        'fuel_consumption',
        'insurance_number',
        'insurance_expiry',
        'registration_number',
        'registration_expiry',
        'status',
        'ownership',
        'purchase_price',
        'purchase_date',
        'is_active',
        'vehicle_type_id',
        'branch_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'capacity_weight' => 'decimal:2',
            'capacity_volume' => 'decimal:2',
            'fuel_consumption' => 'decimal:2',
            'insurance_expiry' => 'date',
            'registration_expiry' => 'date',
            'purchase_price' => 'decimal:2',
            'purchase_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vehicleMaintenances(): HasMany
    {
        return $this->hasMany(VehicleMaintenance::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'assigned_vehicle_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
