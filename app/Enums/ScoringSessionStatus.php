<?php

namespace App\Enums;

enum ScoringSessionStatus: string
{
    case InProgress = 'in_progress';
    case Paused = 'paused';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Paused => 'Paused',
            self::Ended => 'Ended',
        };
    }
}
