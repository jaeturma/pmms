<?php

namespace App\Services;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\ResultPlacement;
use App\Models\TeamEntry;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ResultAttributionService
{
    public function canManage(User $user, Event $event, Delegation $delegation): bool
    {
        if ($user->isAdmin() || $user->hasApprovedCoachScope($delegation, $event)) {
            return true;
        }
        if ($user->managementTeamMemberships()->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($q) => $q->where('meet_id', $delegation->meet_id)->where('source_code', 'EVENT_SECRETARIAT'))->exists()) {
            return true;
        }

        return $user->meetSportAssignments()->where('status', MeetSportAssignmentStatus::Active)
            ->whereIn('role', [MeetSportAssignmentRole::TournamentICT, MeetSportAssignmentRole::TournamentSecretary])
            ->whereHas('meetSport', fn ($q) => $q->where('meet_id', $delegation->meet_id)->where('sport_id', $event->sport_id))->exists()
            && app(CompetitionAccessService::class)->canAccessEvent($user, $event, $delegation->meet_id);
    }

    public function athletes(Event $event, Delegation $delegation)
    {
        return Athlete::query()->where('delegation_id', $delegation->id)
            ->whereHas('sportRosterMemberships', fn ($q) => $q->where('delegation_id', $delegation->id)
                ->whereHas('meetSport', fn ($scope) => $scope->where('meet_id', $delegation->meet_id)->where('sport_id', $event->sport_id)));
    }

    public function coaches(Event $event, Delegation $delegation)
    {
        return User::query()->whereHas('coachAssignmentRequests', fn ($q) => $q
            ->where('delegation_id', $delegation->id)->where('status', 'approved')->whereNull('ended_at')
            ->whereHas('meetSport', fn ($s) => $s->where('meet_id', $delegation->meet_id)->where('sport_id', $event->sport_id)))
            ->get()->filter(fn ($user) => $user->hasApprovedCoachScope($delegation, $event))->values();
    }

    public function validate(Event $event, Delegation $delegation, array $data): array
    {
        $athlete = $data['athlete_id'] ?? null;
        $members = array_values(array_unique($data['athlete_ids'] ?? []));
        $coaches = $data['coaches'] ?? [];
        $teamId = $data['team_entry_id'] ?? null;
        if ($event->is_team_event) {
            if ($athlete) {
                throw ValidationException::withMessages(['attribution' => 'Team results use a roster.']);
            }
            if ($teamId) {
                $team = TeamEntry::query()->where('event_id', $event->id)->where('delegation_id', $delegation->id)->find($teamId);
                if (! $team) {
                    throw ValidationException::withMessages(['attribution' => 'Team Entry must belong to this event and delegation.']);
                }
                // Copy IDs only on explicit import; never derive historical players from mutable membership.
                if (! array_key_exists('athlete_ids', $data)) {
                    $members = $team->members()->pluck('athlete_id')->unique()->values()->all();
                }
            }
        } elseif ($members || $coaches || $teamId) {
            throw ValidationException::withMessages(['attribution' => 'Individual results use an optional athlete.']);
        }
        $ids = $athlete ? [$athlete] : $members;
        if ($this->athletes($event, $delegation)->whereKey($ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['attribution' => 'Every athlete must belong to this delegation and current Meet sport roster.']);
        }
        $allowed = $this->coaches($event, $delegation)->modelKeys();
        foreach ($coaches as $coach) {
            if (! in_array((int) $coach['user_id'], $allowed, true)) {
                throw ValidationException::withMessages(['attribution' => 'Coach must be assigned to this delegation and event.']);
            }
        }

        return ['athlete_id' => $athlete, 'athlete_ids' => $members, 'team_entry_id' => $teamId, 'coaches' => $coaches];
    }

    public function save(ResultPlacement $placement, array $data): void
    {
        $before = $this->report($placement);
        $placement->update(['athlete_id' => $data['athlete_id'], 'team_entry_id' => $data['team_entry_id']]);
        $placement->reportingAthletes()->sync($data['athlete_ids']);
        $placement->reportingCoaches()->sync(collect($data['coaches'])->mapWithKeys(fn ($c) => [$c['user_id'] => ['role' => $c['role']]])->all());
        $placement->unsetRelation('athlete')->unsetRelation('reportingAthletes')->unsetRelation('reportingCoaches');
        app(AuditLogger::class)->record('result.attribution_updated', $placement->result, [
            'placement_id' => $placement->id, 'before' => $before, 'after' => $this->report($placement),
        ]);
    }

    public function report(ResultPlacement $placement): array
    {
        return [
            'athlete_id' => $placement->athlete_id,
            'team_entry_id' => $placement->team_entry_id,
            'athlete_name' => $placement->athlete?->fullName(),
            'athlete_ids' => $placement->reportingAthletes->modelKeys(),
            'players' => $placement->reportingAthletes->map(fn ($a) => $a->fullName())->all(),
            'coaches' => $placement->reportingCoaches->map(fn ($c) => ['user_id' => $c->id, 'name' => $c->name, 'role' => $c->pivot->role])->all(),
        ];
    }
}
