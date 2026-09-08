<?php

namespace App\Services;

use App\Models\EventResult;
use App\Models\Meet;
use App\Models\MeetSportAssignment;

/** Read-only accepted award report; deliberately includes tally-excluded sports. */
class SportsMedalAwardsReport
{
    public function build(Meet $meet, ?int $sportId, ?int $delegationId = null): array
    {
        $results = app(PublicEventResults::class)->withMedals(EventResult::query())
            ->where('meet_id', $meet->id)
            ->whereHas('medalAwards', fn ($awards) => $awards->where('tally_quantity', '>', 0)
                ->whereColumn('medal_awards.result_version', 'event_results.version')
                ->when($delegationId !== null, fn ($query) => $query->where('delegation_id', $delegationId)))
            ->where(fn ($query) => $query->whereNull('match_id')->orWhereHas('match', fn ($match) => $match->where('awards_medals', true)))
            ->when($sportId !== null, fn ($query) => $query->whereHas('event', fn ($event) => $event->where('sport_id', $sportId)))
            ->with(['medalAwards' => fn ($awards) => $awards->when($delegationId !== null, fn ($query) => $query->where('delegation_id', $delegationId)),
                'event.sport', 'medalAwards.delegation.school', 'medalAwards.delegation.district',
                'medalAwards.placement.athlete', 'medalAwards.placement.entry.athlete',
                'medalAwards.placement.reportingAthletes', 'medalAwards.placement.reportingCoaches',
                'medalAwards.placement.delegation.school', 'medalAwards.placement.delegation.district',
                'medalAwards.placement.entry.delegation.school', 'medalAwards.placement.entry.delegation.district',
                'medalAwards.placement.teamEntry.delegation.school', 'medalAwards.placement.teamEntry.delegation.district'])
            ->orderBy('id')->get();

        $staff = MeetSportAssignment::query()->where('status', 'active')
            ->whereHas('meetSport', fn ($scope) => $scope->where('meet_id', $meet->id)
                ->when($sportId !== null, fn ($q) => $q->where('sport_id', $sportId)))
            ->whereIn('role', ['tournament_secretary', 'tournament_manager'])
            ->with(['meetSport', 'person', 'user'])->orderByDesc('is_lead')->orderBy('id')->get();

        return $results->groupBy(fn ($result) => $result->event?->sport_id ?? 'unknown')
            ->map(function ($group, $id) use ($staff): array {
                $signatories = collect(['Prepared by' => 'tournament_secretary', 'Recommended by' => 'tournament_manager'])
                    ->map(function ($role, $label) use ($staff, $id): array {
                        $member = $staff->first(fn ($row) => (string) $row->meetSport?->sport_id === (string) $id && $row->role->value === $role);
                        $name = $member?->person?->full_name ?? $member?->user?->name;

                        return ['label' => $label, 'name' => $name, 'position' => $name ? ($member->original_designation ?: $member->role->label()) : null];
                    })->values()->all();

                return [
                    'id' => $id, 'name' => $group->first()->event?->sport?->name,
                    'events' => $group->map(function ($result): array {
                        return ['id' => $result->id, 'name' => $result->event?->name,
                            'division' => $result->event?->age_division?->label(),
                            'gender' => $result->event?->gender?->label(),
                            'awards' => $result->medalAwards->filter(fn ($award) => $award->tally_quantity > 0
                                && (int) $award->result_version === (int) $result->version)
                                ->sortBy('rank')->map(function ($award): array {
                                    $placement = $award->placement;
                                    $attribution = $placement ? app(ResultAttributionService::class)->report($placement) : null;
                                    // Use saved attribution, never reconstruct historical teams from mutable rosters.
                                    $athletes = $attribution['players'] ?? [];
                                    if ($athletes === []) {
                                        $name = $attribution['athlete_name'] ?? $placement?->entry?->athlete?->fullName();
                                        $athletes = $name ? [$name] : [];
                                    }
                                    $delegation = $award->delegation ?? $placement?->delegation
                                        ?? $placement?->teamEntry?->delegation ?? $placement?->entry?->delegation;

                                    return ['id' => $award->id, 'medal' => $award->medal_type,
                                        'tally_count' => $award->tally_quantity, 'physical_count' => $award->physical_quantity,
                                        'athletes' => $athletes, 'team' => $delegation?->registrantName(),
                                        'coaches' => $attribution['coaches'] ?? [],
                                        'mark' => $placement?->mark ?? $placement?->result_value];
                                })->values()->all()];
                    })->sortBy('name')->values()->all(),
                    'signatories' => $signatories,
                ];
            })->sortBy('name')->values()->all();
    }
}
