<?php

namespace App\Services;

use App\Models\EventMedalConfig;
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
        if ($result->result_type === 'versus') {
            return;
        }
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

        // A Direct Event Result stores one canonical award per submitted
        // medal row. Each row stands on its own — never collapsed because
        // its medal type or delegation repeats — so two identical
        // "Gold → Compostela → 1" rows contribute Gold +2.
        if ($result->result_source === 'direct') {
            $this->synchronizeDirectRows($result, $config, $actor);

            return;
        }

        $this->logicalMedalPlacements($result->placements->whereIn('rank', [1, 2, 3]), $result->event->is_team_event)
            ->each(function (ResultPlacement $placement) use ($result, $config, $actor): void {
                $medal = match ($placement->rank) {
                    1 => 'gold', 2 => 'silver', default => 'bronze'
                };
                $quantity = $result->result_source === 'direct'
                    ? ($placement->tally_quantity ?? ($config->isComplete() ? $config->tallyQuantityForRank($placement->rank) : 1))
                    : $config->tallyQuantityForRank($placement->rank);
                if ($quantity === 0 && $result->result_source === 'direct') {
                    return;
                }
                MedalAward::query()->create([
                    'event_result_id' => $result->id,
                    'result_placement_id' => $placement->id,
                    'delegation_id' => $placement->delegation_id ?? $placement->teamEntry?->delegation_id ?? $placement->entry?->delegation_id,
                    'school_id' => $placement->delegation_id === null && $placement->team_entry_id === null ? $placement->entry?->athlete?->school_id : null,
                    'rank' => $placement->rank,
                    'medal_type' => $medal,
                    'physical_quantity' => $config->{$medal.'_physical_quantity'} ?? 1,
                    'tally_quantity' => $quantity,
                    'result_version' => (int) ($result->version ?? 1),
                    'snapshotted_by' => $actor->id,
                    'snapshotted_at' => now(),
                ]);
            });
    }

    /**
     * One MedalAward per submitted Direct Result row, keyed by its own
     * placement id. Zero-count rows contribute nothing. Idempotent: the
     * caller has already cleared the result's awards, so a repeated accept
     * simply rebuilds the identical set.
     */
    private function synchronizeDirectRows(EventResult $result, EventMedalConfig $config, User $actor): void
    {
        $result->placements->each(function (ResultPlacement $placement) use ($result, $config, $actor): void {
            $delegationId = $placement->delegation_id
                ?? $placement->teamEntry?->delegation_id
                ?? $placement->entry?->delegation_id;
            $medal = $placement->medal_type ?? match ($placement->rank) {
                1 => 'gold', 2 => 'silver', default => 'bronze',
            };
            // A submitted row always carries an explicit count. Only a
            // legacy row with a null quantity falls back to the event's
            // configured tally for its rank.
            $quantity = $placement->tally_quantity !== null
                ? (int) $placement->tally_quantity
                : ($config->isComplete() ? $config->tallyQuantityForRank($placement->rank) : 1);

            if ($quantity <= 0 || $delegationId === null || ! in_array($medal, ['gold', 'silver', 'bronze'], true)) {
                return;
            }

            MedalAward::query()->create([
                'event_result_id' => $result->id,
                'result_placement_id' => $placement->id,
                'delegation_id' => $delegationId,
                'school_id' => null,
                'rank' => $placement->rank,
                'medal_type' => $medal,
                'physical_quantity' => $config->{$medal.'_physical_quantity'} ?? 1,
                'tally_quantity' => $quantity,
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
