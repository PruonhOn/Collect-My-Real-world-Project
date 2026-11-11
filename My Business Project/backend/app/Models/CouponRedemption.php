<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponRedemption extends Model
{
    protected $fillable = [
        'coupon_id', 'business_id', 'payment_id',
    ];
}
