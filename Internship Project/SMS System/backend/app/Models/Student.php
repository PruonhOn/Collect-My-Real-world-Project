<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'student_code', 'khmer_name', 'gender', 'birth_date', 'contact_phone', 'guardian_name', 'guardian_phone', 'address', 'status', 'enrolled_on'])]
class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'enrolled_on' => 'date',
            'gender' => Gender::class,
            'status' => StudentStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_parent', 'student_id', 'parent_id')
            ->withPivot('relationship', 'is_primary')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_enrollments')
            ->withPivot('enrolled_on')
            ->withTimestamps();
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(StudentFee::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
