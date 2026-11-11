<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleMaintenance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_no',
        'maintenance_type',
        'description',
        'maintenance_date',
        'next_maintenance_date',
        'cost',
        'vendor',
        'status',
        'notes',
        'vehicle_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date',
            'next_maintenance_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
