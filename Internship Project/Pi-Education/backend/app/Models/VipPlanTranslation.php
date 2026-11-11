<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipPlanTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vip_plan_id',
        'locale',
        'name',
        'description',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(VipPlan::class, 'vip_plan_id');
    }
}
