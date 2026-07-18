<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\Personnel;
use App\Models\User;

class PersonnelPolicy
{
    /**
     * Same scoping as athletes: viewers have no access.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer);
    }

    public function view(User $user, Personnel $personnel): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $personnel->delegation->hasOfficer($user);
    }

    public function create(User $user, Delegation $delegation): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $delegation->hasOfficer($user) && $delegation->isEditableByOfficers();
    }

    public function update(User $user, Personnel $personnel): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        $delegation = $personnel->delegation;

        return $delegation->hasOfficer($user) && $delegation->isEditableByOfficers();
    }

    public function delete(User $user, Personnel $personnel): bool
    {
        return $this->update($user, $personnel);
    }
}
