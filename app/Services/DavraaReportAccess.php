<?php

namespace App\Services;

use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who may manage / view DAVRAA Reports, scoped exactly like
 * `DataIntegrityAccess`: Admin is meet-wide; a Tournament ICT is limited
 * to the sport(s) they are assigned to for the meet (a central ICT
 * management-team seat is meet-wide). Event Secretariat gets view-only.
 */
class DavraaReportAccess
{
    /** Null = every sport in the meet; [] = no access; list = these sport ids. */
    public function sportIds(User $user, int $meetId): ?array
    {
        if ($user->isAdmin()) {
            return null;
        }

        if ($user->role === UserRole::Coach) {
            return [];
        }

        $centralIct = $user->managementTeamMemberships()
            ->where('status', 'active')
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('meet_id', $meetId)
                ->where(fn ($ict) => $ict
                    ->where('team_type', ManagementTeamType::ICT->value)
                    ->orWhereIn('source_code', ['ICT', 'CENTRAL_ICT'])))
            ->exists();

        if ($centralIct) {
            return null;
        }

        return $user->meetSportAssignments()
            ->where('status', 'active')
            ->where('role', 'tournament_ict')
            ->whereHas('meetSport', fn ($scope) => $scope->where('meet_id', $meetId))
            ->with('meetSport')
            ->get()
            ->pluck('meetSport.sport_id')
            ->filter()
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function canManage(User $user, int $meetId, ?int $sportId = null): bool
    {
        $sports = $this->sportIds($user, $meetId);

        if ($sports === null) {
            return true;
        }

        if ($sports === []) {
            return false;
        }

        return $sportId === null || in_array($sportId, $sports, true);
    }

    public function isEventSecretariat(User $user, int $meetId): bool
    {
        return $user->managementTeamMemberships()
            ->where('status', 'active')
            ->whereHas('managementTeam', fn ($team) => $team
                ->where('meet_id', $meetId)
                ->where('source_code', 'EVENT_SECRETARIAT'))
            ->exists();
    }

    /**
     * View / print / export — the sport's own ICT (or meet-wide Admin /
     * central ICT), plus the meet's Event Secretariat for any sport.
     */
    public function canView(User $user, int $meetId, ?int $sportId = null): bool
    {
        return $this->canManage($user, $meetId, $sportId)
            || $this->isEventSecretariat($user, $meetId);
    }

    /** Can this user open the module at all (see the list)? */
    public function canBrowse(User $user, int $meetId): bool
    {
        return $this->sportIds($user, $meetId) !== []
            || $this->isEventSecretariat($user, $meetId);
    }

    /** The sports this user may build DAVRAA groups for, for the picker. */
    public function sportOptions(User $user, int $meetId): Collection
    {
        $ids = $this->sportIds($user, $meetId);

        return Sport::query()
            ->when($ids !== null, fn ($query) => $query->whereKey($ids ?? []))
            ->whereHas('meetSports', fn ($ms) => $ms->where('meet_id', $meetId))
            ->orderBy('name')
            ->get(['id', 'name', 'is_team_sport']);
    }
}
