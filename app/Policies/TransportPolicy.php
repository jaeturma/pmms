<?php

namespace App\Policies;

use App\Enums\ManagementTeamType;
use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\Meet;
use App\Models\TransportRequest;
use App\Models\TransportTrip;
use App\Models\User;
use App\Policies\Concerns\ChecksManagementTeamMembership;

/**
 * Same three-tier shape as `BilletingPolicy` ("driver/passenger PII
 * restricted same as Billeting" per the approved organizational model),
 * plus one real manage action a DelegationOfficer *does* get:
 * `createRequest()` — filing a transport request for their own
 * delegation, the same shape `ProtestController::create()` already
 * grants officers for their own delegation's protests. See
 * docs/food-billeting-transport.md.
 */
class TransportPolicy
{
    use ChecksManagementTeamMembership;

    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer, UserRole::DelegationOfficer)
            || $this->hasActiveMembership($user, ManagementTeamType::Transport);
    }

    /**
     * Can this user manage (create/update/delete) this meet's vehicles/
     * trips, and update/delete any request? DelegationOfficers never
     * manage — read-only via `viewRequest()`/`viewTrip()`, plus the one
     * exception in `createRequest()`.
     */
    public function manage(User $user, Meet $meet): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Organizer)
            || $this->hasActiveMembership($user, ManagementTeamType::Transport, $meet);
    }

    /**
     * A DelegationOfficer may file a request for their own delegation —
     * the one manage action this domain grants outside the Transport
     * Team.
     */
    public function createRequest(User $user, Delegation $delegation): bool
    {
        if ($this->manage($user, $delegation->meet)) {
            return true;
        }

        return $user->role === UserRole::DelegationOfficer && $delegation->hasOfficer($user);
    }

    public function viewRequest(User $user, TransportRequest $request): bool
    {
        if ($this->manage($user, $request->meet)) {
            return true;
        }

        return $user->role === UserRole::DelegationOfficer && $request->delegation->hasOfficer($user);
    }

    public function viewTrip(User $user, TransportTrip $trip): bool
    {
        if ($this->manage($user, $trip->meet)) {
            return true;
        }

        return $user->role === UserRole::DelegationOfficer
            && $trip->delegation !== null
            && $trip->delegation->hasOfficer($user);
    }
}
