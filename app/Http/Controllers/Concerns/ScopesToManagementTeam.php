<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Models\ManagementTeamMember;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Shared by every WP-REALIGN-0x `index()` (Equipment/Food/Billeting/
 * Transport) that lists a meet-scoped, `ManagementTeam`-owned catalog:
 * Admin/Organizer see every meet, everyone else only meets where they
 * hold an Active membership of the matching `team_type`.
 */
trait ScopesToManagementTeam
{
    /**
     * @return Collection<int, int>|null Null means unrestricted (Admin/Organizer).
     */
    protected function accessibleMeetIds(User $user, ManagementTeamType $type): ?Collection
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return null;
        }

        return ManagementTeamMember::query()
            ->where('user_id', $user->id)
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($q) => $q->where('team_type', $type))
            ->with('managementTeam:id,meet_id')
            ->get()
            ->pluck('managementTeam.meet_id')
            ->unique()
            ->values();
    }
}
