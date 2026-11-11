<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'parent_id',
        'course_id',
        'enrollment_id',
        'amount',
        'method',
        'transaction_id',
        'status',
        'paid_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_date' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function parentGuardian()
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
