<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\MeetSport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AthleteRegistrationScope
{
    /** @return Collection<int, Delegation> */
    public function delegations(User $coach): Collection
    {
        $access = app(CoachAccessService::class);
        $delegationIds = $access->delegationIds($coach);

        return Delegation::query()
            ->whereIn('id', $delegationIds)
            ->get()
            // Older approved onboarding records have event scope plus a
            // Personnel link instead of CoachAssignmentRequest rows.
            ->filter(fn (Delegation $delegation): bool => $access->eventIds($coach, $delegation)->isNotEmpty())
            ->values();
    }

    /** @return Collection<int, MeetSport> */
    public function meetSports(User $coach, Delegation $delegation): Collection
    {
        $access = app(CoachAccessService::class);
        $meetSportIds = $access->assignments($coach, $delegation)
            ->pluck('meet_sport_id')->filter()->unique();
        $legacySportIds = Event::query()
            ->whereIn('id', $access->eventIds($coach, $delegation))
            ->pluck('sport_id')->unique();

        return MeetSport::query()
            ->where('meet_id', $delegation->meet_id)
            ->where(fn ($query) => $query
                ->whereIn('id', $meetSportIds)
                ->orWhereIn('sport_id', $legacySportIds))
            ->with('sport:id,name')
            ->get()
            ->unique('sport_id')
            ->values();
    }

    public function resolveDelegation(User $coach): Delegation
    {
        if ($coach->role !== UserRole::Coach) {
            throw ValidationException::withMessages(['registered_by' => __('An approved Coach is required.')]);
        }

        $delegations = $this->delegations($coach);
        if ($delegations->count() !== 1) {
            throw ValidationException::withMessages([
                'delegation_id' => $delegations->isEmpty()
                    ? __('Your Coach account has no active approved Delegation assignment.')
                    : __('Your Coach account has conflicting active Delegation assignments. Ask ICT to correct your assignment.'),
            ]);
        }

        return $delegations->first();
    }

    public function resolveMeetSport(User $coach, Delegation $delegation, ?int $selectedMeetSportId): MeetSport
    {
        $meetSports = $this->meetSports($coach, $delegation);
        if ($meetSports->isEmpty()) {
            throw ValidationException::withMessages(['meet_sport_id' => __('Your Coach account has no approved Sport assignment for this Delegation.')]);
        }

        if ($meetSports->count() === 1) {
            if ($selectedMeetSportId !== null && $selectedMeetSportId !== $meetSports->first()->id) {
                throw ValidationException::withMessages(['meet_sport_id' => __('The selected Sport is outside your approved Coach scope.')]);
            }

            return $meetSports->first();
        }

        $selected = $meetSports->firstWhere('id', $selectedMeetSportId);
        if ($selected === null) {
            throw ValidationException::withMessages(['meet_sport_id' => __('Select one of your approved Sports for this Athlete.')]);
        }

        return $selected;
    }
}
