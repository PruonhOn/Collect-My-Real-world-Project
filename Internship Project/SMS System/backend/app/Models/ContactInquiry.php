<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'phone', 'subject', 'message', 'status', 'responded_at'])]
class ContactInquiry extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }
}
