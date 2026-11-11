<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'loyalty_points',
    ];

    protected $casts = [
        'loyalty_points' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getTotalSpentAttribute(): float
    {
        return (float) $this->orders()
            ->whereIn('status', ['completed', 'partially_refunded'])
            ->sum('total');
    }
}
