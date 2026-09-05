<?php

namespace App\Services;

use App\Models\EventResult;
use App\Models\MedalAward;
use App\Models\ResultPlacement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MedalAwardService
{
    public function synchronize(EventResult $result, User $actor): void
    {
        if ($result->demo_scenario_id !== null) {
            $result->medalAwards()->delete();

            return;
        }

        $result->loadMissing(['event.medalConfig', 'placements.entry.athlete', 'placements.teamEntry', 'placements.delegation']);
        $config = $result->event->resolvedMedalConfig();

        if ($config->awards_medals && ! $config->isComplete() && $result->result_source !== 'direct') {
            throw ValidationException::withMessages([
                'medal_configuration' => __('This medal-producing event needs complete physical and official tally quantities before its result can become official.'),
            ]);
        }

        $result->medalAwards()->delete();
        if (! $config->awards_medals) {
            return;
        }

        $this->logicalMedalPlacements($result->placements->whereIn('rank', [1, 2, 3]), $result->event->is_team_event)
            ->each(function (ResultPlacement $placement) use ($result, $config, $actor): void {
                MedalAward::query()->create([
                    'event_result_id' => $result->id,
                    'result_placement_id' => $placement->id,
                    'delegation_id' => $placement->delegation_id ?? $placement->teamEntry?->delegation_id ?? $placement->entry?->delegation_id,
                    'school_id' => $placement->delegation_id === null && $placement->team_entry_id === null ? $placement->entry?->athlete?->school_id : null,
                    'rank' => $placement->rank,
                    'medal_type' => match ($placement->rank) {
                        1 => 'gold', 2 => 'silver', default => 'bronze'
                    },
                    'physical_quantity' => $config->isComplete() ? $config->physicalQuantityForRank($placement->rank) : 1,
                    'tally_quantity' => $config->isComplete() ? $config->tallyQuantityForRank($placement->rank) : 1,
                    'result_version' => (int) ($result->version ?? 1),
                    'snapshotted_by' => $actor->id,
                    'snapshotted_at' => now(),
                ]);
            });
    }

    /** @param Collection<int, ResultPlacement> $placements */
    private function logicalMedalPlacements(Collection $placements, bool $isTeamEvent): Collection
    {
        return $placements->unique(fn (ResultPlacement $placement): string => $isTeamEvent
            ? 'team:'.$placement->rank.':'.($placement->delegation_id ?? $placement->team_entry_id ?? $placement->entry?->delegation_id)
            : "entry:{$placement->id}")->values();
    }
}
