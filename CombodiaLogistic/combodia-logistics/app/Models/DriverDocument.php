<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'type',
        'document_number',
        'file_path',
        'issue_date',
        'expiry_date',
        'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'datetime',
            'expiry_date' => 'datetime',
            'is_verified' => 'boolean',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
