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
    case Foul = 'foul';
    case RoundScore = 'round_score';
    case InningRun = 'inning_run';
    case Count = 'count';
    case Possession = 'possession';
    case Substitution = 'substitution';
    case Horn = 'horn';
    case Bell = 'bell';

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
            self::Foul => 'Foul',
            self::RoundScore => 'Round score',
            self::InningRun => 'Inning run',
            self::Count => 'Count',
            self::Possession => 'Possession',
            self::Substitution => 'Substitution',
            self::Horn => 'Horn',
            self::Bell => 'Bell',
        };
    }
}
