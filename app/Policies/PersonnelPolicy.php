<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\Personnel;
use App\Models\User;

class PersonnelPolicy
{
    /**
     * Same scoping as athletes: viewers have no access. A Coach is scoped
     * identically to a Delegation Officer here — see `Delegation::hasCoach()`.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManagePersonnel()
            || $user->hasRole(UserRole::Organizer, UserRole::DelegationOfficer, UserRole::Coach);
    }

    public function view(User $user, Personnel $personnel): bool
    {
        if ($user->canManagePersonnel() || $user->hasRole(UserRole::Organizer)) {
            return true;
        }

        return $personnel->delegation->hasOfficer($user) || $personnel->delegation->hasCoach($user);
    }

    public function create(User $user, Delegation $delegation): bool
    {
        return $user->canManagePersonnel();
    }

    public function update(User $user, Personnel $personnel): bool
    {
        return $user->canManagePersonnel();
    }

    public function delete(User $user, Personnel $personnel): bool
    {
        return $this->update($user, $personnel);
    }
}
