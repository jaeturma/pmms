<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Organizer = 'organizer';
    case DelegationOfficer = 'delegation_officer';
    case TechnicalOfficial = 'technical_official';
    case TournamentManager = 'tournament_manager';
    case TournamentICT = 'tournament_ict';
    case TournamentSecretary = 'tournament_secretary';
    case Coach = 'coach';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Organizer => 'Meet Organizer',
            self::DelegationOfficer => 'Delegation Officer',
            self::TechnicalOfficial => 'Technical Official',
            self::TournamentManager => 'Tournament Manager',
            self::TournamentICT => 'Tournament ICT',
            self::TournamentSecretary => 'Tournament Secretary',
            self::Coach => 'Coach',
            self::Viewer => 'Viewer',
        };
    }
}
