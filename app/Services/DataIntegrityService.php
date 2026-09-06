<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\MatchParticipantSlot;
use App\Models\ResultPlacement;
use App\Models\SportRosterMember;
use Illuminate\Support\Collection;

/** Read-only diagnostics. No repair, import, or backfill is performed by a scan. */
class DataIntegrityService
{
    public function scan(?int $meetId = null): Collection
    {
        $issues = collect();
        $add = function (string $type, $source, string $missing, string $action, bool $blocks = false, $scope = null, $delegation = null, $event = null, ?string $url = null) use ($issues, $meetId): void {
            $recordMeet = $scope?->meet_id ?? $source->meet_id ?? $delegation?->meet_id;
            if ($meetId !== null && $recordMeet !== null && $recordMeet !== $meetId) {
                return;
            }
            $issues->push([
                'key' => $source->getTable().':'.$source->id.':'.$type,
                'type' => $type, 'source_type' => class_basename($source), 'source_id' => $source->id,
                'meet_id' => $recordMeet, 'sport_id' => $scope?->sport_id ?? $event?->sport_id,
                'sport' => $scope?->sport?->name ?? $event?->sport?->name ?? 'Not linked',
                'event' => $event?->name ?? 'Not linked',
                'delegation' => $delegation?->registrantName() ?? 'Not linked',
                'relationship' => $missing, 'severity' => $blocks ? 'error' : 'warning',
                'blocks_operation' => $blocks, 'suggested_action' => $action,
                'repair_url' => $url,
            ]);
        };

        foreach (SportRosterMember::with(['athlete', 'delegation.school', 'delegation.district', 'meetSport.sport'])->lazyById(300) as $row) {
            $args = [$row->meetSport, $row->delegation, null];
            if ($row->athlete === null) {
                $add('orphan_roster', $row, 'Athlete #'.$row->athlete_id.' is missing or archived', 'Search and link an existing athlete, or explicitly create and link.', true, ...[...$args, '/administration/data-integrity/rosters/'.$row->id]);
            } elseif ($row->athlete->delegation_id !== $row->delegation_id) {
                $add('cross_delegation_roster', $row, 'Athlete belongs to a different delegation', 'Review the delegation and explicitly relink the athlete.', true, ...[...$args, '/administration/data-integrity/rosters/'.$row->id]);
            }
            if ($row->meetSport === null || $row->meetSport->sport === null || $row->delegation === null || $row->meetSport->meet_id !== $row->delegation->meet_id) {
                $add('broken_roster_scope', $row, 'Sport/meet/delegation missing or inconsistent', 'Review canonical sport and delegation before linking.', true, ...$args);
            }
        }
        foreach (Athlete::with(['delegation.school', 'delegation.district', 'school', 'sportRosterMemberships.meetSport.sport'])->lazyById(300) as $athlete) {
            $scope = $athlete->sportRosterMemberships->first()?->meetSport;
            if ($athlete->delegation === null) {
                $add('athlete_without_delegation', $athlete, 'Delegation unavailable', 'Review athlete registration and link the canonical delegation.', true, $scope, null, null, '/athletes/'.$athlete->id.'/edit');
            }
            if ($athlete->sportRosterMemberships->isEmpty()) {
                $add('athlete_without_roster', $athlete, 'No sport roster', 'Assign a sport roster in Athlete registration; do not fabricate an Event Entry.', false, null, $athlete->delegation, null, '/athletes/'.$athlete->id.'/edit');
            }
            if ($athlete->school === null) {
                $add('athlete_without_school', $athlete, 'School not linked', 'Review the athlete school.', false, $scope, $athlete->delegation, null, '/athletes/'.$athlete->id.'/edit');
            }
        }
        foreach (Entry::with(['athlete', 'event.sport', 'delegation.school', 'delegation.district'])->lazyById(300) as $entry) {
            if ($entry->athlete === null || $entry->event === null || $entry->delegation === null || $entry->athlete->delegation_id !== $entry->delegation_id) {
                $add('orphan_event_entry', $entry, 'Athlete/event/delegation missing or inconsistent', 'Review the original Event Entry and registration.', true, null, $entry->delegation, $entry->event, '/entries');
            }
        }
        foreach (MatchParticipantSlot::with(['match.event.sport', 'entry.athlete', 'delegation.school', 'delegation.district'])->lazyById(300) as $slot) {
            if ($slot->match === null || $slot->delegation === null || ($slot->entry_id !== null && ($slot->entry === null || $slot->entry->athlete === null || $slot->entry->delegation_id !== $slot->delegation_id || $slot->entry->event_id !== $slot->match?->event_id))) {
                $add('broken_match_participant', $slot, 'Participant link missing or inconsistent', 'Review and explicitly select a valid participant in Matches.', false, $slot->match, $slot->delegation, $slot->match?->event, '/matches');
            }
        }
        foreach (EventMatch::with(['event.sport', 'entries.athlete', 'entries.delegation', 'teamEntries.delegation'])->lazyById(300) as $match) {
            if ($match->event === null || $match->entries->contains(fn ($entry) => $entry->athlete === null || $entry->delegation === null) || $match->teamEntries->contains(fn ($team) => $team->delegation === null)) {
                $add('broken_match_links', $match, 'Event or participant record unavailable', 'Review Match participants; keep historical scores.', false, null, null, $match->event, '/matches');
            }
        }
        foreach (EventResult::with(['event.sport', 'match', 'schedule'])->lazyById(300) as $result) {
            if (($result->match_id !== null && $result->match === null) || $result->event === null) {
                $add('broken_result_link', $result, 'Referenced match/event unavailable', 'Review the result source; preserve the accepted result.', false, null, null, $result->event, '/results');
            }
            if ($result->event_schedule_id !== null && $result->schedule === null) {
                $add('missing_result_schedule', $result, 'Referenced schedule unavailable (optional)', 'Review schedule history; reporting can continue.', false, null, null, $result->event, '/results');
            }
        }
        foreach (ResultPlacement::with(['result.event.sport', 'athlete', 'entry.athlete', 'entry.delegation', 'teamEntry.members.athlete', 'teamEntry.delegation', 'delegation.school', 'delegation.district', 'reportingAthletes', 'reportingCoaches'])->lazyById(300) as $placement) {
            $result = $placement->result;
            $delegation = $placement->delegation ?? $placement->entry?->delegation ?? $placement->teamEntry?->delegation;
            if ($result === null) {
                $add('orphan_result_placement', $placement, 'Result unavailable', 'Review historical result source.', false, null, $delegation);

                continue;
            }
            $status = app(ResultReportingCompleteness::class)->forPlacement($placement);
            if (! $status['complete']) {
                $type = $result->event?->is_team_event ? 'incomplete_team_result' : 'missing_result_attribution';
                $add($type, $placement, implode('; ', $status['missing']), 'Manage Athlete and coach links on the result.', false, $result, $delegation, $result->event, '/results');
            }
            if (($placement->athlete !== null && $placement->athlete->delegation_id !== $delegation?->id) || $placement->reportingAthletes->contains(fn ($athlete) => $athlete->delegation_id !== $delegation?->id)) {
                $add('cross_delegation_attribution', $placement, 'Linked athlete belongs to another delegation', 'Review result attribution; do not change medal standings.', false, $result, $delegation, $result->event, '/results');
            }
        }
        foreach (CoachAssignmentRequest::where('status', 'approved')->whereNull('ended_at')->with(['user', 'meetSport.sport', 'event.sport', 'delegation.school', 'delegation.district'])->lazyById(300) as $assignment) {
            if ($assignment->user === null || $assignment->delegation === null || $assignment->meetSport === null || ($assignment->event_id !== null && $assignment->event === null) || $assignment->meetSport->meet_id !== $assignment->delegation->meet_id || ($assignment->event !== null && $assignment->event->sport_id !== $assignment->meetSport->sport_id)) {
                $add('stale_coach_assignment', $assignment, 'Coach or scope unavailable/inconsistent', 'Review or end the stale assignment.', false, $assignment->meetSport, $assignment->delegation, $assignment->event, '/coach/assignment-requests');
            }
        }
        foreach (Delegation::with(['school', 'district', 'meet'])->lazyById(300) as $delegation) {
            if ($delegation->meet === null || ($delegation->school === null && $delegation->district === null)) {
                $add('broken_delegation', $delegation, 'Meet or registering unit unavailable', 'Review canonical delegation records.', true, null, $delegation, null, '/delegations');
            }
        }

        return $issues;
    }
}
