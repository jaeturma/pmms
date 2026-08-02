<?php

namespace App\Policies;

use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Models\BilletingAssignment;
use App\Models\Meet;
use App\Models\User;
use App\Policies\Concerns\ChecksManagementTeamMembership;

/**
 * Plain class, same shape as `SupplyPolicy`/`FoodPolicy`, but with a
 * third tier `manage()` doesn't have: the approved organizational
 * model requires "contact/room detail restricted to Billeting Team + the
 * assigned delegation's own officer" — a DelegationOfficer may read (not
 * manage) their own delegation's assignment. This is row-level scoping
 * (an officer's own row arrives with every field intact, they're
 * entitled to all of it), not field-level redaction — the same pattern
 * `ProtestPolicy`/`ProtestController` already use for delegation
 * officers scoped to their own delegation's protests. See
 * docs/food-billeting-transport.md.
 */
class BilletingPolicy
{
    use ChecksManagementTeamMembership;

    /**
     * Any Admin/Organizer/Billeting-Team-member, or any DelegationOfficer
     * (their own query is scoped to their own delegation's assignment —
     * this only gates whether the page is reachable at all).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer)
            || $this->hasActiveMembership($user, ManagementTeamType::Billeting);
    }

    /**
     * Can this user manage (create/update/delete) this meet's Billeting
     * data? DelegationOfficers never manage, read-only via
     * `canView()`.
     */
    public function manage(User $user, Meet $meet): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer)
            || $this->hasActiveMembership($user, ManagementTeamType::Billeting, $meet);
    }

    /**
     * Can this user see this specific assignment — managers see every
     * assignment for the meet, a DelegationOfficer only their own
     * delegation's.
     */
    public function view(User $user, BilletingAssignment $assignment): bool
    {
        if ($this->manage($user, $assignment->meet)) {
            return true;
        }

        return $user->role === UserRole::DelegationOfficer
            && $assignment->delegation->hasOfficer($user);
    }
}
