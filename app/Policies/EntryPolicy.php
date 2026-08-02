<?php

namespace App\Policies;

use App\Enums\EntryStatus;
use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\User;

class EntryPolicy
{
    /**
     * Entries carry athlete names (minors) — viewers have no access. A
     * Coach is scoped identically to a Delegation Officer for create/
     * withdraw/delete below — see `Delegation::hasCoach()` — but never
     * for `confirm()`, which stays a manager-only decision.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer, UserRole::Coach);
    }

    /**
     * Managers may submit anytime; officers and coaches for their own
     * delegation while the meet's registration window is open. Unlike
     * roster edits, entries do not require the delegation to still be a
     * draft.
     */
    public function create(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return ($delegation->hasOfficer($user) || $delegation->hasCoach($user))
            && $delegation->meet->isRegistrationOpen();
    }

    /**
     * Confirming entries is a manager decision.
     */
    public function confirm(User $user, Entry $entry): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer);
    }

    /**
     * Managers may withdraw any entry; officers and coaches only their own
     * delegation's still-submitted entries while registration is open.
     */
    public function withdraw(User $user, Entry $entry): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $entry->status === EntryStatus::Submitted
            && ($entry->delegation->hasOfficer($user) || $entry->delegation->hasCoach($user))
            && $entry->delegation->meet->isRegistrationOpen();
    }

    /**
     * Only withdrawn entries may be deleted (frees the athlete+event slot).
     */
    public function delete(User $user, Entry $entry): bool
    {
        if ($entry->status !== EntryStatus::Withdrawn) {
            return false;
        }

        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return ($entry->delegation->hasOfficer($user) || $entry->delegation->hasCoach($user))
            && $entry->delegation->meet->isRegistrationOpen();
    }
}
