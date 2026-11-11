<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImpersonationLog extends Model
{
    protected $fillable = [
        'impersonator_id', 'impersonated_id', 'business_id', 'reason',
        'started_at', 'ended_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}
