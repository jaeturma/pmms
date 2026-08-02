<?php

namespace App\Policies\Concerns;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Models\Meet;
use App\Models\User;

/**
 * Shared by every `ManagementTeam`-scoped policy (`SupplyPolicy`,
 * `FoodPolicy`, `BilletingPolicy`, `TransportPolicy`) — each repeats the
 * identical "does this user have an Active membership of the matching
 * `team_type`, optionally for one specific meet" check. Extracted here on
 * its third/fourth use (WP-REALIGN-11), the same "extract on second use"
 * discipline this codebase applies elsewhere.
 */
trait ChecksManagementTeamMembership
{
    /**
     * A `Pending` member hasn't been confirmed yet and a `Declined`/
     * `Ended` member no longer has standing — only `Active` counts.
     */
    protected function hasActiveMembership(User $user, ManagementTeamType $type, ?Meet $meet = null): bool
    {
        return $user->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($query) => $query
                ->where('team_type', $type)
                ->when($meet !== null, fn ($q) => $q->where('meet_id', $meet->id)))
            ->exists();
    }
}
