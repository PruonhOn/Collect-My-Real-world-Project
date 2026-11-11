<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Transferred = 'transferred';
    case Graduated = 'graduated';
    case Dropped = 'dropped';
}
