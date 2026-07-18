<?php

namespace App\Enums;

enum SchoolLevel: string
{
    case Elementary = 'elementary';
    case Secondary = 'secondary';
    case Integrated = 'integrated';

    public function label(): string
    {
        return match ($this) {
            self::Elementary => 'Elementary',
            self::Secondary => 'Secondary',
            self::Integrated => 'Integrated',
        };
    }
}
