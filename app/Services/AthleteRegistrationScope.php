<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Delegation;
use App\Models\MeetSport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AthleteRegistrationScope
{
    /** @return Collection<int, Delegation> */
    public function delegations(User $coach): Collection
    {
        $delegationIds = app(CoachAccessService::class)->assignments($coach)
            ->pluck('delegation_id')->filter()->unique();

        return Delegation::query()
            ->whereIn('id', $delegationIds)
            ->get();
    }

    /** @return Collection<int, MeetSport> */
    public function meetSports(User $coach, Delegation $delegation): Collection
    {
        $meetSportIds = app(CoachAccessService::class)->assignments($coach, $delegation)
            ->pluck('meet_sport_id')->filter()->unique();

        return MeetSport::query()
            ->where('meet_id', $delegation->meet_id)
            ->whereIn('id', $meetSportIds)
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
