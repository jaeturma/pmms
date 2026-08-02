<?php

namespace App\Enums;

/**
 * Personnel roles a person can hold against one `MeetSport` (a sport's
 * inclusion in one specific meet), per the approved DdOPAA organizational
 * model §9-12 — see
 * docs/reports/architecture/pmms-organizational-realignment-gap-assessment.md.
 * `TechnicalOfficial` here is deliberately distinct from the existing
 * `App\Enums\UserRole::TechnicalOfficial` login role: that enum is the
 * account's base access level, this one is which specific duty an
 * assignment row records — the same person's `UserRole` stays
 * `technical_official` regardless of which of these roles their
 * individual assignments carry.
 */
enum MeetSportAssignmentRole: string
{
    case TournamentManager = 'tournament_manager';
    case AssistantTournamentManager = 'assistant_tournament_manager';
    case TrackTournamentManager = 'track_tournament_manager';
    case FieldTournamentManager = 'field_tournament_manager';
    case BoysTournamentManager = 'boys_tournament_manager';
    case GirlsTournamentManager = 'girls_tournament_manager';
    case CategoryTournamentManager = 'category_tournament_manager';
    case TournamentSecretary = 'tournament_secretary';
    case TournamentICT = 'tournament_ict';
    case TechnicalOfficial = 'technical_official';

    public function label(): string
    {
        return match ($this) {
            self::TournamentManager => 'Tournament Manager',
            self::AssistantTournamentManager => 'Assistant Tournament Manager',
            self::TrackTournamentManager => 'Track Tournament Manager',
            self::FieldTournamentManager => 'Field Tournament Manager',
            self::BoysTournamentManager => 'Boys Tournament Manager',
            self::GirlsTournamentManager => 'Girls Tournament Manager',
            self::CategoryTournamentManager => 'Category Tournament Manager',
            self::TournamentSecretary => 'Tournament Secretary',
            self::TournamentICT => 'Tournament ICT',
            self::TechnicalOfficial => 'Technical Official',
        };
    }
}
