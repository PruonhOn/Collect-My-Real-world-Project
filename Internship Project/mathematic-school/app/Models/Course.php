<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'course_code',
        'slug',
        'description',
        'thumbnail',
        'age_group',
        'level',
        'duration',
        'price',
        'requirements',
        'objectives',
        'status',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'course_teacher');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function publishedLessons()
    {
        return $this->lessons()->where('is_published', true);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments')
            ->withPivot('status', 'completion_percentage', 'enrolled_at');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
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

    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1);
    }

    public function getEnrolledCountAttribute()
    {
        return $this->enrollments()->count();
    }
}
