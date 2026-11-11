<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessInvoice extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'customer_id', 'order_id', 'invoice_number', 'subtotal', 'tax',
        'discount', 'total', 'currency', 'status', 'issue_date', 'due_date',
        'paid_at', 'line_items', 'is_branded',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'tax' => 'integer',
        'discount' => 'integer',
        'total' => 'integer',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'line_items' => 'array',
        'is_branded' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
