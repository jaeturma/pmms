<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * One definition of "does this user operate this sport" — a Technical
 * Official via the many-to-many `sport_user` pivot (`User::sports()`), a
 * Tournament Manager via either the legacy 1:1 sport FK or an active,
 * meet-scoped Tournament/Assistant Tournament Manager assignment.
 * Extracted once this check reached three call sites (matching this
 * project's own `SearchesAndPaginates` extraction threshold).
 */
trait ScopesToAssignedSport
{
    protected function userOperatesSport(User $user, int $sportId): bool
    {
        if ($user->hasRole(UserRole::TechnicalOfficial, UserRole::TournamentICT, UserRole::TournamentSecretary)
            && ($this->userAssignedSportIds($user, [
                MeetSportAssignmentRole::TechnicalOfficial,
                MeetSportAssignmentRole::TournamentSecretary,
                MeetSportAssignmentRole::TournamentICT,
            ])->contains($sportId) || $user->sports()->whereKey($sportId)->exists())) {
            return true;
        }

        return $user->hasRole(UserRole::TournamentManager)
            && $this->userManagedSportIds($user)->contains($sportId);
    }

    /**
     * @return Collection<int, int>
     */
    protected function userManagedSportIds(User $user): Collection
    {
        $assigned = $this->userAssignedSportIds($user, [
            MeetSportAssignmentRole::TournamentManager,
            MeetSportAssignmentRole::AssistantTournamentManager,
            MeetSportAssignmentRole::TrackTournamentManager,
            MeetSportAssignmentRole::FieldTournamentManager,
            MeetSportAssignmentRole::BoysTournamentManager,
            MeetSportAssignmentRole::GirlsTournamentManager,
            MeetSportAssignmentRole::CategoryTournamentManager,
        ]);

        if ($user->managedSport !== null) {
            $assigned->push($user->managedSport->id);
        }

        return $assigned->filter()->map(fn ($id): int => (int) $id)->unique()->values();
    }

    /**
     * @param  list<MeetSportAssignmentRole>  $roles
     * @return Collection<int, int>
     */
    protected function userAssignedSportIds(User $user, array $roles): Collection
    {
        return $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active)
            ->whereIn('role', $roles)
            ->whereHas('meetSport.meet', fn ($query) => $query->where('id', Meet::current()->id))
            ->with('meetSport:id,sport_id')
            ->get()
            ->pluck('meetSport.sport_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }
}
