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
    case RallyPoint = 'rally_point';
    case SetComplete = 'set_complete';
    case Card = 'card';
    case GamePoint = 'game_point';
    case GameComplete = 'game_complete';
    case WrestlingPoint = 'wrestling_point';
    case Fall = 'fall';
    case TennisPoint = 'tennis_point';
    case TennisUndo = 'tennis_undo';
    case PenaltyThrow = 'penalty_throw';
    case RackComplete = 'rack_complete';
    case RackUndo = 'rack_undo';
    case EndComplete = 'end_complete';
    case EndUndo = 'end_undo';

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
            self::RallyPoint => 'Rally point',
            self::SetComplete => 'Set complete',
            self::Card => 'Card',
            self::GamePoint => 'Game point',
            self::GameComplete => 'Game complete',
            self::WrestlingPoint => 'Wrestling point',
            self::Fall => 'Fall',
            self::TennisPoint => 'Tennis point',
            self::TennisUndo => 'Tennis undo',
            self::PenaltyThrow => 'Penalty throw',
            self::RackComplete => 'Rack complete',
            self::RackUndo => 'Rack undone',
            self::EndComplete => 'End complete',
            self::EndUndo => 'End undone',
        };
    }
}
