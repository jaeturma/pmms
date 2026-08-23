<?php

namespace App\Policies;

use App\Enums\ManagementTeamType;
use App\Models\Meet;
use App\Models\User;
use App\Policies\Concerns\ChecksManagementTeamMembership;

/**
 * Standard two-tier shape (`SupplyPolicy`/`FoodPolicy`) — DRRM data
 * (plans, routes, contacts, equipment, incident reports) is not
 * personally sensitive the way Medical is, so it needs no third tier.
 * See docs/medical-drrm.md.
 */
class DrrmPolicy
{
    use ChecksManagementTeamMembership;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $this->hasActiveMembership($user, ManagementTeamType::DRRM);
    }

    public function manage(User $user, Meet $meet): bool
    {
        return $user->isAdmin()
            || $this->hasActiveMembership($user, ManagementTeamType::DRRM, $meet);
    }
}
