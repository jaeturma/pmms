<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Collection;

class CoachAccessService
{
    /** @return Collection<int, CoachAssignmentRequest> */
    public function assignments(User $coach, ?Delegation $delegation = null): Collection
    {
        return $coach->coachAssignmentRequests()->where('status', 'approved')->whereNull('ended_at')
            ->when($delegation !== null, fn ($query) => $query->where('delegation_id', $delegation->id))
            ->with(['meetSport', 'sportCategory', 'event'])->get();
    }

    /** @return Collection<int, int> */
    public function eventIds(User $coach, ?Delegation $delegation = null): Collection
    {
        $ids = $this->assignments($coach, $delegation)->flatMap(function (CoachAssignmentRequest $assignment): Collection|array {
            if ($assignment->event_id !== null) {
                return [$assignment->event_id];
            }
            $events = Event::query()->where('sport_id', $assignment->meetSport->sport_id)
                ->whereHas('meets', fn ($meets) => $meets->whereKey($assignment->meetSport->meet_id));
            if ($assignment->sport_category_id !== null) {
                $events->where('sport_category_id', $assignment->sport_category_id);
            }

            return $events->pluck('events.id');
        });

        // Compatibility only for registrations approved before assignment
        // rows became authoritative. New sport-only applications never
        // populate this pivot and therefore receive no implicit scope.
        if ($ids->isEmpty() && ($delegation === null || $delegation->hasCoach($coach))) {
            $legacy = $coach->coachOnboardingRequest()->where('status', 'approved')
                ->whereNull('meet_sport_id')->with('events:id')->first();
            $ids = $ids->merge($legacy?->events->modelKeys() ?? []);
        }

        return $ids->filter()->unique()->values();
    }

    public function canAccessEvent(User $coach, Event $event, Delegation $delegation): bool
    {
        return $coach->role === UserRole::Coach
            && $this->eventIds($coach, $delegation)->contains($event->id);
    }

    public function canAccessSport(User $coach, int $sportId, Delegation $delegation): bool
    {
        return Event::query()->whereIn('id', $this->eventIds($coach, $delegation))->where('sport_id', $sportId)->exists();
    }

    public function canAccessCategory(User $coach, int $categoryId, Delegation $delegation): bool
    {
        return Event::query()->whereIn('id', $this->eventIds($coach, $delegation))->where('sport_category_id', $categoryId)->exists();
    }

    public function canManageAthlete(User $coach, Athlete $athlete): bool
    {
        if ($coach->role !== UserRole::Coach || ! $athlete->isOwnedBy($coach)) {
            return false;
        }

        return $this->assignments($coach, $athlete->delegation)->isNotEmpty()
            || ($athlete->delegation->hasCoach($coach) && $this->eventIds($coach, $athlete->delegation)->isNotEmpty());
    }

    /** @return Collection<int, int> */
    public function delegationIds(User $coach): Collection
    {
        return $this->assignments($coach)->pluck('delegation_id')
            ->merge(Personnel::query()->where('user_id', $coach->id)->pluck('delegation_id'))
            ->filter()->unique()->values();
    }
}
