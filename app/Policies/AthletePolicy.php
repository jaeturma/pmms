<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\User;

class AthletePolicy
{
    /**
     * Athlete data belongs to minors — viewers have no access at all.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer);
    }

    /**
     * Managers see any athlete; officers only their own delegation's.
     */
    public function view(User $user, Athlete $athlete): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $athlete->delegation->hasOfficer($user);
    }

    /**
     * Managers may register athletes for any delegation; officers only for
     * their own, while it is a draft and registration is open.
     */
    public function create(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $delegation->hasOfficer($user) && $delegation->isEditableByOfficers();
    }

    public function update(User $user, Athlete $athlete): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        $delegation = $athlete->delegation;

        return $delegation->hasOfficer($user) && $delegation->isEditableByOfficers();
    }

    public function delete(User $user, Athlete $athlete): bool
    {
        return $this->update($user, $athlete);
    }
}
