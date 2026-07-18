<?php

namespace App\Policies;

use App\Enums\DelegationStatus;
use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\User;

class DelegationPolicy
{
    /**
     * Managers and viewers may see any delegation; officers only their own.
     */
    public function view(User $user, Delegation $delegation): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::Viewer)
            || $delegation->hasOfficer($user);
    }

    /**
     * Managers may always edit; an assigned officer only while the
     * delegation is a draft and the meet's registration window is open.
     */
    public function update(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $delegation->hasOfficer($user)
            && $delegation->status === DelegationStatus::Draft
            && $delegation->meet->isRegistrationOpen();
    }

    /**
     * Managers, or an assigned officer while registration is open.
     */
    public function submit(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $delegation->hasOfficer($user)
            && $delegation->meet->isRegistrationOpen();
    }

    /**
     * Approval (and returning a submission) is a manager decision.
     */
    public function approve(User $user, Delegation $delegation): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer);
    }

    /**
     * Only draft delegations may be deleted, and only by managers.
     */
    public function delete(User $user, Delegation $delegation): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer)
            && $delegation->status === DelegationStatus::Draft;
    }

    /**
     * Officer assignment is a manager decision.
     */
    public function assignOfficers(User $user, Delegation $delegation): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer);
    }
}
