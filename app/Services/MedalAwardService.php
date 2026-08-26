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
        $result->loadMissing(['event.medalConfig', 'placements.entry.athlete', 'placements.teamEntry']);
        $config = $result->event->resolvedMedalConfig();

        if ($config->awards_medals && ! $config->isComplete()) {
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
                    'delegation_id' => $placement->entry->delegation_id,
                    'school_id' => $placement->team_entry_id === null ? $placement->entry->athlete->school_id : null,
                    'rank' => $placement->rank,
                    'medal_type' => match ($placement->rank) { 1 => 'gold', 2 => 'silver', default => 'bronze' },
                    'physical_quantity' => $config->physicalQuantityForRank($placement->rank),
                    'tally_quantity' => $config->tallyQuantityForRank($placement->rank),
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
            ? 'team:'.$placement->rank.':'.($placement->team_entry_id ?? $placement->entry->delegation_id)
            : "entry:{$placement->id}")->values();
    }
}
