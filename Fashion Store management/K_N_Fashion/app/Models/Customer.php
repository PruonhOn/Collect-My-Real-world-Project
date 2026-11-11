<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'loyalty_points',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
