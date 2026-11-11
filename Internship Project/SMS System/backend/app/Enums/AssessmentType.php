<?php

namespace App\Enums;

enum AssessmentType: string
{
    case Quiz = 'quiz';
    case Assignment = 'assignment';
    case Midterm = 'midterm';
    case Final = 'final';
    case Other = 'other';
}
