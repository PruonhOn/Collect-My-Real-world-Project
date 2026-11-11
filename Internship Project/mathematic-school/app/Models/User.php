<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'avatar',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher');
    }

    public function isParent(): bool
    {
        return $this->hasRole('parent');
    }

    public function isStudent(): bool
    {
        return $this->hasRole('student');
    }

    public function getRoleNameAttribute(): string
    {
        return $this->roles->first()?->name ?? $this->role;
    }

    public function profile()
    {
        return match ($this->role) {
            'teacher' => $this->teacherProfile(),
            'parent' => $this->parentProfile(),
            'student' => $this->studentProfile(),
            default => null,
        };
    }

    public function teacherProfile()
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentGuardian::class, 'user_id');
    }

    public function studentProfile()
    {
        return $this->hasOne(Student::class, 'user_id');
    }
}
