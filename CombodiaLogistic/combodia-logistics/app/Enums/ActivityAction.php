<?php

namespace App\Enums;

enum ActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Login = 'login';
    case Logout = 'logout';
    case Viewed = 'viewed';
    case Exported = 'exported';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Deleted => 'Deleted',
            self::Restored => 'Restored',
            self::Login => 'Login',
            self::Logout => 'Logout',
            self::Viewed => 'Viewed',
            self::Exported => 'Exported',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created => 'success',
            self::Updated => 'info',
            self::Deleted => 'danger',
            self::Restored => 'warning',
            self::Login => 'primary',
            self::Logout => 'secondary',
            self::Viewed => 'secondary',
            self::Exported => 'info',
        };
    }
}
