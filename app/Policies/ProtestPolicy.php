<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\User;

class ProtestPolicy
{
    /**
     * Protests reference results and delegations — viewers have no access.
     * Officers see only their own delegation's protests (scoped in the
     * controller query).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer);
    }

    /**
     * Managers may file for any delegation; officers only for their own.
     */
    public function create(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $delegation->hasOfficer($user);
    }
}
