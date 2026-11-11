<?php

namespace App\Enums;

enum DriverDocumentType: string
{
    case License = 'license';
    case IdCard = 'id_card';
    case Contract = 'contract';
    case Certification = 'certification';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::License => 'Driver License',
            self::IdCard => 'ID Card',
            self::Contract => 'Contract',
            self::Certification => 'Certification',
            self::Other => 'Other',
        };
    }
}
