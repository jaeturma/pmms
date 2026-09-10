<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

/**
 * Internal announcement management. The registry is visible to any
 * Organizer and to an Active ICT / Information `ManagementTeam` member
 * (`User::canViewAnnouncements()`); creating, editing, publishing, and
 * deleting are limited to a System Administrator, an ICT / Central-ICT
 * member, or an Information-team member (`User::canManageAnnouncements()`).
 * This policy is the authorization surface for those two predicates
 * (WP-REALIGN-13) — there is no per-record owner-scoping.
 *
 * The public portal reads published rows directly and never goes through
 * this policy.
 */
class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewAnnouncements();
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->canViewAnnouncements();
    }

    public function create(User $user): bool
    {
        return $user->canManageAnnouncements();
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->canManageAnnouncements();
    }

    /** Gates both publish and unpublish. */
    public function publish(User $user, Announcement $announcement): bool
    {
        return $user->canManageAnnouncements();
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->canManageAnnouncements();
    }
}
