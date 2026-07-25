<?php

namespace App\Enums;

enum ScoreEventType: string
{
    case Point = 'point';
    case Correction = 'correction';
    case PeriodChange = 'period_change';
    case Note = 'note';
    case Paused = 'paused';
    case Resumed = 'resumed';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Point => 'Point',
            self::Correction => 'Correction',
            self::PeriodChange => 'Period change',
            self::Note => 'Note',
            self::Paused => 'Paused',
            self::Resumed => 'Resumed',
            self::Ended => 'Ended',
        };
    }
}
