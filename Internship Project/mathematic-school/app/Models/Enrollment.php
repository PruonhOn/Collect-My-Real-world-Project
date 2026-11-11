<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'course_id',
        'enrolled_at',
        'status',
        'completion_percentage',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completion_percentage' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function getTotalLessonsAttribute()
    {
        return $this->course->publishedLessons()->count();
    }

    public function getCompletedLessonsAttribute()
    {
        return $this->lessonProgress()->where('is_completed', true)->count();
    }
}
