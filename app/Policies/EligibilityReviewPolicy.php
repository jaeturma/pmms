<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\User;

class EligibilityReviewPolicy
{
    /**
     * Eligibility data concerns minors — viewers have no access.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer);
    }

    /**
     * Managers see any review; officers only their own delegation's.
     */
    public function view(User $user, EligibilityReview $review): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $review->athlete->delegation->hasOfficer($user);
    }

    /**
     * Managers may upload anytime; officers for their own delegation's
     * athletes while the meet's registration window is open (entries-style
     * window — the delegation need not still be a draft).
     */
    public function upload(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $delegation->hasOfficer($user)
            && $delegation->meet->isRegistrationOpen();
    }

    /**
     * Eligibility decisions are made by managers, never automatically.
     */
    public function decide(User $user, EligibilityReview $review): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer);
    }
}
