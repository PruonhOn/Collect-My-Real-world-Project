<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tracking_number',
        'shipment_type',
        'status',
        'origin_address',
        'origin_city',
        'origin_province',
        'destination_address',
        'destination_city',
        'destination_province',
        'shipper_name',
        'shipper_phone',
        'shipper_email',
        'consignee_name',
        'consignee_phone',
        'consignee_email',
        'weight',
        'volume',
        'pieces',
        'description',
        'declared_value',
        'currency',
        'shipment_date',
        'estimated_delivery',
        'actual_delivery',
        'delivery_proof',
        'is_express',
        'is_insured',
        'insurance_amount',
        'notes',
        'customer_id',
        'origin_branch_id',
        'destination_branch_id',
        'assigned_driver_id',
        'assigned_vehicle_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'volume' => 'decimal:2',
            'pieces' => 'integer',
            'declared_value' => 'decimal:2',
            'shipment_date' => 'date',
            'estimated_delivery' => 'date',
            'actual_delivery' => 'date',
            'is_express' => 'boolean',
            'is_insured' => 'boolean',
            'insurance_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function originBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function assignedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shipmentStatuses(): HasMany
    {
        return $this->hasMany(ShipmentStatus::class);
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function shipmentDocuments(): HasMany
    {
        return $this->hasMany(ShipmentDocument::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class); 
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
