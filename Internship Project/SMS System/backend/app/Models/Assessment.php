<?php

namespace App\Models;

use App\Enums\AssessmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'title', 'type', 'max_score', 'weight', 'date'])]
class Assessment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => AssessmentType::class,
            'max_score' => 'decimal:2',
            'weight' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}
