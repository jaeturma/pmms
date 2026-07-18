<?php

namespace App\Enums;

enum EntryStatus: string
{
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Confirmed => 'Confirmed',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
