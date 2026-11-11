<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'student_code', 'department_id', 'program_id', 'year'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function getAttendanceRateAttribute(): float
    {
        $total = $this->attendanceRecords()->count();

        if ($total === 0) {
            return 0.0;
        }

        $present = $this->attendanceRecords()
            ->where('status', \App\Enums\AttendanceStatus::Present)
            ->count();

        return round(($present / $total) * 100, 2);
    }
}
