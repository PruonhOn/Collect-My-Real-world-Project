<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Returns extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'returns';

    protected $fillable = [
        'shipment_id',
        'customer_id',
        'branch_id',
        'return_number',
        'type',
        'status',
        'reason',
        'description',
        'total_items',
        'total_refund_amount',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'total_items' => 'integer',
            'total_refund_amount' => 'decimal:2',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }
}
