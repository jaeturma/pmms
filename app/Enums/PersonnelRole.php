<?php

namespace App\Enums;

enum PersonnelRole: string
{
    case Coach = 'coach';
    case AssistantCoach = 'assistant_coach';
    case Chaperone = 'chaperone';
    case TeamDelegationOfficer = 'team_delegation_officer';
    case DistrictSportsCoordinator = 'district_sports_coordinator';
    case DelegationManager = 'delegation_manager';
    case MunicipalMayor = 'municipal_mayor';

    public function label(): string
    {
        return match ($this) {
            self::Coach => 'Coach',
            self::AssistantCoach => 'Assistant Coach',
            self::Chaperone => 'Chaperone',
            self::TeamDelegationOfficer => 'Team / Delegation Officer',
            self::DistrictSportsCoordinator => 'District Sports Coordinator',
            self::DelegationManager => 'Delegation Manager',
            self::MunicipalMayor => 'Municipal Mayor',
        };
    }

    /**
     * Only coaching roles carry sport assignments.
     */
    public function coaches(): bool
    {
        return $this === self::Coach || $this === self::AssistantCoach;
    }
}
