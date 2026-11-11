<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSection extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'teacher_id', 'term_id', 'name', 'room', 'schedule_json'];

    protected function casts(): array
    {
        return [
            'schedule_json' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }

    public function getSectionNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function attendanceRecords(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(AttendanceRecord::class, AttendanceSession::class);
    }

    public function enrolledStudents(): Collection
    {
        return Student::whereIn(
            'id',
            $this->enrollments()
                ->where('status', \App\Enums\EnrollmentStatus::Active)
                ->pluck('student_id')
        )->get();
    }
}
