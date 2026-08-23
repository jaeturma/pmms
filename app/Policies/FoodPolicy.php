<?php

namespace App\Policies;

use App\Enums\ManagementTeamType;
use App\Models\Meet;
use App\Models\User;
use App\Policies\Concerns\ChecksManagementTeamMembership;

/**
 * Plain class, not Laravel's per-model Policy auto-discovery — Food spans
 * two models (`MealAnnouncement`/`MealSchedule`) with one shared
 * authorization concept (a meet's Food Team), same shape as
 * `SupplyPolicy`. Meal schedules/announcements are meet-wide operational
 * info, not delegation-specific, so this is a plain two-tier check —
 * unlike `BilletingPolicy`/`TransportPolicy`, there's no third
 * DelegationOfficer tier here. See docs/food-billeting-transport.md.
 */
class FoodPolicy
{
    use ChecksManagementTeamMembership;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $this->hasActiveMembership($user, ManagementTeamType::Food);
    }

    public function manage(User $user, Meet $meet): bool
    {
        return $user->isAdmin()
            || $this->hasActiveMembership($user, ManagementTeamType::Food, $meet);
    }
}
