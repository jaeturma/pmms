<?php

namespace App\Services;

use App\Models\EventResult;
use App\Models\ResultPlacement;

class ResultReportingCompleteness
{
    public function forPlacement(ResultPlacement $placement, ?EventResult $result = null): array
    {
        $event = ($result ?? $placement->result)?->event;
        $delegation = $placement->delegation ?? $placement->entry?->delegation ?? $placement->teamEntry?->delegation;
        $missing = [];
        if ($delegation === null) {
            $missing[] = 'Delegation not linked';
        }
        if ($event?->is_team_event) {
            $players = $placement->reportingAthletes->filter(fn ($athlete) => ! $athlete->trashed());
            $members = $placement->teamEntry?->members ?? collect();
            $minimum = max(1, $event->sportCategory?->min_players ?? $event->team_size ?? 1);
            $usableMembers = $members->filter(fn ($member) => $member->athlete !== null);
            if ($players->count() < $minimum && $usableMembers->count() < $minimum) {
                $missing[] = 'Team roster incomplete';
            }
            if ($placement->reportingCoaches->filter(fn ($coach) => ! $coach->trashed())->isEmpty()) {
                $missing[] = 'Coach not linked';
            }
        } elseif (($placement->athlete === null || $placement->athlete->trashed()) && $placement->entry?->athlete === null) {
            $missing[] = 'Athlete attribution not linked (optional)';
        }

        return ['complete' => $missing === [], 'label' => $missing === [] ? 'Reporting complete' : 'Reporting data incomplete', 'missing' => $missing, 'blocks_operation' => false];
    }
}
