<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'description', 'price', 'duration_days', 'duration_hours', 'is_test', 'is_active'])]
class VipPlan extends Model
{
    use HasFactory;

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'duration_hours' => 'integer',
        'is_test' => 'boolean',
        'is_active' => 'boolean',
    ];
}
