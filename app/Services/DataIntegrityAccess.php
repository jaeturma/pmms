<?php

namespace App\Services;

use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Models\User;

class DataIntegrityAccess
{
    /** Null means meet-wide access; an empty list grants no access. */
    public function sportIds(User $user, int $meetId): ?array
    {
        if ($user->isAdmin()) {
            return null;
        }
        // A Coach role never opens the integrity console, even with other memberships.
        if ($user->role === UserRole::Coach) {
            return [];
        }
        if ($user->managementTeamMemberships()->where('status', 'active')
            ->whereHas('managementTeam', fn ($team) => $team->where('meet_id', $meetId)
                ->where(fn ($ict) => $ict->where('team_type', ManagementTeamType::ICT->value)
                    ->orWhereIn('source_code', ['ICT', 'CENTRAL_ICT'])))->exists()) {
            return null;
        }

        return $user->meetSportAssignments()->where('status', 'active')->where('role', 'tournament_ict')
            ->whereHas('meetSport', fn ($scope) => $scope->where('meet_id', $meetId))
            ->with('meetSport')->get()->pluck('meetSport.sport_id')->filter()->unique()->values()->all();
    }

    public function allows(User $user, int $meetId, ?int $sportId = null): bool
    {
        $sports = $this->sportIds($user, $meetId);

        return $sports === null || ($sportId === null ? $sports !== [] : in_array($sportId, $sports, true));
    }
}
