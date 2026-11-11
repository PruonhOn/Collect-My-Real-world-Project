<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'address', 'phone', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
