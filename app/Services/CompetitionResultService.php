<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\ResultStatus;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\TeamEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetitionResultService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function createManual(EventMatch $match, array $placements, User $user): EventResult
    {
        $this->assertReady($match);
        $this->assertUnique($match);
        $result = DB::transaction(function () use ($match, $placements, $user): EventResult {
            $result = $this->newResult($match, $user, 'manual');
            foreach ($placements as $placement) {
                $result->placements()->create($this->withTeamSnapshot($placement));
            }

            return $result;
        });
        $this->audit->record('result.manually_entered', $result, ['match_id' => $match->id]);

        return $result;
    }

    public function createFinalEventResult(Meet $meet, Event $event, ?EventSchedule $schedule, array $placements, User $user): EventResult
    {
        if (EventResult::query()->where('meet_id', $meet->id)->where('event_id', $event->id)->where('result_scope', 'event')->exists()) {
            throw ValidationException::withMessages(['event_id' => __('This Sports Event already has a final result.')]);
        }

        $result = DB::transaction(function () use ($meet, $event, $schedule, $placements, $user): EventResult {
            $result = new EventResult([
                'meet_id' => $meet->id,
                'event_id' => $event->id,
                'event_schedule_id' => $schedule?->id,
                'result_source' => $schedule === null ? 'manual' : 'schedule',
                'result_scope' => 'event',
            ]);
            $result->forceFill(['status' => ResultStatus::Encoded, 'encoded_by' => $user->id, 'encoded_at' => now()])->save();
            foreach ($placements as $placement) {
                $result->placements()->create($this->withTeamSnapshot($placement));
            }

            return $result;
        });
        $this->audit->record($schedule === null ? 'event_result.manually_entered' : 'event_result.schedule_entered', $result, [
            'event_id' => $event->id,
            'event_schedule_id' => $schedule?->id,
        ]);

        return $result;
    }

    public function createFromLiveScore(ScoringSession $session, User $user): EventResult
    {
        $session->loadMissing('match.event', 'match.entries', 'match.teamEntries.members');
        $match = $session->match;
        $this->assertCompleted($match);
        if ($match->result !== null) {
            return $match->result;
        }
        $participants = $match->event->is_team_event
            ? $match->teamEntries->values()
            : $match->entries->values();
        if ($participants->count() !== 2) {
            $result = DB::transaction(fn (): EventResult => $this->newResult($match, $user, 'live_score', $session));
            $this->audit->record('result.created_from_live_score', $result, ['match_id' => $match->id, 'scoring_session_id' => $session->id, 'placements_require_review' => true]);

            return $result;
        }
        $tie = $session->score_a === $session->score_b;
        $ranks = $tie ? [1, 1] : ($session->score_a > $session->score_b ? [1, 2] : [2, 1]);
        $mark = "{$session->score_a}-{$session->score_b}";
        $result = DB::transaction(function () use ($match, $session, $user, $participants, $ranks, $mark, $tie): EventResult {
            $result = $this->newResult($match, $user, 'live_score', $session);
            foreach ($participants as $index => $participant) {
                $placement = ['rank' => $ranks[$index], 'mark' => $mark, 'is_tie' => $tie];
                if ($match->event->is_team_event) {
                    $placement['team_entry_id'] = $participant->id;
                    $placement['entry_id'] = $participant->members->first()?->entry_id;
                } else {
                    $placement['entry_id'] = $participant->id;
                }
                $result->placements()->create($this->withTeamSnapshot($placement));
            }

            return $result;
        });
        $this->audit->record('result.created_from_live_score', $result, ['match_id' => $match->id, 'scoring_session_id' => $session->id]);

        return $result;
    }

    public function createFromManualOutcome(EventMatch $match, User $user): EventResult
    {
        $this->assertCompleted($match);
        $this->assertUnique($match);
        if (EventResult::query()->where('meet_id', $match->meet_id)->where('event_id', $match->event_id)
            ->where('result_scope', 'event')->exists()) {
            throw ValidationException::withMessages(['match_id' => __('This Sports Event already has a final result.')]);
        }
        $delegationIds = $match->participantSlots()->where('is_selected', true)->pluck('delegation_id')
            ->merge($match->teamEntries()->pluck('delegation_id'))
            ->merge($match->entries()->pluck('delegation_id'))->unique()->values();
        if ($match->winner_delegation_id === null || ! $delegationIds->contains($match->winner_delegation_id)) {
            throw ValidationException::withMessages(['winner_delegation_id' => __('The completed match needs a valid winner.')]);
        }

        $result = DB::transaction(function () use ($match, $user, $delegationIds): EventResult {
            $result = $this->newResult($match, $user, 'manual');
            $result->forceFill(['result_scope' => 'event'])->save();
            $nextLoserRank = 2;
            foreach ($delegationIds as $delegationId) {
                $isWinner = (int) $delegationId === (int) $match->winner_delegation_id;
                $result->placements()->create([
                    'delegation_id' => $delegationId,
                    'rank' => $isWinner ? 1 : $nextLoserRank++,
                    'mark' => collect([$match->manual_score_a, $match->manual_score_b])
                        ->filter(fn ($score) => $score !== null && $score !== '')->implode(' - ') ?: null,
                    'is_tie' => false,
                ]);
            }

            return $result;
        });
        $this->audit->record('result.created_from_manual_match', $result, [
            'match_id' => $match->id,
            'delegations' => $delegationIds->all(),
            'winner_delegation_id' => $match->winner_delegation_id,
            'scores' => [$match->manual_score_a, $match->manual_score_b],
        ]);

        return $result;
    }

    public function assertReady(EventMatch $match): void
    {
        $this->assertCompleted($match);
    }

    private function assertCompleted(EventMatch $match): void
    {
        if (! in_array($match->status, [MatchStatus::Completed, MatchStatus::Walkover], true)) {
            throw ValidationException::withMessages(['match_id' => __('The competition must be completed before entering a result.')]);
        }
    }

    private function assertUnique(EventMatch $match): void
    {
        if ($match->result()->exists()) {
            throw ValidationException::withMessages(['match_id' => __('This competition already has a result.')]);
        }
    }

    private function newResult(EventMatch $match, User $user, string $source, ?ScoringSession $session = null): EventResult
    {
        $result = new EventResult(['meet_id' => $match->meet_id, 'event_id' => $match->event_id, 'match_id' => $match->id, 'event_schedule_id' => $match->event_schedule_id, 'scoring_session_id' => $session?->id, 'result_source' => $source, 'result_scope' => 'match']);
        $result->forceFill(['status' => ResultStatus::Encoded, 'encoded_by' => $user->id, 'encoded_at' => now()])->save();

        return $result;
    }

    private function withTeamSnapshot(array $placement): array
    {
        if (! empty($placement['team_entry_id'])) {
            $placement['entry_id'] ??= TeamEntry::query()
                ->find($placement['team_entry_id'])?->members()
                ->value('entry_id');

            return $placement;
        }

        $entry = Entry::query()->with('event')->find($placement['entry_id']);
        if ($entry?->event?->is_team_event) {
            $placement['team_entry_id'] = $entry->teamMemberships()
                ->whereHas('teamEntry', fn ($team) => $team->where('status', 'confirmed'))
                ->value('team_entry_id');
        }

        return $placement;
    }
}
