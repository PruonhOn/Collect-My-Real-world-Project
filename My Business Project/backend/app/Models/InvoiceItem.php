<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'description', 'quantity', 'unit_amount', 'amount', 'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_amount' => 'integer',
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
