<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'branch_id',
        'warehouse_id',
        'user_id',
        'shipment_number',
        'type',
        'status',
        'priority',
        'sender_name',
        'sender_phone',
        'sender_address',
        'sender_city',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'recipient_city',
        'total_weight',
        'total_volume',
        'total_packages',
        'total_freight_charge',
        'total_insurance',
        'total_tax',
        'grand_total',
        'notes',
        'pickup_date',
        'pickup_time',
        'delivery_date',
        'delivery_time',
        'signed_by',
        'signature_path',
        'is_paid',
        'is_invoiced',
    ];

    protected function casts(): array
    {
        return [
            'total_weight' => 'decimal:2',
            'total_volume' => 'decimal:2',
            'total_freight_charge' => 'decimal:2',
            'total_insurance' => 'decimal:2',
            'total_tax' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'pickup_date' => 'datetime',
            'delivery_date' => 'datetime',
            'is_paid' => 'boolean',
            'is_invoiced' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function shipmentStatusHistories(): HasMany
    {
        return $this->hasMany(ShipmentStatusHistory::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function trackingHistories(): HasMany
    {
        return $this->hasMany(TrackingHistory::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function pickupRequests(): HasMany
    {
        return $this->hasMany(PickupRequest::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(Returns::class);
    }
}
