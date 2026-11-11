<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'lesson_id',
        'enrollment_id',
        'is_completed',
        'completed_at',
        'learning_time_seconds',
        'last_activity_at',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'learning_time_seconds' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
