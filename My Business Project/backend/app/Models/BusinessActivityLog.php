<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessActivityLog extends Model
{
    protected $fillable = [
        'business_id', 'user_id', 'action', 'description', 'properties',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];
}
