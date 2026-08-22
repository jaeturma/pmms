<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\User;

class AthletePolicy
{
    /**
     * Athlete data belongs to minors — viewers have no access at all. A
     * Coach is scoped identically to a Delegation Officer here (their own
     * delegation's roster only) — see `Delegation::hasCoach()`.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer, UserRole::Coach)
            || $user->hasPermission(Permission::DistrictAthletesView)
            || $user->hasPermission(Permission::MunicipalityAthletesView)
            || $user->tournamentAthleteSportIds()->isNotEmpty();
    }

    /**
     * Managers see any athlete; officers and coaches only their own
     * delegation's.
     */
    public function view(User $user, Athlete $athlete): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        if ($athlete->entries()->whereHas('event', fn ($event) => $event->whereIn('sport_id', $user->tournamentAthleteSportIds()))->exists()) {
            return true;
        }

        if ($user->athleteOversightAssignments()->where('active', true)->where('meet_id', $athlete->delegation->meet_id)
            ->where(function ($query) use ($athlete) {
                $query->where(fn ($scope) => $scope->where('authority_type', 'district_sports_coordinator')->where('school_district_id', $athlete->school->school_district_id))
                    ->orWhere(fn ($scope) => $scope->where('authority_type', 'municipality_team_manager')->where('district_id', $athlete->school->district_id));
            })->exists()) {
            return true;
        }

        return $athlete->delegation->hasOfficer($user) || $user->hasApprovedCoachScope($athlete->delegation);
    }

    /**
     * Managers may maintain every roster. Delegation officers are limited to
     * their own editable roster, while coaches require an approved assignment.
     */
    public function create(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        if ($user->role === UserRole::Coach) {
            return $user->hasApprovedCoachScope($delegation);
        }

        return $delegation->hasOfficer($user) && $delegation->isEditableByOfficers();
    }

    public function update(User $user, Athlete $athlete): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        if ($user->role === UserRole::Coach) {
            return $user->hasApprovedCoachScope($athlete->delegation)
                && $athlete->entries()->whereIn(
                    'event_id',
                    $user->coachAssignmentRequests()
                        ->where('status', 'approved')
                        ->where('delegation_id', $athlete->delegation_id)
                        ->select('event_id'),
                )->exists();
        }

        return $athlete->delegation->hasOfficer($user)
            && $athlete->delegation->isEditableByOfficers();
    }

    public function delete(User $user, Athlete $athlete): bool
    {
        return $this->update($user, $athlete);
    }
}
