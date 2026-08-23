<?php

namespace App\Enums;

enum AthleteOversightType: string
{
    case DistrictSportsCoordinator = 'district_sports_coordinator';
    case MunicipalityTeamManager = 'municipality_team_manager';

    public function label(): string
    {
        return match ($this) {
            self::DistrictSportsCoordinator => 'District Sports Coordinator',
            self::MunicipalityTeamManager => 'Municipality / Team Manager',
        };
    }
}
