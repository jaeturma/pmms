<?php

namespace App\Policies;

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
        return $user->isAdmin() || $user->canManageProductionAccounts() || $user->canFileProtest();
    }

    /**
     * Managers may file for any delegation; officers only for their own.
     */
    public function create(User $user, Delegation $delegation): bool
    {
        return $user->canFileProtest($delegation);
    }
}
