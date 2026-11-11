<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'description',
        'time_limit',
        'passing_score',
        'allow_retry',
        'is_published',
    ];

    protected $casts = [
        'allow_retry' => 'boolean',
        'is_published' => 'boolean',
        'passing_score' => 'decimal:2',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function getTotalMarksAttribute()
    {
        return $this->questions()->sum('marks');
    }

    public function getQuestionCountAttribute()
    {
        return $this->questions()->count();
    }
}
