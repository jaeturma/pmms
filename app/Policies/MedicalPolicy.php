<?php

namespace App\Policies;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Enums\Permission;
use App\Models\Meet;
use App\Models\User;
use App\Policies\Concerns\ChecksManagementTeamMembership;

/**
 * A real departure from every other WP-REALIGN-0x policy — Medical does
 * not grant Organizer unconditional access, only Admin does, per the
 * owner's own "Medical Team only" decision (not "Medical Team +
 * managers"). Three tiers instead of two:
 *
 * - `viewAny` (aggregate status only): Admin/Organizer/Medical-Team, the
 *   usual broad-summary visibility.
 * - `viewDetail`/`manage` (raw conditions/contact/notes): Medical-Team-
 *   or-Admin only — mirrors the `administer` gate's Admin-only tier this
 *   app already uses for its most sensitive existing data, not the
 *   Admin+Organizer `manage-meet-data` gate every other domain uses.
 * - `requestEmergencyAccess`: any authenticated staff role, logged and
 *   subject to mandatory review — see `MedicalAccessController`.
 *
 * See docs/medical-drrm.md.
 */
class MedicalPolicy
{
    use ChecksManagementTeamMembership;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer)
            || $this->hasActiveMembership($user, ManagementTeamType::Medical);
    }

    public function viewDetail(User $user, Meet $meet): bool
    {
        return $this->manage($user, $meet);
    }

    public function manage(User $user, Meet $meet): bool
    {
        return $user->hasPermission(Permission::MedicalClearanceApprove, $meet);
    }

    /**
     * Any staff role may request emergency access to one specific
     * clearance record — a Viewer account is not "staff" and is
     * excluded.
     */
    public function requestEmergencyAccess(User $user): bool
    {
        return $user->role !== UserRole::Viewer;
    }

    /**
     * Post-use review of an emergency access invocation: Admin, or an
     * Active Medical Team lead (`is_head` member).
     */
    public function reviewAccess(User $user, Meet $meet): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        return $user->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->where('is_head', true)
            ->whereHas('managementTeam', fn ($query) => $query
                ->where('team_type', ManagementTeamType::Medical)
                ->where('meet_id', $meet->id))
            ->exists();
    }
}
