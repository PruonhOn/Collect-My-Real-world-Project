<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_section_id',
        'session_date',
        'start_time',
        'end_time',
        'qr_token',
        'grace_period_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SessionStatus::Active);
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->whereDate('session_date', $date);
    }
}
