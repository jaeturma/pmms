<?php

namespace App\Services;

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
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
    public function scopeAthletes(Builder $query, User $user, ?int $meetId = null): Builder
    {
        $eventIds = $this->eventIds($user, $meetId);
        $sportIds = $this->sportIds($user, $meetId);
        [$meetIds, $coachIds] = $this->assignedCoachScope($user, $meetId, $eventIds, $sportIds);

        return $query->where(fn (Builder $athletes): Builder => $athletes
            ->whereHas(
                'entries',
                fn (Builder $entries): Builder => $entries->whereIn('event_id', $eventIds),
            )
            ->orWhereHas(
                'sportRosterMemberships.meetSport',
                fn (Builder $meetSports): Builder => $meetSports
                    ->whereIn('sport_id', $sportIds)
                    ->when($meetId !== null, fn (Builder $scope): Builder => $scope->where('meet_id', $meetId)),
            )
            ->orWhere(fn (Builder $registered): Builder => $registered
                ->whereIn('registered_by', $coachIds)
                ->whereHas('delegation', fn (Builder $delegations): Builder => $delegations->whereIn('meet_id', $meetIds))));
    }

    /** @return array{Collection<int, int>, Collection<int, int>} */
    private function assignedCoachScope(User $user, ?int $meetId, Collection $eventIds, Collection $sportIds): array
    {
        $meetIds = $meetId === null
            ? $this->assignments($user)->pluck('meetSport.meet_id')->filter()->unique()->values()
            : collect([$meetId]);
        $coachIds = User::query()
            ->where('role', UserRole::Coach->value)
            ->where(function (Builder $coaches) use ($eventIds, $sportIds, $meetIds): void {
                $coaches->whereHas('coachAssignmentRequests', fn (Builder $assignments) => $assignments
                    ->where('status', 'approved')
                    ->whereNull('ended_at')
                    ->where(function (Builder $scope) use ($eventIds, $sportIds, $meetIds): void {
                        $scope->whereIn('event_id', $eventIds)
                            ->orWhereHas('meetSport', fn (Builder $meetSports) => $meetSports
                                ->whereIn('meet_id', $meetIds)
                                ->whereIn('sport_id', $sportIds));
                    }))
                    ->orWhereHas('coachOnboardingRequest', fn (Builder $onboarding) => $onboarding
                        ->where('status', 'approved')
                        ->whereHas('events', fn (Builder $events) => $events->whereIn('events.id', $eventIds)));
            })
            ->pluck('id');

        return [$meetIds, $coachIds];
    }

    public function canAccessAthlete(User $user, Athlete $athlete): bool
    {
        $eventIds = $this->eventIds($user, $athlete->delegation->meet_id);
        $sportIds = $this->sportIds($user, $athlete->delegation->meet_id);

        $canAccessCompetition = $athlete->entries()->whereIn('event_id', $eventIds)->exists()
            || $athlete->sportRosterMemberships()
                ->whereHas('meetSport', fn (Builder $meetSport): Builder => $meetSport
                    ->where('meet_id', $athlete->delegation->meet_id)
                    ->whereIn('sport_id', $sportIds))
                ->exists();

        if ($canAccessCompetition || $athlete->registered_by === null) {
            return $canAccessCompetition;
        }

        [, $coachIds] = $this->assignedCoachScope(
            $user,
            $athlete->delegation->meet_id,
            $eventIds,
            $sportIds,
        );

        return $coachIds->contains($athlete->registered_by);
    }

    /** @return list<string> */
    public function competitionManagerRoles(): array
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
        ])->map(fn (MeetSportAssignmentRole $role): string => $role->value)->all();
    }

    /** @return list<string> */
    public function resultEncoderRoles(): array
    {
        return [
            MeetSportAssignmentRole::TournamentSecretary->value,
            MeetSportAssignmentRole::TournamentICT->value,
        ];
    }

    /** @param list<string> $roles */
    public function hasAssignmentRole(User $user, array $roles, ?int $meetId = null): bool
    {
        if ($user->role === UserRole::TournamentManager
            && $user->managedSport !== null
            && in_array(MeetSportAssignmentRole::TournamentManager->value, $roles, true)) {
            return true;
        }

        return $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active->value)
            ->whereIn('role', $roles)
            ->when($meetId !== null, fn ($query) => $query->whereHas(
                'meetSport', fn ($meetSport) => $meetSport->where('meet_id', $meetId),
            ))
            ->exists();
    }

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
        if ($user->trashed()) {
            return collect();
        }

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

    /** @return Collection<int, int> */
    public function sportIds(User $user, ?int $meetId = null): Collection
    {
        $sportIds = $this->assignments($user, $meetId)
            ->pluck('meetSport.sport_id')
            ->filter()
            ->map(fn ($id): int => (int) $id);

        if ($user->role === UserRole::TechnicalOfficial) {
            $sportIds = $sportIds->merge($user->sports()->pluck('sports.id'));
        } elseif ($user->role === UserRole::TournamentManager && $user->managedSport !== null) {
            $sportIds->push($user->managedSport->id);
        }

        return $sportIds->unique()->values();
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
