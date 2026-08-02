<?php

namespace App\Enums;

enum ManagementTeamStatus: string
{
    case Forming = 'forming';
    case Active = 'active';
    case Disbanded = 'disbanded';

    public function label(): string
    {
        return match ($this) {
            self::Forming => 'Forming',
            self::Active => 'Active',
            self::Disbanded => 'Disbanded',
        };
    }
}
