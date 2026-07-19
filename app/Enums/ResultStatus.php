<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Encoded = 'encoded';
    case Validated = 'validated';

    public function label(): string
    {
        return match ($this) {
            self::Encoded => 'Encoded',
            self::Validated => 'Validated',
        };
    }
}
