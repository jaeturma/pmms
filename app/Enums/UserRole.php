<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Organizer = 'organizer';
    case DelegationOfficer = 'delegation_officer';
    case TechnicalOfficial = 'technical_official';
    case Coach = 'coach';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Organizer => 'Meet Organizer',
            self::DelegationOfficer => 'Delegation Officer',
            self::TechnicalOfficial => 'Technical Official',
            self::Coach => 'Coach',
            self::Viewer => 'Viewer',
        };
    }
}
