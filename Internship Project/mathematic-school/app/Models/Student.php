<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_id',
        'full_name',
        'first_name',
        'last_name',
        'date_of_birth',
        'age',
        'gender',
        'phone',
        'address',
        'photo',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'age' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentGuardian::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relation');
    }

    public function getGuardiansAttribute()
    {
        return $this->parents;
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('status', 'completion_percentage', 'enrolled_at');
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function assignmentSubmissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }
}
