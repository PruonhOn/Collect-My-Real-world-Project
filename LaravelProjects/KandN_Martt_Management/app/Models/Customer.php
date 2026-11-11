<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'membership_card_no',
        'points',
        'total_purchases',
        'last_purchase_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'total_purchases' => 'decimal:2',
            'last_purchase_at' => 'datetime',
            'status' => 'string',
        ];
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
