<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\MatchStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\ScoreboardType;
use App\Enums\ScoreEventType;
use App\Enums\ScoringSessionStatus;
use App\Enums\UserRole;
use App\Events\ScoreUpdated;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\MatchRosterPlayer;
use App\Models\MeetSportAssignment;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Live, provisional scoring for a match (Phase 7). This never creates,
 * updates, or implies an EventResult/ResultPlacement — the only path to
 * an official result is still Phase 3's encode->validate flow, completely
 * untouched by this controller. Viewing/authorization mirrors the
 * "Matches — list" rule (docs/authorization.md): Admin/Organizer any
 * match, Delegation Officer their own delegation's matches only, Viewer
 * never. Mutations are Admin (any match), a Technical Official scoped to
 * their own assigned sport, or an Organizer specifically assigned as
 * Tournament Secretary/ICT for the match's meet+sport (see `canManage()`)
 * — a plain Organizer, and a Technical Official/Tournament Secretary/ICT
 * outside their own assignment, gain no scoreboard access.
 */
class ScoringSessionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Current (most recent) scoring session for a match, for the
     * interactive display and for polling — works whether or not Reverb
     * is running.
     */
    public function show(Request $request, EventMatch $match): JsonResponse
    {
        $this->authorizeView($request, $match);

        $session = $match->scoringSessions()->latest('id')->first();

        return response()->json([
            'session' => $session === null ? null : $session->toLivePayload(),
        ]);
    }

    /**
     * The scoreboard page — operator console for Admin/Organizer, read-only
     * live display for everyone else who could already view this match.
     * Initial session state comes from this Inertia response; subsequent
     * updates come from polling scoring.show and/or the Reverb channel.
     */
    public function board(Request $request, EventMatch $match): Response
    {
        $this->authorizeView($request, $match);

        /** @var User $user */
        $user = $request->user();

        $match->loadMissing([
            'meet:id,name',
            'event.sport:id,name',
            'entries.athlete:id,first_name,last_name,school_id,photo_upload_id',
            'entries.athlete.school:id,name',
            'schedule.venue:id,name',
        ]);

        $session = $match->scoringSessions()->latest('id')->first();
        $entries = $match->entries;

        return Inertia::render('scoring/show', [
            'match' => [
                'id' => $match->id,
                'meet' => $match->meet->name,
                'event' => sprintf('%s — %s', $match->event->sport->name, $match->event->name),
                'sport' => $match->event->sport->name,
                'category' => sprintf('%s %s', $match->event->gender->label(), $match->event->age_division->label()),
                'round_label' => $match->round_label,
                'venue' => $match->schedule?->venue?->name,
                'scheduled_date' => $match->schedule?->scheduled_date?->format('M j, Y'),
                'status' => $match->status->value,
                'is_scheduled' => $match->status === MatchStatus::Scheduled,
            ],
            'suggestedLabels' => $entries->count() === 2 ? [
                $entries[0]->athlete->school->name,
                $entries[1]->athlete->school->name,
            ] : [null, null],
            'suggestedBoardType' => ScoreboardType::forSport($match->event->sport->name)->value,
            'session' => $session === null ? null : $session->toLivePayload(),
            'channel' => "match.{$match->id}.scoring",
            'canManage' => $this->canManage($user, $match),
            'participants' => $this->matchParticipants($entries),
            'roster' => $this->rosterPayload($match),
            'eligibleAthletes' => $this->eligibleAthletes($match, $entries),
        ]);
    }

    /**
     * Start a new session for a scheduled match. Only one active
     * (non-ended) session per match is allowed.
     */
    public function store(Request $request, EventMatch $match): RedirectResponse
    {
        $this->authorizeManage($request, $match);

        if ($match->status !== MatchStatus::Scheduled) {
            throw ValidationException::withMessages([
                'match_id' => __('Live scoring can only start for a scheduled match.'),
            ]);
        }

        if ($match->scoringSessions()->where('status', '!=', ScoringSessionStatus::Ended->value)->exists()) {
            throw ValidationException::withMessages([
                'match_id' => __('This match already has an active scoring session.'),
            ]);
        }

        $data = $request->validate([
            'side_a_label' => ['required', 'string', 'max:255'],
            'side_b_label' => ['required', 'string', 'max:255'],
            'board_type' => ['nullable', 'string', Rule::in([ScoreboardType::Generic->value])],
        ]);

        /** @var User $user */
        $user = $request->user();

        $session = ScoringSession::create([
            'match_id' => $match->id,
            'side_a_label' => $data['side_a_label'],
            'side_b_label' => $data['side_b_label'],
        ]);

        // 'status'/'started_by'/'started_at' are guarded (not in
        // #[Fillable]) — state transitions only happen through
        // forceFill(), the same convention pause()/resume()/end() below
        // already use. `status` happens to also have a matching DB
        // default ('in_progress'), which is the only reason a session
        // has ever looked "started" correctly without this — started_at/
        // started_by were silently never persisted before this fix.
        $session->forceFill([
            'status' => ScoringSessionStatus::InProgress,
            'started_by' => $user->id,
            'started_at' => now(),
        ])->save();

        if (($data['board_type'] ?? null) === ScoreboardType::Generic->value) {
            $session->forceFill(['board_type_override' => ScoreboardType::Generic])->save();
        }

        $initialSportState = match ($session->boardType()) {
            ScoreboardType::Basketball => [
                'fouls_a' => 0, 'fouls_b' => 0,
                'on_court_a' => [], 'on_court_b' => [],
                'possession' => null,
                'player_points' => [], 'player_fouls' => [],
                'game_clock_seconds' => 600, 'game_clock_updated_at' => null,
                'shot_clock_seconds' => 24, 'shot_clock_updated_at' => null,
                'minutes_per_period' => 10, 'shot_clock_duration' => 24,
                'team_color_a' => '#dc2626', 'team_color_b' => '#2563eb',
                'horn_sounded_at' => null,
            ],
            ScoreboardType::Boxing => ['rounds' => []],
            ScoreboardType::SoftballBaseball => [
                'inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => [],
            ],
            default => null,
        };

        if ($initialSportState !== null) {
            $session->forceFill(['sport_state' => $initialSportState])->save();
        }

        $this->audit->record('scoring.started', $session, [...$this->context($session), 'board_type' => $session->boardType()->value]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Live scoring started.')]);

        return back();
    }

    /**
     * Record a point or a correction. Corrections require a reason.
     */
    public function score(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);

        $data = $request->validate([
            'type' => ['required', Rule::in([ScoreEventType::Point->value, ScoreEventType::Correction->value])],
            'side' => ['required', Rule::in(['a', 'b'])],
            'delta' => ['required', 'integer'],
            'reason' => ['required_if:type,'.ScoreEventType::Correction->value, 'nullable', 'string', 'max:500'],
            'roster_player_id' => ['nullable', 'integer'],
        ]);

        $column = $data['side'] === 'a' ? 'score_a' : 'score_b';
        $newValue = max(0, $session->{$column} + $data['delta']);

        $state = $session->sport_state;
        $rosterPlayer = null;

        if (($data['roster_player_id'] ?? null) !== null && $session->boardType() === ScoreboardType::Basketball) {
            $rosterPlayer = $this->rosterPlayerForSide($session, $data['roster_player_id'], $data['side']);

            if ($rosterPlayer !== null && $data['type'] === ScoreEventType::Point->value) {
                $state ??= ['player_points' => []];
                $id = (string) $rosterPlayer->id;
                $state['player_points'][$id] = max(0, ($state['player_points'][$id] ?? 0) + $data['delta']);
            }
        }

        $session->forceFill([
            $column => $newValue,
            ...($state !== null ? ['sport_state' => $state] : []),
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => $data['type'],
            'payload' => [
                'side' => $data['side'],
                'delta' => $data['delta'],
                'reason' => $data['reason'] ?? null,
                'result' => $newValue,
                ...($rosterPlayer !== null ? [
                    'roster_player_id' => $rosterPlayer->id,
                    'player_name' => $rosterPlayer->entry->athlete->fullName(),
                ] : []),
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record(
            $data['type'] === ScoreEventType::Correction->value ? 'scoring.corrected' : 'scoring.scored',
            $session,
            [...$this->context($session), 'side' => $data['side'], 'delta' => $data['delta'], 'reason' => $data['reason'] ?? null],
        );

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Update the period/round label and/or a free-text status note.
     */
    public function period(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);

        $data = $request->validate([
            'period_label' => ['nullable', 'string', 'max:100'],
            'status_note' => ['nullable', 'string', 'max:500'],
        ]);

        $session->forceFill([
            'period_label' => $data['period_label'] ?? null,
            'status_note' => $data['status_note'] ?? null,
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::PeriodChange,
            'payload' => $data,
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.period_changed', $session, [...$this->context($session), ...$data]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    public function pause(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);

        $session->forceFill(['status' => ScoringSessionStatus::Paused])->save();

        $this->recordSimpleEvent($request, $session, ScoreEventType::Paused, 'scoring.paused');

        return back();
    }

    public function resume(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);

        if ($session->status !== ScoringSessionStatus::Paused) {
            throw ValidationException::withMessages([
                'status' => __('Only a paused session can be resumed.'),
            ]);
        }

        $session->forceFill(['status' => ScoringSessionStatus::InProgress])->save();

        $this->recordSimpleEvent($request, $session, ScoreEventType::Resumed, 'scoring.resumed');

        return back();
    }

    /**
     * End the session. This never creates, updates, or implies an
     * EventResult/ResultPlacement — an Organizer still encodes the
     * official result separately, same as if no live session existed.
     */
    public function end(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);

        /** @var User $user */
        $user = $request->user();

        $session->forceFill([
            'status' => ScoringSessionStatus::Ended,
            'ended_by' => $user->id,
            'ended_at' => now(),
        ])->save();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Ended,
            'payload' => ['score_a' => $session->score_a, 'score_b' => $session->score_b],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.ended', $session, $this->context($session));

        broadcast(new ScoreUpdated($session))->toOthers();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Live scoring ended.')]);

        return back();
    }

    /**
     * Record or reset a team foul (basketball scoreboard only — WP-07-04).
     * Fouls live in `sport_state`, decoupled from the generic score/period
     * columns every board type shares.
     */
    public function foul(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBasketball($session);

        $data = $request->validate([
            'action' => ['required', Rule::in(['add', 'reset'])],
            'side' => ['required_if:action,add', 'nullable', Rule::in(['a', 'b'])],
            'roster_player_id' => ['nullable', 'integer'],
        ]);

        $state = $session->sport_state ?? ['fouls_a' => 0, 'fouls_b' => 0];
        $rosterPlayer = null;

        if ($data['action'] === 'add') {
            $column = $data['side'] === 'a' ? 'fouls_a' : 'fouls_b';
            $state[$column] = ($state[$column] ?? 0) + 1;

            if (($data['roster_player_id'] ?? null) !== null) {
                $rosterPlayer = $this->rosterPlayerForSide($session, $data['roster_player_id'], $data['side']);

                if ($rosterPlayer !== null) {
                    $state['player_fouls'] ??= [];
                    $id = (string) $rosterPlayer->id;
                    $state['player_fouls'][$id] = ($state['player_fouls'][$id] ?? 0) + 1;
                }
            }
        } else {
            // A quarter's team-foul reset never touches player_fouls — a
            // player's own foul count is cumulative for the whole game
            // (real disqualification tracking), unlike the team total.
            $state['fouls_a'] = 0;
            $state['fouls_b'] = 0;
        }

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Foul,
            'payload' => [
                ...$data,
                'fouls_a' => $state['fouls_a'],
                'fouls_b' => $state['fouls_b'],
                ...($rosterPlayer !== null ? ['player_name' => $rosterPlayer->entry->athlete->fullName()] : []),
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record(
            $data['action'] === 'add' ? 'scoring.foul_recorded' : 'scoring.fouls_reset',
            $session,
            [...$this->context($session), ...$data],
        );

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Record a judged round score for both sides at once (boxing
     * scoreboard only — WP-07-05), 10-point-must style. Appends to the
     * round-by-round history in `sport_state` and adds to the session's
     * running `score_a`/`score_b` total — the same cumulative total every
     * board type displays. Past rounds are not individually editable here;
     * a mis-scored round is fixed the same way as any other board type,
     * through the generic `score` correction endpoint.
     */
    public function round(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBoxing($session);

        $data = $request->validate([
            'score_a' => ['required', 'integer', 'min:0', 'max:10'],
            'score_b' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $state = $session->sport_state ?? ['rounds' => []];
        $roundNumber = count($state['rounds']) + 1;
        $state['rounds'][] = ['round' => $roundNumber, ...$data];

        $session->forceFill([
            'sport_state' => $state,
            'score_a' => $session->score_a + $data['score_a'],
            'score_b' => $session->score_b + $data['score_b'],
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::RoundScore,
            'payload' => ['round' => $roundNumber, ...$data],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.round_scored', $session, [...$this->context($session), 'round' => $roundNumber, ...$data]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Advance the count/outs (softball/baseball scoreboard only —
     * WP-07-06). `out`/`strike` reset the count for the next batter; a
     * third strike is itself an out; a third out ends the half-inning
     * (resets outs/count, flips top<->bottom, increments the inning when
     * bottom ends). `ball` resets the count at four (a walk — this app
     * doesn't model baserunners, so no run is auto-added). `reset_count`
     * is a manual correction for a new batter with no walk/strikeout.
     */
    public function count(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertSoftballBaseball($session);

        $data = $request->validate([
            'action' => ['required', Rule::in(['out', 'ball', 'strike', 'reset_count'])],
        ]);

        $state = $session->sport_state ?? $this->initialSoftballState();

        $state = match ($data['action']) {
            'out' => $this->applySoftballOut($state),
            'ball' => $this->applySoftballBall($state),
            'strike' => $this->applySoftballStrike($state),
            'reset_count' => [...$state, 'balls' => 0, 'strikes' => 0],
            default => $state,
        };

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Count,
            'payload' => [...$data, ...$state],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.count_updated', $session, [...$this->context($session), ...$data]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Record runs scored by one side in the current inning (softball/
     * baseball scoreboard only — WP-07-06). Adds to the per-inning
     * breakdown in `sport_state` and to the session's running `score_a`/
     * `score_b` in the same request, so the two can never disagree.
     */
    public function inningRun(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertSoftballBaseball($session);

        $data = $request->validate([
            'side' => ['required', Rule::in(['a', 'b'])],
            'runs' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $state = $session->sport_state ?? $this->initialSoftballState();
        $column = $data['side'] === 'a' ? 'runs_a' : 'runs_b';

        $index = null;

        foreach ($state['innings'] as $i => $row) {
            if ($row['inning'] === $state['inning']) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            $state['innings'][] = ['inning' => $state['inning'], 'runs_a' => 0, 'runs_b' => 0];
            $index = count($state['innings']) - 1;
        }

        $state['innings'][$index][$column] += $data['runs'];

        $scoreColumn = $data['side'] === 'a' ? 'score_a' : 'score_b';

        $session->forceFill([
            'sport_state' => $state,
            $scoreColumn => $session->{$scoreColumn} + $data['runs'],
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::InningRun,
            'payload' => ['inning' => $state['inning'], ...$data],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.run_scored', $session, [...$this->context($session), 'inning' => $state['inning'], ...$data]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Update the game control settings (minutes per period, shot clock
     * duration, team colors) — basketball only. A config change, not a
     * play, so this doesn't append a score_events row/play-by-play line,
     * same reasoning as gameClock()/shotClock() below.
     */
    public function settings(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBasketball($session);

        $data = $request->validate([
            'minutes_per_period' => ['required', 'integer', 'min:1', 'max:20'],
            'shot_clock_duration' => ['required', 'integer', 'min:5', 'max:60'],
            'team_color_a' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'team_color_b' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $state = [...($session->sport_state ?? []), ...$data];

        $session->forceFill(['sport_state' => $state])->save();

        $this->audit->record('scoring.settings_updated', $session, [...$this->context($session), ...$data]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Set or clear the possession arrow — basketball only.
     */
    public function possession(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBasketball($session);

        $data = $request->validate([
            'side' => ['nullable', Rule::in(['a', 'b'])],
        ]);

        $state = $session->sport_state ?? [];
        $state['possession'] = $data['side'] ?? null;

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Possession,
            'payload' => $data,
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.possession_set', $session, [...$this->context($session), ...$data]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Set the operator-controlled game clock's remaining seconds —
     * basketball only. Manual, not server-ticking (owner decision): a
     * snapshot value plus a timestamp, same anchor+ticker shape
     * `RunningClock` already uses for the elapsed-time clock, just counting
     * down instead of up. Not an event/play-by-play line — a frequent
     * manual correction would flood the feed.
     */
    public function gameClock(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBasketball($session);

        $data = $request->validate([
            'seconds' => ['required', 'integer', 'min:0', 'max:3600'],
        ]);

        $state = $session->sport_state ?? [];
        $state['game_clock_seconds'] = $data['seconds'];
        $state['game_clock_updated_at'] = now()->toIso8601String();

        $session->forceFill(['sport_state' => $state])->save();

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Set the shot clock's remaining seconds — basketball only. Omitting
     * `seconds` resets it to the configured `shot_clock_duration` (the
     * common "reset after a play" case) — this app deliberately never
     * auto-resets it from a made basket/turnover, same documented
     * restraint as the rest of live scoring (docs/live-scoring.md).
     */
    public function shotClock(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBasketball($session);

        $data = $request->validate([
            'seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);

        $state = $session->sport_state ?? [];
        $state['shot_clock_seconds'] = $data['seconds'] ?? ($state['shot_clock_duration'] ?? 24);
        $state['shot_clock_updated_at'] = now()->toIso8601String();

        $session->forceFill(['sport_state' => $state])->save();

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Sound a manual horn/buzzer signal — basketball only. No other state
     * change; viewers flash on a fresh `horn_sounded_at`.
     */
    public function horn(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBasketball($session);

        $state = $session->sport_state ?? [];
        $state['horn_sounded_at'] = now()->toIso8601String();

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Horn,
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.horn_sounded', $session, $this->context($session));

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Toggle a roster player in or out of their side's on-court lineup —
     * basketball only. One primitive covers both "send a starter to court"
     * and "sub during play": `on_court: true` adds them (422 past 5 already
     * on court), `on_court: false` removes them.
     */
    public function lineup(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBasketball($session);

        $data = $request->validate([
            'side' => ['required', Rule::in(['a', 'b'])],
            'roster_player_id' => ['required', 'integer'],
            'on_court' => ['required', 'boolean'],
        ]);

        $rosterPlayer = $this->rosterPlayerForSide($session, $data['roster_player_id'], $data['side']);

        if ($rosterPlayer === null) {
            throw ValidationException::withMessages([
                'roster_player_id' => __('That player is not on this side\'s roster.'),
            ]);
        }

        $column = $data['side'] === 'a' ? 'on_court_a' : 'on_court_b';
        $state = $session->sport_state ?? [];
        $onCourt = $state[$column] ?? [];

        if ($data['on_court']) {
            if (in_array($rosterPlayer->id, $onCourt, true)) {
                throw ValidationException::withMessages([
                    'roster_player_id' => __('That player is already on court.'),
                ]);
            }

            if (count($onCourt) >= 5) {
                throw ValidationException::withMessages([
                    'roster_player_id' => __('Only 5 players may be on court at once — bench someone first.'),
                ]);
            }

            $onCourt[] = $rosterPlayer->id;
        } else {
            $onCourt = array_values(array_diff($onCourt, [$rosterPlayer->id]));
        }

        $state[$column] = $onCourt;

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Substitution,
            'payload' => [
                'side' => $data['side'],
                'roster_player_id' => $rosterPlayer->id,
                'on_court' => $data['on_court'],
                'player_name' => $rosterPlayer->entry->athlete->fullName(),
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.lineup_changed', $session, [
            ...$this->context($session),
            'side' => $data['side'],
            'roster_player_id' => $rosterPlayer->id,
            'on_court' => $data['on_court'],
        ]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * A roster player belonging to this session's match and the given
     * side — `null` if the id doesn't resolve, so callers can decide
     * whether that's a hard validation error or just "skip attribution."
     */
    private function rosterPlayerForSide(ScoringSession $session, int $rosterPlayerId, string $side): ?MatchRosterPlayer
    {
        $session->loadMissing('match');

        return MatchRosterPlayer::query()
            ->where('id', $rosterPlayerId)
            ->where('match_id', $session->match_id)
            ->where('side', $side)
            ->with('entry.athlete')
            ->first();
    }

    /**
     * @return array{inning: int, half: string, outs: int, balls: int, strikes: int, innings: array<int, array{inning: int, runs_a: int, runs_b: int}>}
     */
    private function initialSoftballState(): array
    {
        return ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => []];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function applySoftballBall(array $state): array
    {
        $state['balls']++;

        if ($state['balls'] >= 4) {
            $state['balls'] = 0;
            $state['strikes'] = 0;
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function applySoftballStrike(array $state): array
    {
        $state['strikes']++;

        return $state['strikes'] >= 3 ? $this->applySoftballOut($state) : $state;
    }

    /**
     * An out always ends the current batter's at-bat (count resets); a
     * third out ends the half-inning (flips top<->bottom, increments the
     * inning once bottom ends).
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function applySoftballOut(array $state): array
    {
        $state['outs']++;
        $state['balls'] = 0;
        $state['strikes'] = 0;

        if ($state['outs'] >= 3) {
            $state['outs'] = 0;

            if ($state['half'] === 'top') {
                $state['half'] = 'bottom';
            } else {
                $state['half'] = 'top';
                $state['inning']++;
            }
        }

        return $state;
    }

    private function assertActive(ScoringSession $session): void
    {
        if ($session->status === ScoringSessionStatus::Ended) {
            throw ValidationException::withMessages([
                'status' => __('This scoring session has already ended.'),
            ]);
        }
    }

    private function assertBasketball(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::Basketball) {
            abort(422, __('This action is only available for a basketball scoring session.'));
        }
    }

    private function assertBoxing(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::Boxing) {
            abort(422, __('This action is only available for a boxing scoring session.'));
        }
    }

    private function assertSoftballBaseball(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::SoftballBaseball) {
            abort(422, __('This action is only available for a softball/baseball scoring session.'));
        }
    }

    private function recordSimpleEvent(Request $request, ScoringSession $session, ScoreEventType $type, string $action): void
    {
        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => $type,
            'recorded_by' => $user->id,
        ]);

        $this->audit->record($action, $session, $this->context($session));

        broadcast(new ScoreUpdated($session))->toOthers();
    }

    /**
     * Mirrors the "Matches — list" authorization row, plus a Technical
     * Official scoped to their own assigned sport (view and manage use
     * the identical scope here — a Technical Official has no reason to
     * see a match outside the sport they're allowed to run scoring for).
     */
    private function authorizeView(Request $request, EventMatch $match): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->role === UserRole::TechnicalOfficial) {
            abort_unless($this->canManage($user, $match), 403);

            return;
        }

        Gate::authorize('viewAny', Entry::class);

        if ($user->role !== UserRole::DelegationOfficer) {
            return;
        }

        $isOwnMatch = $match->entries()
            ->whereHas('delegation.officers', fn ($officers) => $officers->whereKey($user->getKey()))
            ->exists();

        if (! $isOwnMatch) {
            abort(403);
        }
    }

    /**
     * Admin may manage any match's scoring; a Technical Official only a
     * match whose sport is one they're assigned to (`User::sports()`); an
     * Organizer only a match whose meet+sport they hold an active
     * Tournament Secretary or Tournament ICT `MeetSportAssignment` for —
     * a plain Organizer with no such assignment is still denied. Shared
     * by `board()`'s `canManage` flag, `authorizeView()`'s Technical
     * Official branch, and every mutating action's own authorization
     * check below — one definition of "who may run this match's
     * scoreboard."
     */
    private function canManage(User $user, EventMatch $match): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        // Deliberately not `loadMissing('event:id,sport_id')`: restricting
        // columns here would poison the relation cache for callers (like
        // `board()`) that load `event` more fully afterward — `loadMissing`
        // is a no-op once a relation is marked loaded, regardless of which
        // columns it was loaded with. A plain access either reuses an
        // already-fully-loaded `event` or lazy-loads the full row once.
        if ($user->role === UserRole::TechnicalOfficial) {
            return $user->sports()->whereKey($match->event->sport_id)->exists();
        }

        if ($user->role !== UserRole::Organizer) {
            return false;
        }

        return MeetSportAssignment::query()
            ->where('user_id', $user->id)
            ->where('status', MeetSportAssignmentStatus::Active)
            ->whereIn('role', [MeetSportAssignmentRole::TournamentSecretary, MeetSportAssignmentRole::TournamentICT])
            ->whereHas('meetSport', fn ($query) => $query
                ->where('meet_id', $match->meet_id)
                ->where('sport_id', $match->event->sport_id))
            ->exists();
    }

    /**
     * Authorization for a mutating action given the match directly
     * (`store()`, which doesn't yet have a `ScoringSession`).
     */
    private function authorizeManage(Request $request, EventMatch $match): void
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->canManage($user, $match), 403);
    }

    /**
     * Same as `authorizeManage()`, for the actions that take an existing
     * `ScoringSession` rather than the `EventMatch` itself.
     */
    private function authorizeManageSession(Request $request, ScoringSession $session): void
    {
        $session->loadMissing('match.event');

        $this->authorizeManage($request, $session->match);
    }

    /**
     * The two individual participants' photos (boxing's red/blue corner
     * display) — only meaningful for a 1v1 individual match, the same
     * `count() === 2` condition `suggestedLabels` already uses. A team
     * event (basketball/softball/baseball) has no single "the team's
     * photo," so this is `[null, null]` for those; the frontend falls
     * back to a generated logo badge from the side label instead.
     *
     * @param  Collection<int, Entry>  $entries
     * @return array{0: array{photo_url: string|null}|null, 1: array{photo_url: string|null}|null}
     */
    private function matchParticipants($entries): array
    {
        if ($entries->count() !== 2) {
            return [null, null];
        }

        $photoUrl = fn (Entry $entry): ?string => $entry->athlete->photo_upload_id === null
            ? null
            : route('athletes.photo', $entry->athlete);

        return [
            ['photo_url' => $photoUrl($entries[0])],
            ['photo_url' => $photoUrl($entries[1])],
        ];
    }

    /**
     * The match's current basketball roster, both sides — same shape
     * `MatchRosterPlayer::payloadForMatch()` gives the live payload, so the
     * initial Inertia page and every subsequent poll/Echo push agree.
     *
     * @return array{a: array<int, array<string, mixed>>, b: array<int, array<string, mixed>>}
     */
    private function rosterPayload(EventMatch $match): array
    {
        return MatchRosterPlayer::payloadForMatch($match->id);
    }

    /**
     * Confirmed entries for this match's event, per side, minus whoever's
     * already rostered — the pool `MatchRosterController::store()` can add
     * from. Only meaningful when the match has exactly two representative
     * entries (same guard `suggestedLabels` already uses); anything else
     * returns both sides empty so the frontend can prompt the operator to
     * set match participants first.
     *
     * @param  Collection<int, Entry>  $entries
     * @return array{a: array<int, array{id: int, label: string}>, b: array<int, array{id: int, label: string}>}
     */
    private function eligibleAthletes(EventMatch $match, $entries): array
    {
        if ($entries->count() !== 2) {
            return ['a' => [], 'b' => []];
        }

        $rosteredEntryIds = MatchRosterPlayer::query()
            ->where('match_id', $match->id)
            ->pluck('entry_id');

        $schoolIdFor = fn (int $index): int => $entries[$index]->athlete->school_id;

        $poolFor = function (int $schoolId) use ($match, $rosteredEntryIds): array {
            return Entry::query()
                ->where('event_id', $match->event_id)
                ->where('status', EntryStatus::Confirmed->value)
                ->whereHas('athlete', fn ($query) => $query->where('school_id', $schoolId))
                ->whereNotIn('id', $rosteredEntryIds)
                ->with('athlete')
                ->get()
                ->map(fn (Entry $entry): array => [
                    'id' => $entry->id,
                    'label' => $entry->athlete->fullName(),
                ])
                ->values()
                ->all();
        };

        return [
            'a' => $poolFor($schoolIdFor(0)),
            'b' => $poolFor($schoolIdFor(1)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function context(ScoringSession $session): array
    {
        $session->loadMissing('match:id,meet_id,event_id,round_label');

        return [
            'match_id' => $session->match_id,
            'round' => $session->match->round_label,
        ];
    }
}
