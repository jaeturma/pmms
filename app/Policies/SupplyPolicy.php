<?php

namespace App\Policies;

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Not a per-model policy — Supply/Equipment spans six models
 * (`EquipmentCategory`/`EquipmentItem`/`EquipmentIssue`/
 * `EquipmentReturn`/`EquipmentTransfer`/`InventoryAdjustment`) with one
 * shared authorization concept (a meet's Supply Team), so this is a
 * plain class injected into controllers and called directly via
 * `abort_unless`, the same shape `ScoringSessionController::canManage()`
 * already uses — not Laravel's per-model Policy auto-discovery. See
 * `docs/architecture/pmms-role-and-scope-map.md`'s recommended
 * enforcement pattern for WP-REALIGN-09-through-13 domains and
 * docs/equipment-management.md.
 *
 * Unlike `ManagementTeamController` (view open to every authenticated
 * role, since "who's on the ICT team" isn't sensitive), equipment
 * inventory is internal operational data — `viewAny`/`manage` are both
 * Admin/Organizer/Supply-Team-member-only, not open to every role. A
 * membership only counts once it's `Active` — a `Pending` member hasn't
 * been confirmed yet, and a `Declined`/`Ended` member no longer has
 * standing.
 */
class SupplyPolicy
{
    /**
     * Can this user see Supply/Equipment data at all (any meet)?
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $this->activeSupplyMemberships($user)->exists();
    }

    /**
     * Can this user manage this specific meet's Supply/Equipment data?
     */
    public function manage(User $user, Meet $meet): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $this->activeSupplyMemberships($user)
            ->whereHas('managementTeam', fn ($query) => $query->where('meet_id', $meet->id))
            ->exists();
    }

    /**
     * @return HasMany<ManagementTeamMember, User>
     */
    private function activeSupplyMemberships(User $user): HasMany
    {
        return $user->managementTeamMemberships()
            ->where('status', ManagementTeamMemberStatus::Active)
            ->whereHas('managementTeam', fn ($query) => $query->where('team_type', ManagementTeamType::Supply));
    }
}
