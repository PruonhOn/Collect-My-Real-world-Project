<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'max_weight',
        'max_volume',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_weight' => 'decimal:2',
            'max_volume' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
