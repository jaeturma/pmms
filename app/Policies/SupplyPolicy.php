<?php

namespace App\Policies;

use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Models\Meet;
use App\Models\User;
use App\Policies\Concerns\ChecksManagementTeamMembership;

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
 * Admin/Organizer/Supply-Team-member-only, not open to every role.
 */
class SupplyPolicy
{
    use ChecksManagementTeamMembership;

    /**
     * Can this user see Supply/Equipment data at all (any meet)?
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer)
            || $this->hasActiveMembership($user, ManagementTeamType::Supply);
    }

    /**
     * Can this user manage this specific meet's Supply/Equipment data?
     */
    public function manage(User $user, Meet $meet): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer)
            || $this->hasActiveMembership($user, ManagementTeamType::Supply, $meet);
    }
}
