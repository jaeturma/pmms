<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Setting;
use App\Models\User;
use App\Services\CompetitionAccessService;
use App\Enums\MeetSportAssignmentRole;

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
            || $user->canManageProductionAccounts()
            || $user->hasPermission(Permission::AthleteEligibilityReview)
            || $user->hasPermission(Permission::DistrictAthletesView)
            || $user->hasPermission(Permission::MunicipalityAthletesView)
            || $user->tournamentMeetIds()->isNotEmpty();
    }

    /**
     * Managers see any athlete; officers and coaches only their own
     * delegation's.
     */
    public function view(User $user, Athlete $athlete): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer) || $user->canManageProductionAccounts()) {
            return true;
        }

        if ($user->hasPermission(Permission::AthleteEligibilityReview, $athlete->delegation->meet)) {
            return true;
        }

        if (app(CompetitionAccessService::class)->canAccessAthlete($user, $athlete)) {
            return true;
        }

        if ($user->athleteOversightAssignments()->where('active', true)->where('meet_id', $athlete->delegation->meet_id)
            ->where(function ($query) use ($athlete) {
                $query->where(fn ($scope) => $scope->where('authority_type', 'district_sports_coordinator')->where('school_district_id', $athlete->school->school_district_id))
                    ->orWhere(fn ($scope) => $scope->where('authority_type', 'municipality_team_manager')->where('district_id', $athlete->school->district_id));
            })->exists()) {
            return true;
        }

        return $athlete->delegation->hasOfficer($user)
            || ($user->role === UserRole::Coach
                && $user->hasApprovedCoachScope($athlete->delegation)
                && $athlete->isOwnedBy($user));
    }

    /**
     * Managers may maintain every roster. Delegation officers are limited to
     * their own editable roster, while coaches require an approved assignment.
     */
    public function create(User $user, Delegation $delegation): bool
    {
        if ($user->isAdmin() || $user->canManageProductionAccounts()) {
            return true;
        }

        if ($user->role === UserRole::Coach) {
            return Setting::current()->coach_athlete_registration_enabled
                && $user->hasApprovedCoachScope($delegation);
        }

        return false;
    }

    public function update(User $user, Athlete $athlete): bool
    {
        if ($user->isAdmin() || $user->canManageProductionAccounts()) {
            return true;
        }

        if ($user->role === UserRole::Coach) {
            return $athlete->isOwnedBy($user)
                && $user->hasApprovedCoachScope($athlete->delegation)
                && $this->isNotEligible($athlete);
        }

        if ($this->isAssignedTournamentIct($user, $athlete)) {
            return $this->isNotEligible($athlete);
        }

        return $athlete->delegation->hasOfficer($user)
            && $athlete->delegation->isEditableByOfficers();
    }

    public function delete(User $user, Athlete $athlete): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $this->isNotEligible($athlete)) {
            return false;
        }

        return $this->isAssignedTournamentIct($user, $athlete)
            || ($user->role === UserRole::Coach
                && $athlete->isOwnedBy($user)
                && $user->hasApprovedCoachScope($athlete->delegation));
    }

    private function isNotEligible(Athlete $athlete): bool
    {
        return $athlete->eligibilityReview()->where('status', 'approved')->doesntExist();
    }

    private function isAssignedTournamentIct(User $user, Athlete $athlete): bool
    {
        $access = app(CompetitionAccessService::class);

        $hasIctAssignment = $access->hasAssignmentRole(
            $user,
            [MeetSportAssignmentRole::TournamentICT->value],
            $athlete->delegation->meet_id,
        );

        if (! $hasIctAssignment) {
            return false;
        }

        $assignedMeetSportIds = $user->meetSportAssignments()
            ->where('status', 'active')
            ->where('role', MeetSportAssignmentRole::TournamentICT->value)
            ->whereHas('meetSport', fn ($query) => $query->where('meet_id', $athlete->delegation->meet_id))
            ->pluck('meet_sport_id');

        return $access->canAccessAthlete($user, $athlete)
            || $athlete->sportRosterMemberships()->whereIn('meet_sport_id', $assignedMeetSportIds)->exists();
    }
}
