<?php

namespace App\Services;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\MeetSportAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Resolves the competition records visible to sport-assigned tournament
 * personnel. A null assignment category grants the whole sport; otherwise
 * only events in that configured category are included.
 */
class CompetitionAccessService
{
    /** @return list<string> */
    public function tournamentRoles(): array
    {
        return collect([
            MeetSportAssignmentRole::TournamentManager,
            MeetSportAssignmentRole::AssistantTournamentManager,
            MeetSportAssignmentRole::TrackTournamentManager,
            MeetSportAssignmentRole::FieldTournamentManager,
            MeetSportAssignmentRole::BoysTournamentManager,
            MeetSportAssignmentRole::GirlsTournamentManager,
            MeetSportAssignmentRole::CategoryTournamentManager,
            MeetSportAssignmentRole::TournamentSecretary,
            MeetSportAssignmentRole::TournamentICT,
            MeetSportAssignmentRole::TechnicalOfficial,
        ])->map(fn (MeetSportAssignmentRole $role): string => $role->value)->all();
    }

    /** @return Collection<int, MeetSportAssignment> */
    public function assignments(User $user, ?int $meetId = null): Collection
    {
        return $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active->value)
            ->whereIn('role', $this->tournamentRoles())
            ->when($meetId !== null, fn ($query) => $query
                ->whereHas('meetSport', fn ($meetSport) => $meetSport->where('meet_id', $meetId)))
            ->with(['meetSport:id,meet_id,sport_id', 'meetSport.sport:id,name', 'sportCategory:id,display_name'])
            ->get();
    }

    /** @return Collection<int, int> */
    public function eventIds(User $user, ?int $meetId = null): Collection
    {
        $assignments = $this->assignments($user, $meetId);
        $events = Event::query()->where(function (Builder $scope) use ($assignments, $user): void {
            foreach ($assignments as $assignment) {
                $scope->orWhere(function (Builder $event) use ($assignment): void {
                    $event->where('sport_id', $assignment->meetSport->sport_id);
                    if ($assignment->sport_category_id !== null) {
                        $event->where('sport_category_id', $assignment->sport_category_id);
                    }
                });
            }

            // Compatibility for legacy whole-sport TM/TO assignments.
            if ($user->role === UserRole::TechnicalOfficial) {
                $scope->orWhereIn('sport_id', $user->sports()->pluck('sports.id'));
            } elseif ($user->role === UserRole::TournamentManager && $user->managedSport !== null) {
                $scope->orWhere('sport_id', $user->managedSport->id);
            }

            if ($assignments->isEmpty()
                && $user->role !== UserRole::TechnicalOfficial
                && ! ($user->role === UserRole::TournamentManager && $user->managedSport !== null)) {
                $scope->whereRaw('1 = 0');
            }
        })->when($meetId !== null, fn ($query) => $query
            ->whereHas('meets', fn ($meets) => $meets->whereKey($meetId)));

        return $events->distinct()->pluck('events.id');
    }

    public function canAccessEvent(User $user, Event $event, ?int $meetId = null): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $this->eventIds($user, $meetId)->contains($event->id);
    }

    /** @return Collection<int, string> */
    public function labels(User $user): Collection
    {
        return $this->assignments($user)->map(function (MeetSportAssignment $assignment): string {
            $sport = $assignment->meetSport->sport->name;

            return $assignment->sportCategory === null
                ? $sport
                : $sport.' — '.$assignment->sportCategory->display_name;
        })->unique()->values();
    }
}
