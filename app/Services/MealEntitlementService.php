<?php

namespace App\Services;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\MealEntitlement;
use App\Models\MealSchedule;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Support\Collection;

class MealEntitlementService
{
    public function isEligible(User $user, Meet $meet): bool
    {
        if ($user->hasRole(UserRole::Coach)) {
            return false;
        }

        return $user->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active->value)
            ->whereHas('managementTeam', fn ($teams) => $teams->where('meet_id', $meet->id))
            ->exists()
            || $user->meetSportAssignments()
                ->where('status', MeetSportAssignmentStatus::Active->value)
                ->whereHas('meetSport', fn ($sports) => $sports->where('meet_id', $meet->id))
                ->exists();
    }

    /** @return Collection<int, User> */
    public function eligibleUsers(Meet $meet): Collection
    {
        return User::query()->whereNull('disabled_at')
            ->where('role', '!=', UserRole::Coach->value)
            ->where(fn ($users) => $users
                ->whereHas('managementTeamMemberships', fn ($members) => $members
                    ->where('status', ManagementTeamMemberStatus::Active->value)
                    ->whereHas('managementTeam', fn ($teams) => $teams->where('meet_id', $meet->id)))
                ->orWhereHas('meetSportAssignments', fn ($assignments) => $assignments
                    ->where('status', MeetSportAssignmentStatus::Active->value)
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

    public function displayState(MealEntitlement $entitlement, $now = null): string
    {
        if ($entitlement->status !== 'available') {
            return $entitlement->status;
        }
        $now ??= now();
        $schedule = $entitlement->schedule;
        $start = $schedule->date->copy()->setTimeFromTimeString($schedule->starts_at ?: '00:00:00');
        $end = $schedule->date->copy()->setTimeFromTimeString($schedule->ends_at ?: '23:59:59');

        return $now->lt($start) ? 'upcoming' : ($now->gt($end) ? 'missed' : 'available');
    }
}
