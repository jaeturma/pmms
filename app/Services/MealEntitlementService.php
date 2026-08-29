<?php

namespace App\Services;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\PersonnelRole;
use App\Enums\UserRole;
use App\Models\MealEntitlement;
use App\Models\MealSchedule;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MealEntitlementService
{
    public function isEligible(User $user, Meet $meet): bool
    {
        if ($user->hasRole(UserRole::Coach)
            || $user->personnel()->whereIn('role', [PersonnelRole::Coach->value, PersonnelRole::AssistantCoach->value])->exists()) {
            return false;
        }

        return $user->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active->value)
            ->whereHas('managementTeam', fn ($teams) => $teams->where('meet_id', $meet->id))
            ->exists()
            || $user->meetSportAssignments()
                ->where('status', MeetSportAssignmentStatus::Active->value)
                ->whereIn('role', [
                    MeetSportAssignmentRole::TournamentManager->value,
                    MeetSportAssignmentRole::AssistantTournamentManager->value,
                    MeetSportAssignmentRole::TournamentICT->value,
                    MeetSportAssignmentRole::TournamentSecretary->value,
                    MeetSportAssignmentRole::TechnicalOfficial->value,
                ])
                ->whereHas('meetSport', fn ($sports) => $sports->where('meet_id', $meet->id))
                ->exists();
    }

    /** @return Collection<int, User> */
    public function eligibleUsers(Meet $meet): Collection
    {
        return User::query()->whereNull('disabled_at')
            ->where('role', '!=', UserRole::Coach->value)
            ->whereDoesntHave('personnel', fn ($personnel) => $personnel
                ->whereIn('role', [PersonnelRole::Coach->value, PersonnelRole::AssistantCoach->value]))
            ->where(fn ($users) => $users
                ->whereHas('managementTeamMemberships', fn ($members) => $members
                    ->where('status', ManagementTeamMemberStatus::Active->value)
                    ->whereHas('managementTeam', fn ($teams) => $teams->where('meet_id', $meet->id)))
                ->orWhereHas('meetSportAssignments', fn ($assignments) => $assignments
                    ->where('status', MeetSportAssignmentStatus::Active->value)
                    ->whereIn('role', [
                        MeetSportAssignmentRole::TournamentManager->value,
                        MeetSportAssignmentRole::AssistantTournamentManager->value,
                        MeetSportAssignmentRole::TournamentICT->value,
                        MeetSportAssignmentRole::TournamentSecretary->value,
                        MeetSportAssignmentRole::TechnicalOfficial->value,
                    ])
                    ->whereHas('meetSport', fn ($sports) => $sports->where('meet_id', $meet->id))))
            ->get();
    }

    public function sync(Meet $meet): void
    {
        $userIds = $this->eligibleUsers($meet)->modelKeys();
        if ($userIds === []) {
            return;
        }

        foreach (MealSchedule::query()->where('meet_id', $meet->id)->pluck('id') as $scheduleId) {
            foreach ($userIds as $userId) {
                MealEntitlement::query()->firstOrCreate([
                    'meal_schedule_id' => $scheduleId,
                    'user_id' => $userId,
                ]);
            }
        }
    }

    /** @return array{role: string, sport: ?string} */
    public function identity(User $user, Meet $meet): array
    {
        $assignment = $user->meetSportAssignments()->where('status', 'active')
            ->whereHas('meetSport', fn ($sports) => $sports->where('meet_id', $meet->id))
            ->with('meetSport.sport:id,name')->first();
        if ($assignment !== null) {
            return ['role' => $assignment->role->label(), 'sport' => $assignment->meetSport->sport->name];
        }

        $membership = $user->managementTeamMemberships()->where('status', 'active')
            ->whereHas('managementTeam', fn ($teams) => $teams->where('meet_id', $meet->id))
            ->with('managementTeam:id,name')->first();

        return ['role' => $membership?->role_title ?: $membership?->managementTeam?->name ?: 'Meet Management', 'sport' => null];
    }

    public function effectiveStatus(MealEntitlement $entitlement, $now = null): string
    {
        if ($entitlement->status === 'consumed' || $entitlement->consumed_at !== null) {
            return 'consumed';
        }

        $timezone = (string) config('app.timezone', 'Asia/Manila');
        $now = ($now ?? now())->copy()->setTimezone($timezone);
        $schedule = $entitlement->schedule;
        $date = $schedule->date->toDateString();
        $start = Carbon::parse($date.' '.($schedule->starts_at ?: '00:00:00'), $timezone);
        $end = Carbon::parse($date.' '.($schedule->ends_at ?: '23:59:59'), $timezone);

        return $now->lt($start) ? 'upcoming' : ($now->gt($end) ? 'expired' : 'available');
    }

    /** Backward-compatible name for existing controller consumers. */
    public function displayState(MealEntitlement $entitlement, $now = null): string
    {
        return $this->effectiveStatus($entitlement, $now);
    }
}
