<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'class_section_id', 'status'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function attendance()
    {
        return $this->student->attendanceRecords()
            ->whereHas('attendanceSession', fn ($q) => $q->where('class_section_id', $this->class_section_id))
            ->with('attendanceSession');
    }
}
