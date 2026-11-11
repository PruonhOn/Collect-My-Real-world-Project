<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'payment_id', 'invoice_number', 'type', 'subtotal', 'tax',
        'discount', 'total', 'currency', 'status', 'issue_date', 'due_date',
        'paid_at', 'line_items', 'billing_details',
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
        'billing_details' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
