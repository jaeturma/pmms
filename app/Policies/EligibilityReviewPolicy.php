<?php

namespace App\Policies;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\User;

class EligibilityReviewPolicy
{
    /**
     * Eligibility data concerns minors — viewers have no access. A Coach
     * is scoped identically to a Delegation Officer for view/upload —
     * see `Delegation::hasCoach()` — but never for `decide()` below,
     * which stays a manager-only DSAC-style decision.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer, UserRole::Coach)
            || $user->hasPermission(Permission::AthleteEligibilityReview)
            || $user->hasPermission(Permission::DistrictAthletesView)
            || $user->hasPermission(Permission::MunicipalityAthletesView);
    }

    /**
     * Managers see any review; officers and coaches only their own
     * delegation's.
     */
    public function view(User $user, EligibilityReview $review): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        if ($user->hasPermission(Permission::AthleteEligibilityReview, $review->meet)) {
            return true;
        }

        $school = $review->athlete->school;
        if ($user->athleteOversightAssignments()->where('active', true)->where('meet_id', $review->meet_id)
            ->where(function ($query) use ($school) {
                $query->where(fn ($scope) => $scope->where('authority_type', 'district_sports_coordinator')->where('school_district_id', $school->school_district_id))
                    ->orWhere(fn ($scope) => $scope->where('authority_type', 'municipality_team_manager')->where('district_id', $school->district_id));
            })->exists()) {
            return true;
        }

        return $review->athlete->delegation->hasOfficer($user) || $review->athlete->delegation->hasCoach($user);
    }

    /**
     * Managers may upload anytime; officers and coaches for their own
     * delegation's athletes while the meet's registration window is open
     * (entries-style window — the delegation need not still be a draft).
     */
    public function upload(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return ($delegation->hasOfficer($user) || $delegation->hasCoach($user))
            && $delegation->meet->isRegistrationOpen();
    }

    /**
     * Eligibility decisions are made by managers, never automatically —
     * a Coach submits and resubmits (`upload()` above) but never decides
     * their own submission, same as an officer today.
     */
    public function decide(User $user, EligibilityReview $review): bool
    {
        return $user->hasPermission(Permission::AthleteEligibilityApprove, $review->meet);
    }
}
