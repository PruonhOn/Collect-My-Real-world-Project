<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'department_id', 'employee_code'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function classSections(): HasMany
    {
        return $this->hasMany(ClassSection::class);
    }

    public function attendanceSessions(): HasManyThrough
    {
        return $this->hasManyThrough(AttendanceSession::class, ClassSection::class);
    }
}
