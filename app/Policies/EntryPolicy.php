<?php

namespace App\Policies;

use App\Enums\EntryStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\User;
use App\Services\CompetitionAccessService;

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
        return $user->hasRole(
            UserRole::Admin,
            UserRole::Organizer,
            UserRole::DelegationOfficer,
            UserRole::Coach,
            UserRole::TournamentManager,
        ) || $user->tournamentEventIds()->isNotEmpty();
    }

    /**
     * Managers may submit anytime; officers and coaches for their own
     * delegation while the meet's registration window is open. Unlike
     * roster edits, entries do not require the delegation to still be a
     * draft.
     */
    public function create(User $user, Delegation $delegation, ?Event $event = null): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $access = app(CompetitionAccessService::class);
        if ($access->hasAssignmentRole($user, [MeetSportAssignmentRole::TournamentICT->value], $delegation->meet_id)) {
            return $event === null || $access->canAccessEvent($user, $event, $delegation->meet_id);
        }

        return ($delegation->hasOfficer($user) || ($event !== null && $user->hasApprovedCoachScope($delegation, $event)))
            && $delegation->meet->isRegistrationOpen();
    }

    /**
     * Confirming entries is a manager decision.
     */
    public function confirm(User $user, Entry $entry): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->meetSportAssignments()
            ->where('status', MeetSportAssignmentStatus::Active->value)
            ->whereIn('role', [
                MeetSportAssignmentRole::TournamentManager->value,
                MeetSportAssignmentRole::AssistantTournamentManager->value,
                MeetSportAssignmentRole::TournamentSecretary->value,
                MeetSportAssignmentRole::TournamentICT->value,
            ])
            ->whereHas('meetSport', fn ($meetSport) => $meetSport
                ->where('meet_id', $entry->delegation->meet_id)
                ->where('sport_id', $entry->event->sport_id))
            ->exists();
    }

    /**
     * Managers may withdraw any entry; officers and coaches only their own
     * delegation's still-submitted entries while registration is open.
     */
    public function withdraw(User $user, Entry $entry): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $entry->status === EntryStatus::Submitted
            && ($entry->delegation->hasOfficer($user) || $user->hasApprovedCoachScope($entry->delegation, $entry->event))
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

        if ($user->isAdmin()) {
            return true;
        }

        return ($entry->delegation->hasOfficer($user) || $user->hasApprovedCoachScope($entry->delegation, $entry->event))
            && $entry->delegation->meet->isRegistrationOpen();
    }
}
