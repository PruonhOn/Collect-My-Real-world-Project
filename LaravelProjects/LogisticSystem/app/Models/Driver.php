<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'license_number',
        'license_type',
        'license_expiry',
        'date_of_birth',
        'national_id',
        'address',
        'emergency_contact',
        'emergency_phone',
        'status',
        'hire_date',
        'salary',
        'photo',
        'is_active',
        'branch_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'assigned_driver_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
