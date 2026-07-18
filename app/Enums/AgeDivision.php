<?php

namespace App\Enums;

enum AgeDivision: string
{
    case Elementary = 'elementary';
    case Secondary = 'secondary';

    public function label(): string
    {
        return match ($this) {
            self::Elementary => 'Elementary',
            self::Secondary => 'Secondary',
        };
    }
}
