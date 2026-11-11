<?php

namespace App\Enums;

enum FeeStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Waived = 'waived';
    case Cancelled = 'cancelled';
}
