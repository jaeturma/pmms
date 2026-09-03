<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\ResultStatus;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\Event;
use App\Models\Meet;
use App\Models\EventResult;
use App\Models\ScoringSession;
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

    public function createFinalEventResult(Meet $meet, Event $event, array $placements, User $user): EventResult
    {
        if (EventResult::query()->where('meet_id', $meet->id)->where('event_id', $event->id)->where('result_scope', 'event')->exists()) {
            throw ValidationException::withMessages(['event_id' => __('This Sports Event already has a final result.')]);
        }

        $result = DB::transaction(function () use ($meet, $event, $placements, $user): EventResult {
            $result = new EventResult([
                'meet_id' => $meet->id,
                'event_id' => $event->id,
                'result_source' => 'manual',
                'result_scope' => 'event',
            ]);
            $result->forceFill(['status' => ResultStatus::Encoded, 'encoded_by' => $user->id, 'encoded_at' => now()])->save();
            foreach ($placements as $placement) {
                $result->placements()->create($this->withTeamSnapshot($placement));
            }

            return $result;
        });
        $this->audit->record('event_result.manually_entered', $result, ['event_id' => $event->id]);

        return $result;
    }

    public function createFromLiveScore(ScoringSession $session, User $user): EventResult
    {
        $session->loadMissing('match.entries');
        $match = $session->match;
        $this->assertCompleted($match);
        if ($match->result !== null) {
            return $match->result;
        }
        $entries = $match->entries->values();
        if ($entries->count() !== 2) {
            $result = DB::transaction(fn (): EventResult => $this->newResult($match, $user, 'live_score', $session));
            $this->audit->record('result.created_from_live_score', $result, ['match_id' => $match->id, 'scoring_session_id' => $session->id, 'placements_require_review' => true]);

            return $result;
        }
        $tie = $session->score_a === $session->score_b;
        $ranks = $tie ? [1, 1] : ($session->score_a > $session->score_b ? [1, 2] : [2, 1]);
        $mark = "{$session->score_a}-{$session->score_b}";
        $result = DB::transaction(function () use ($match, $session, $user, $entries, $ranks, $mark, $tie): EventResult {
            $result = $this->newResult($match, $user, 'live_score', $session);
            foreach ($entries as $index => $entry) {
                $result->placements()->create($this->withTeamSnapshot(['entry_id' => $entry->id, 'rank' => $ranks[$index], 'mark' => $mark, 'is_tie' => $tie]));
            }

            return $result;
        });
        $this->audit->record('result.created_from_live_score', $result, ['match_id' => $match->id, 'scoring_session_id' => $session->id]);

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
        $entry = Entry::query()->with('event')->find($placement['entry_id']);
        if ($entry?->event?->is_team_event) {
            $placement['team_entry_id'] = $entry->teamMemberships()
                ->whereHas('teamEntry', fn ($team) => $team->where('status', 'confirmed'))
                ->value('team_entry_id');
        }

        return $placement;
    }
}
