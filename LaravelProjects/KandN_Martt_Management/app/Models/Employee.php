<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'position',
        'department',
        'hire_date',
        'salary',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
            'hire_date' => 'date',
            'status' => 'string',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
