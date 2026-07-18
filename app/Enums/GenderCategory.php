<?php

namespace App\Enums;

enum GenderCategory: string
{
    case Boys = 'boys';
    case Girls = 'girls';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Boys => 'Boys',
            self::Girls => 'Girls',
            self::Mixed => 'Mixed',
        };
    }

    public function accepts(Sex $sex): bool
    {
        return match ($this) {
            self::Mixed => true,
            self::Boys => $sex === Sex::Male,
            self::Girls => $sex === Sex::Female,
        };
    }
}
