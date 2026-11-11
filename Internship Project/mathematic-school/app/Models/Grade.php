<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'quiz_id',
        'assignment_id',
        'score',
        'max_score',
        'type',
        'note',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function getPercentageAttribute()
    {
        if (!$this->max_score) {
            return 0;
        }
        return round(($this->score / $this->max_score) * 100, 2);
    }
}
