<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['course_id', 'student_id', 'enrolled_on'])]
class CourseEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'enrolled_on' => 'date',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }
}
