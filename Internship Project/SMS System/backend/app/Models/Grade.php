<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['enrollment_id', 'letter_grade', 'points', 'credit_hours', 'remarks'])]
class Grade extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'points' => 'decimal:2',
            'credit_hours' => 'decimal:2',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(CourseEnrollment::class);
    }
}
