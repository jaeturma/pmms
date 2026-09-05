<?php

namespace App\Services;

use App\Enums\ResultStatus;
use App\Models\Event;
use App\Models\EventResult;

class EventTeamStandingsService
{
    /** Recompute from accepted records. No incremental counter or roster-derived contribution. */
    public function standings(int $meetId, Event $event): array
    {
        $results = EventResult::query()->real()->where('meet_id', $meetId)->where('event_id', $event->id)
            ->where('result_type', 'versus')->where('status', ResultStatus::Official)
            ->with(['placements.delegation.school', 'placements.delegation.district'])->get();
        $rows = [];
        foreach ($results as $result) {
            $winner = $result->placements->firstWhere('rank', 1);
            $loser = $result->placements->firstWhere('rank', 2);
            if ($winner === null || $loser === null || $winner->delegation === null || $loser->delegation === null || $winner->delegation_id === $loser->delegation_id) {
                continue;
            }
            foreach ([[$winner, $loser, true], [$loser, $winner, false]] as [$participant, $opponent, $won]) {
                $id = $participant->delegation_id;
                $rows[$id] ??= ['delegation_id' => $id, 'team' => $participant->delegation->registrantName(), 'played' => 0, 'wins' => 0, 'losses' => 0];
                $rows[$id]['played']++;
                $rows[$id][$won ? 'wins' : 'losses']++;
            }
        }
        // No universal sport-points or tie-break formula exists in PMMS.
        // Equal win/loss records share a summary rank; names only stabilize display order.
        $rows = collect($rows)->sortBy([['wins', 'desc'], ['losses', 'asc'], ['team', 'asc']])->values();
        $rank = 0;
        $previous = null;
        $rows = $rows->map(function ($row, $index) use (&$rank, &$previous) {
            $record = [$row['wins'], $row['losses']];
            if ($record !== $previous) {
                $rank = $index + 1;
                $previous = $record;
            }

            return ['rank' => $rank, ...$row];
        });

        return ['columns' => ['played' => 'GP', 'wins' => 'W', 'losses' => 'L'], 'rows' => $rows->all(),
            'note' => 'Win/loss summary from accepted versus results. Sport-specific standings points and tie-breaks are not configured.'];
    }
}
