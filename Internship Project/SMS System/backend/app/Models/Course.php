<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Enums\CourseType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['academic_year_id', 'category_id', 'subject_id', 'teacher_id', 'name', 'code', 'description', 'type', 'fee', 'capacity', 'status', 'featured', 'is_published', 'start_date', 'end_date'])]
class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => CourseType::class,
            'status' => CourseStatus::class,
            'fee' => 'decimal:2',
            'capacity' => 'integer',
            'featured' => 'boolean',
            'is_published' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'course_enrollments')
            ->withPivot('enrolled_on')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * True when the course has no enrollment capacity limit or seats remain.
     */
    public function hasCapacity(): bool
    {
        if ($this->capacity <= 0) {
            return true;
        }

        return $this->enrollments_count < $this->capacity;
    }

    /**
     * Restrict a query to courses taught by the given teacher.
     */
    public function scopeTaughtBy(Builder $query, Teacher $teacher): Builder
    {
        return $query->where('teacher_id', $teacher->getKey());
    }
}
