<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Teacher => 'Teacher',
            self::Student => 'Student',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SuperAdmin => 'bg-red-100 text-red-800',
            self::Admin => 'bg-purple-100 text-purple-800',
            self::Teacher => 'bg-blue-100 text-blue-800',
            self::Student => 'bg-green-100 text-green-800',
        };
    }
}
