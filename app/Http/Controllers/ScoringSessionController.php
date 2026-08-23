<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\ScoreboardType;
use App\Enums\ScoreEventType;
use App\Enums\ScoringSessionStatus;
use App\Enums\UserRole;
use App\Events\ScoreUpdated;
use App\Http\Controllers\Concerns\ScopesToAssignedSport;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\MatchRosterPlayer;
use App\Models\MeetSportAssignment;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use App\Services\CompetitionResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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
    use ScopesToAssignedSport;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CompetitionResultService $competitionResults,
    ) {}

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
        ]);
    }

    /**
     * Start a new session for a scheduled match. Only one active
     * (non-ended) session per match is allowed.
     */
    public function store(Request $request, EventMatch $match): RedirectResponse
    {
        $this->authorizeManage($request, $match);

        if (! $match->live_scoring_enabled) {
            throw ValidationException::withMessages([
                'match_id' => __('Live scoring is disabled for this competition.'),
            ]);
        }

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

        // For a scheduled head-to-head match, the assigned participants
        // are authoritative. An ICT operator must not accidentally start
        // the board under hand-typed or swapped team names.
        $scheduledLabels = $match->entries()
            ->with('athlete.school:id,name')
            ->get()
            ->map(fn (Entry $entry): string => $entry->athlete->school->name)
            ->values();

        if ($scheduledLabels->count() === 2) {
            $data['side_a_label'] = $scheduledLabels[0];
            $data['side_b_label'] = $scheduledLabels[1];
        }

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
                'horn_sounded_at' => null, 'quarters' => 4,
            ],
            // Taekwondo/Wushu/Pencak Silat/Arnis (CombatRounds) share the exact
            // same round/rest-clock + bell + judged-round-points shape as
            // boxing — all four are genuinely 3-round, red/blue-corner
            // combat sports at the level of detail this app models, not a
            // fabricated resemblance. Deliberately not varying the
            // defaults per sport the way e.g. volleyball/sepak takraw
            // does — real round/rest duration varies more by age
            // division/weight class than by sport, and the operator can
            // already adjust it via settings().
            ScoreboardType::Boxing, ScoreboardType::CombatRounds => [
                'rounds' => [],
                'round_duration_seconds' => 120, 'rest_duration_seconds' => 60, 'total_rounds' => 3,
                'clock_seconds' => 120, 'clock_updated_at' => null, 'clock_phase' => 'round',
                'bell_sounded_at' => null,
            ],
            ScoreboardType::SoftballBaseball => [
                'inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => [],
                'innings_scheduled' => 7,
                'team_color_a' => '#dc2626', 'team_color_b' => '#2563eb',
            ],
            ScoreboardType::VolleyballSepakTakraw => $this->initialRallySetsState($match->event->sport->name),
            ScoreboardType::FootballFutsal => [
                'yellow_cards_a' => 0, 'yellow_cards_b' => 0,
                'red_cards_a' => 0, 'red_cards_b' => 0,
                'minutes_per_half' => mb_strtolower($match->event->sport->name) === 'futsal' ? 20 : 45,
            ],
            ScoreboardType::RacketGames => $this->initialRacketGamesState($match->event->sport->name),
            ScoreboardType::Wrestling => [
                'period_duration_seconds' => 180, 'rest_duration_seconds' => 30, 'total_periods' => 2,
                'clock_seconds' => 180, 'clock_updated_at' => null, 'clock_phase' => 'period',
                'fall_side' => null, 'fall_declared_at' => null,
            ],
            ScoreboardType::Tennis => [
                'sets' => [],
                'sets_won_a' => 0, 'sets_won_b' => 0,
                'current_set_games_a' => 0, 'current_set_games_b' => 0,
                'current_game_points_a' => 0, 'current_game_points_b' => 0,
                'is_tiebreak' => false,
                'tiebreak_points_a' => 0, 'tiebreak_points_b' => 0,
                'sets_to_win' => 2,
                'possession' => null,
            ],
            ScoreboardType::GoalBall => [
                'penalty_throws_a' => 0, 'penalty_throws_b' => 0,
                'minutes_per_half' => 6,
            ],
            ScoreboardType::Billiard => [
                'racks' => [],
                'racks_won_a' => 0, 'racks_won_b' => 0,
                'racks_to_win' => 5,
            ],
            ScoreboardType::Bocce => [
                'ends' => [],
                'ends_played' => 0,
                'target_score' => 12,
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

        $session->forceFill([
            'status' => ScoringSessionStatus::Paused,
            'sport_state' => $this->materializeCountdownClocks($session->sport_state ?? []),
        ])->save();

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

        $session->forceFill([
            'status' => ScoringSessionStatus::InProgress,
            'sport_state' => $this->restartCountdownClocks($session->sport_state ?? []),
        ])->save();

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
            'sport_state' => $this->materializeCountdownClocks($session->sport_state ?? []),
            'ended_by' => $user->id,
            'ended_at' => now(),
        ])->save();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Ended,
            'payload' => ['score_a' => $session->score_a, 'score_b' => $session->score_b],
            'recorded_by' => $user->id,
        ]);

        $session->match->forceFill(['status' => MatchStatus::Completed])->save();
        if ($session->match->event_schedule_id !== null) {
            $this->competitionResults->createFromLiveScore($session->fresh(), $user);
        }

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

    /** Remove a point or added basketball foul and reverse its totals. */
    public function removeEvent(Request $request, ScoringSession $session, ScoreEvent $event): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($event->scoring_session_id !== $session->id) {
            abort(404);
        }

        $payload = $event->payload ?? [];
        $isPoint = $event->type === ScoreEventType::Point;
        $isAddedFoul = $event->type === ScoreEventType::Foul && ($payload['action'] ?? null) === 'add';

        if (! $isPoint && ! $isAddedFoul) {
            throw ValidationException::withMessages([
                'event' => __('Only recorded points and added fouls can be removed.'),
            ]);
        }

        DB::transaction(function () use ($data, $event, $isPoint, $payload, $session): void {
            $state = $session->sport_state;
            $side = $payload['side'] ?? null;

            if ($isPoint) {
                $column = $side === 'a' ? 'score_a' : 'score_b';
                $delta = (int) ($payload['delta'] ?? 0);
                $session->{$column} = max(0, (int) $session->{$column} - $delta);

                if ($state !== null && isset($payload['roster_player_id'])) {
                    $id = (string) $payload['roster_player_id'];
                    $state['player_points'][$id] = max(0, (int) ($state['player_points'][$id] ?? 0) - $delta);
                }
            } else {
                $column = $side === 'a' ? 'fouls_a' : 'fouls_b';
                $state ??= [];
                $state[$column] = max(0, (int) ($state[$column] ?? 0) - 1);

                if (isset($payload['roster_player_id'])) {
                    $id = (string) $payload['roster_player_id'];
                    $state['player_fouls'][$id] = max(0, (int) ($state['player_fouls'][$id] ?? 0) - 1);
                }
            }

            $session->sport_state = $state;
            $session->save();

            $this->audit->record('scoring.event_removed', $session, [
                ...$this->context($session),
                'score_event_id' => $event->id,
                'score_event_type' => $event->type->value,
                'payload' => $payload,
                'reason' => $data['reason'],
            ]);

            $event->delete();
        });

        broadcast(new ScoreUpdated($session->fresh()))->toOthers();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Play removed.')]);

        return back();
    }

    /**
     * Record a judged round score for both sides at once (boxing
     * scoreboard — WP-07-05 — and taekwondo/wushu/pencak silat/arnis, which
     * share this exact round-clock+judged-round-points shape), 10-point-
     * must style. Appends to the round-by-round history in `sport_state`
     * and adds to the session's running `score_a`/`score_b` total — the
     * same cumulative total every board type displays. Past rounds are
     * not individually editable here; a mis-scored round is fixed the
     * same way as any other board type, through the generic `score`
     * correction endpoint.
     */
    public function round(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBoxingOrCombatRounds($session);

        $data = $request->validate([
            'score_a' => ['required', 'integer', 'min:0', 'max:10'],
            'score_b' => ['required', 'integer', 'min:0', 'max:10'],
        ]);

        $state = $session->sport_state ?? ['rounds' => []];
        $totalRounds = $state['total_rounds'] ?? null;

        if ($totalRounds !== null && count($state['rounds']) >= $totalRounds) {
            throw ValidationException::withMessages([
                'score_a' => __('All :n scheduled rounds have already been recorded.', ['n' => $totalRounds]),
            ]);
        }

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
     * Record a rally point or a correction to the current set's live score
     * — volleyball/sepak takraw only. Unlike boxing's round() (a whole
     * round judged and entered at once), a set is built up point by point,
     * so this is closer in shape to the generic score() endpoint, just
     * targeting `sport_state.current_set_score_*` instead of the session's
     * own `score_a`/`score_b` columns — those instead track **sets won**
     * for this sport (the number the main scoreboard shows), only ever
     * changed here when a point completes a set. A `point` (not
     * `correction`) delta that brings a side to the set's target with a
     * 2-point lead finalizes the set: appends to `sport_state.sets`,
     * increments `sets_won_*`, resets the live set score to 0-0, and syncs
     * `score_a`/`score_b` to the new `sets_won_*` — this app never
     * auto-ends the session even once a side reaches the sets needed to
     * win a match, same restraint as every other sport-specific rule here
     * (the operator still ends it manually). A `correction` only adjusts
     * the live set score and never triggers set completion, so a
     * mis-tapped point can be undone without accidentally finalizing a set
     * a moment too early.
     */
    public function rallyPoint(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertVolleyballSepakTakraw($session);

        $data = $request->validate([
            'type' => ['required', Rule::in([ScoreEventType::Point->value, ScoreEventType::Correction->value])],
            'side' => ['required', Rule::in(['a', 'b'])],
            'delta' => ['required', 'integer'],
            'reason' => ['required_if:type,'.ScoreEventType::Correction->value, 'nullable', 'string', 'max:500'],
        ]);

        if ($session->sport_state === null) {
            $session->loadMissing('match.event.sport');
        }

        $state = $session->sport_state ?? $this->initialRallySetsState($session->match->event->sport->name);
        $column = $data['side'] === 'a' ? 'current_set_score_a' : 'current_set_score_b';
        $state[$column] = max(0, $state[$column] + $data['delta']);

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            // Always RallyPoint, even for a correction — this action
            // targets `current_set_score_*`, not the session's own
            // `score_a`/`score_b`, so it must never share the generic
            // Correction type (playByPlay()'s reconstruction treats that
            // type as a main-score delta; sharing it would corrupt the
            // sets-won running total). `is_correction` distinguishes the
            // two within this one type instead.
            'type' => ScoreEventType::RallyPoint,
            'payload' => [
                'side' => $data['side'],
                'delta' => $data['delta'],
                'is_correction' => $data['type'] === ScoreEventType::Correction->value,
                'reason' => $data['reason'] ?? null,
                'current_set_score_a' => $state['current_set_score_a'],
                'current_set_score_b' => $state['current_set_score_b'],
                'set' => count($state['sets']) + 1,
            ],
            'recorded_by' => $user->id,
        ]);

        $setCompleted = null;

        if ($data['type'] === ScoreEventType::Point->value) {
            $setCompleted = $this->finalizeSetIfWon($state);
        }

        $session->forceFill([
            'sport_state' => $state,
            'score_a' => $state['sets_won_a'],
            'score_b' => $state['sets_won_b'],
        ])->save();

        if ($setCompleted !== null) {
            ScoreEvent::create([
                'scoring_session_id' => $session->id,
                'type' => ScoreEventType::SetComplete,
                'payload' => $setCompleted,
                'recorded_by' => $user->id,
            ]);

            $this->audit->record('scoring.set_completed', $session, [...$this->context($session), ...$setCompleted]);
        }

        $this->audit->record(
            $data['type'] === ScoreEventType::Correction->value ? 'scoring.rally_point_corrected' : 'scoring.rally_point_scored',
            $session,
            [...$this->context($session), 'side' => $data['side'], 'delta' => $data['delta'], 'reason' => $data['reason'] ?? null],
        );

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Whether the just-updated live set score reaches this set's target
     * with the required 2-point lead — the deciding set (both sides one
     * set away from winning the match) uses `deciding_set_target_points`
     * instead of the regular `set_target_points`, the same real rule
     * volleyball/sepak takraw both use. Mutates `$state` in place (appends
     * to `sets`, increments `sets_won_*`, resets the live set score) and
     * returns the completed set's summary for the caller's own
     * `score_events`/audit row, or `null` if the set isn't over yet.
     *
     * @param  array<string, mixed>  &$state
     * @return array{set: int, score_a: int, score_b: int, sets_won_a: int, sets_won_b: int}|null
     */
    private function finalizeSetIfWon(array &$state): ?array
    {
        $isDecidingSet = ($state['sets_won_a'] + $state['sets_won_b']) === (2 * $state['sets_to_win'] - 2);
        $target = $isDecidingSet ? $state['deciding_set_target_points'] : $state['set_target_points'];

        $a = $state['current_set_score_a'];
        $b = $state['current_set_score_b'];

        if (max($a, $b) < $target || abs($a - $b) < 2) {
            return null;
        }

        $setNumber = count($state['sets']) + 1;
        $state['sets'][] = ['set' => $setNumber, 'score_a' => $a, 'score_b' => $b];

        if ($a > $b) {
            $state['sets_won_a']++;
        } else {
            $state['sets_won_b']++;
        }

        $state['current_set_score_a'] = 0;
        $state['current_set_score_b'] = 0;

        return [
            'set' => $setNumber,
            'score_a' => $a,
            'score_b' => $b,
            'sets_won_a' => $state['sets_won_a'],
            'sets_won_b' => $state['sets_won_b'],
        ];
    }

    /**
     * Record or reset a card tally — football/futsal only, the same
     * add/reset team-tally shape as basketball's foul() above (`add`
     * increments one of four counters — yellow/red × side; `reset` zeroes
     * all four, e.g. an operator correcting a mis-tap, since this app
     * never auto-clears cards at a real stoppage the way it does for
     * basketball's per-quarter fouls). No roster/per-player attribution,
     * matching this WP's scope decision for every sport after basketball
     * (team-level tallies only).
     */
    public function card(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertFootballFutsal($session);

        $data = $request->validate([
            'action' => ['required', Rule::in(['add', 'reset'])],
            'side' => ['required_if:action,add', 'nullable', Rule::in(['a', 'b'])],
            'type' => ['required_if:action,add', 'nullable', Rule::in(['yellow', 'red'])],
        ]);

        $state = $session->sport_state ?? [
            'yellow_cards_a' => 0, 'yellow_cards_b' => 0,
            'red_cards_a' => 0, 'red_cards_b' => 0,
        ];

        if ($data['action'] === 'add') {
            $column = "{$data['type']}_cards_{$data['side']}";
            $state[$column] = ($state[$column] ?? 0) + 1;
        } else {
            $state['yellow_cards_a'] = 0;
            $state['yellow_cards_b'] = 0;
            $state['red_cards_a'] = 0;
            $state['red_cards_b'] = 0;
        }

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Card,
            'payload' => [
                ...$data,
                'yellow_cards_a' => $state['yellow_cards_a'],
                'yellow_cards_b' => $state['yellow_cards_b'],
                'red_cards_a' => $state['red_cards_a'],
                'red_cards_b' => $state['red_cards_b'],
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record(
            $data['action'] === 'add' ? 'scoring.card_issued' : 'scoring.cards_reset',
            $session,
            [...$this->context($session), ...$data],
        );

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Record or reset a penalty throw tally — goal ball only. A penalty
     * throw (illegal defense, quick-throw/8-second violation, noise foul,
     * etc.) gives the opposing team an uncontested shot defended by only
     * the offending player; this app doesn't model who defends or
     * auto-award the resulting goal (that's a real scoring chance, not a
     * guaranteed one), so the operator still scores it with the ordinary
     * `score()` endpoint if it goes in — this endpoint only tracks the
     * tally, same "count, don't auto-resolve" shape as basketball's foul().
     */
    public function penaltyThrow(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertGoalBall($session);

        $data = $request->validate([
            'action' => ['required', Rule::in(['add', 'reset'])],
            'side' => ['required_if:action,add', 'nullable', Rule::in(['a', 'b'])],
        ]);

        $state = $session->sport_state ?? ['penalty_throws_a' => 0, 'penalty_throws_b' => 0];

        if ($data['action'] === 'add') {
            $column = "penalty_throws_{$data['side']}";
            $state[$column] = ($state[$column] ?? 0) + 1;
        } else {
            $state['penalty_throws_a'] = 0;
            $state['penalty_throws_b'] = 0;
        }

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::PenaltyThrow,
            'payload' => [
                ...$data,
                'penalty_throws_a' => $state['penalty_throws_a'],
                'penalty_throws_b' => $state['penalty_throws_b'],
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record(
            $data['action'] === 'add' ? 'scoring.penalty_throw_issued' : 'scoring.penalty_throws_reset',
            $session,
            [...$this->context($session), ...$data],
        );

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Award a completed rack to a side — billiard only. Unlike every other
     * family in this app, a rack has no in-progress running score to speak
     * of (this app doesn't model individual balls/shots), so there's no
     * point-by-point endpoint here at all: the operator simply declares
     * who won the rack just played, once it's over.
     */
    public function billiardRack(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBilliard($session);

        $data = $request->validate([
            'side' => ['required', Rule::in(['a', 'b'])],
        ]);

        $state = $session->sport_state ?? ['racks' => [], 'racks_won_a' => 0, 'racks_won_b' => 0, 'racks_to_win' => 5];
        $rack = ['rack' => count($state['racks']) + 1, 'winner' => $data['side']];
        $state['racks'][] = $rack;
        $column = "racks_won_{$data['side']}";
        $state[$column] = ($state[$column] ?? 0) + 1;

        $session->forceFill([
            'sport_state' => $state,
            'score_a' => $state['racks_won_a'],
            'score_b' => $state['racks_won_b'],
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::RackComplete,
            'payload' => [
                'rack' => $rack['rack'],
                'winner' => $data['side'],
                'racks_won_a' => $state['racks_won_a'],
                'racks_won_b' => $state['racks_won_b'],
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.rack_completed', $session, [...$this->context($session), ...$rack]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Undo the most recently awarded rack — billiard only. Racks are a
     * plain append-only list (not a derived state machine like tennis'
     * games/sets), so undo is just "pop the last entry and decrement its
     * winner's count," no snapshot needed. A harmless no-op with no racks
     * played yet, same convention as tennisUndo().
     */
    public function billiardUndoRack(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBilliard($session);

        $state = $session->sport_state ?? [];

        if (empty($state['racks'])) {
            return back();
        }

        $lastRack = array_pop($state['racks']);
        $column = "racks_won_{$lastRack['winner']}";
        $state[$column] = max(0, ($state[$column] ?? 0) - 1);

        $session->forceFill([
            'sport_state' => $state,
            'score_a' => $state['racks_won_a'],
            'score_b' => $state['racks_won_b'],
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::RackUndo,
            'payload' => [
                'rack' => $lastRack['rack'],
                'winner' => $lastRack['winner'],
                'racks_won_a' => $state['racks_won_a'],
                'racks_won_b' => $state['racks_won_b'],
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.rack_undone', $session, [...$this->context($session), ...$lastRack]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Award the points from a completed end to one side — bocce only. A
     * real end always awards points to exactly one side (whichever team's
     * balls finished closest to the jack; the other side scores 0 for
     * that end), so this always increments a single side's score, never
     * splits points across both. How many points a given end is worth
     * varies by local rules/ball count (not a fixed number this app can
     * assert), so the operator enters it — this endpoint only enforces
     * that it's a positive whole number, not a specific maximum.
     */
    public function bocceEnd(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBocce($session);

        $data = $request->validate([
            'side' => ['required', Rule::in(['a', 'b'])],
            'points' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $state = $session->sport_state ?? ['ends' => [], 'ends_played' => 0, 'target_score' => 12];
        $end = ['end' => count($state['ends']) + 1, 'winner' => $data['side'], 'points' => $data['points']];
        $state['ends'][] = $end;
        $state['ends_played'] = count($state['ends']);

        $column = $data['side'] === 'a' ? 'score_a' : 'score_b';
        $newValue = $session->{$column} + $data['points'];

        $session->forceFill([
            $column => $newValue,
            'sport_state' => $state,
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::EndComplete,
            'payload' => [
                'end' => $end['end'],
                'winner' => $data['side'],
                'points' => $data['points'],
                'score_a' => $data['side'] === 'a' ? $newValue : $session->score_a,
                'score_b' => $data['side'] === 'b' ? $newValue : $session->score_b,
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.end_completed', $session, [...$this->context($session), ...$end]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Undo the most recently completed end — bocce only. Same "pop the
     * last append-only entry and reverse its effect" shape as
     * billiardUndoRack(), no snapshot needed since an end's point award is
     * a plain, fully-reversible delta to one side's score.
     */
    public function bocceUndoEnd(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBocce($session);

        $state = $session->sport_state ?? [];

        if (empty($state['ends'])) {
            return back();
        }

        $lastEnd = array_pop($state['ends']);
        $state['ends_played'] = count($state['ends']);

        $column = $lastEnd['winner'] === 'a' ? 'score_a' : 'score_b';
        $newValue = max(0, $session->{$column} - $lastEnd['points']);

        $session->forceFill([
            $column => $newValue,
            'sport_state' => $state,
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::EndUndo,
            'payload' => [
                'end' => $lastEnd['end'],
                'winner' => $lastEnd['winner'],
                'points' => $lastEnd['points'],
                'score_a' => $lastEnd['winner'] === 'a' ? $newValue : $session->score_a,
                'score_b' => $lastEnd['winner'] === 'b' ? $newValue : $session->score_b,
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.end_undone', $session, [...$this->context($session), ...$lastEnd]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Record a game point or a correction to the current game's live score
     * — table tennis/badminton only. Same shape as volleyball/sepak
     * takraw's rallyPoint() (point-by-point toward a `sport_state.
     * current_game_score_*`, corrections never trigger completion,
     * `score_a`/`score_b` track **games won** not points), kept as its
     * own endpoint/event type rather than reusing rallyPoint()'s because
     * the win condition genuinely differs: badminton's hard cap at 30
     * (finalizeGameIfWon() below) has no equivalent in volleyball's rules,
     * and sharing one endpoint for two different win-condition shapes
     * would be more error-prone than two small, clear ones.
     */
    public function gamePoint(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertRacketGames($session);

        $data = $request->validate([
            'type' => ['required', Rule::in([ScoreEventType::Point->value, ScoreEventType::Correction->value])],
            'side' => ['required', Rule::in(['a', 'b'])],
            'delta' => ['required', 'integer'],
            'reason' => ['required_if:type,'.ScoreEventType::Correction->value, 'nullable', 'string', 'max:500'],
        ]);

        if ($session->sport_state === null) {
            $session->loadMissing('match.event.sport');
        }

        $state = $session->sport_state ?? $this->initialRacketGamesState($session->match->event->sport->name);
        $column = $data['side'] === 'a' ? 'current_game_score_a' : 'current_game_score_b';
        $state[$column] = max(0, $state[$column] + $data['delta']);

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            // Always GamePoint, even for a correction — same reasoning as
            // rallyPoint()'s RallyPoint type: this targets
            // `current_game_score_*`, not the session's own `score_a`/
            // `score_b`, so it must never share the generic Correction
            // type (playByPlay()'s reconstruction would misread it as a
            // main-score delta and corrupt the games-won running total).
            'type' => ScoreEventType::GamePoint,
            'payload' => [
                'side' => $data['side'],
                'delta' => $data['delta'],
                'is_correction' => $data['type'] === ScoreEventType::Correction->value,
                'reason' => $data['reason'] ?? null,
                'current_game_score_a' => $state['current_game_score_a'],
                'current_game_score_b' => $state['current_game_score_b'],
                'game' => count($state['games']) + 1,
            ],
            'recorded_by' => $user->id,
        ]);

        $gameCompleted = null;

        if ($data['type'] === ScoreEventType::Point->value) {
            $gameCompleted = $this->finalizeGameIfWon($state);
        }

        $session->forceFill([
            'sport_state' => $state,
            'score_a' => $state['games_won_a'],
            'score_b' => $state['games_won_b'],
        ])->save();

        if ($gameCompleted !== null) {
            ScoreEvent::create([
                'scoring_session_id' => $session->id,
                'type' => ScoreEventType::GameComplete,
                'payload' => $gameCompleted,
                'recorded_by' => $user->id,
            ]);

            $this->audit->record('scoring.game_completed', $session, [...$this->context($session), ...$gameCompleted]);
        }

        $this->audit->record(
            $data['type'] === ScoreEventType::Correction->value ? 'scoring.game_point_corrected' : 'scoring.game_point_scored',
            $session,
            [...$this->context($session), 'side' => $data['side'], 'delta' => $data['delta'], 'reason' => $data['reason'] ?? null],
        );

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Whether the just-updated live game score is over — either side
     * reaching `hard_cap_points` (badminton's 30-point ceiling; `0` means
     * no cap, table tennis) wins immediately regardless of lead, otherwise
     * the normal target-with-a-2-point-lead rule applies (both sports use
     * the same target for every game, including the decider — no
     * volleyball-style reduced deciding-game target here). Mutates
     * `$state` in place and returns the completed game's summary, or
     * `null` if the game isn't over yet.
     *
     * @param  array<string, mixed>  &$state
     * @return array{game: int, score_a: int, score_b: int, games_won_a: int, games_won_b: int}|null
     */
    private function finalizeGameIfWon(array &$state): ?array
    {
        $a = $state['current_game_score_a'];
        $b = $state['current_game_score_b'];
        $cap = $state['hard_cap_points'];
        $target = $state['game_target_points'];

        $capReached = $cap > 0 && max($a, $b) >= $cap;
        $targetReachedWithLead = max($a, $b) >= $target && abs($a - $b) >= 2;

        if (! $capReached && ! $targetReachedWithLead) {
            return null;
        }

        $gameNumber = count($state['games']) + 1;
        $state['games'][] = ['game' => $gameNumber, 'score_a' => $a, 'score_b' => $b];

        if ($a > $b) {
            $state['games_won_a']++;
        } else {
            $state['games_won_b']++;
        }

        $state['current_game_score_a'] = 0;
        $state['current_game_score_b'] = 0;

        return [
            'game' => $gameNumber,
            'score_a' => $a,
            'score_b' => $b,
            'games_won_a' => $state['games_won_a'],
            'games_won_b' => $state['games_won_b'],
        ];
    }

    /**
     * Record a named-move wrestling point — wrestling only. Unlike boxing's
     * round() (a whole round judged and entered at once) or the rally-
     * point sports' current-set/current-game tracking, wrestling's
     * `score_a`/`score_b` columns are a plain running point total, the
     * same semantic as basketball/football's — so, deliberately, a
     * correction does NOT get its own endpoint here; a mis-tapped point
     * is fixed through the existing generic `scoring.score` correction
     * endpoint exactly like basketball/football already do, since there's
     * no risk of misreading the correction as the wrong kind of delta
     * (unlike rallyPoint()/gamePoint(), which had to avoid the generic
     * Correction type because THEIR score_a/b means sets/games won, not
     * points). The `move` is real, structured data for the bout sheet/
     * play-by-play (e.g. "+2 Takedown — Red"), not just a label — this
     * app doesn't hardcode a per-move point value (real values vary by
     * wrestling style/rule set), the operator supplies `points` for
     * whichever move actually happened.
     */
    public function wrestlingPoint(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertWrestling($session);

        $data = $request->validate([
            'side' => ['required', Rule::in(['a', 'b'])],
            'move' => ['required', Rule::in(['takedown', 'escape', 'reversal', 'near_fall', 'penalty'])],
            'points' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $column = $data['side'] === 'a' ? 'score_a' : 'score_b';
        $newValue = $session->{$column} + $data['points'];

        $session->forceFill([$column => $newValue])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::WrestlingPoint,
            'payload' => [
                'side' => $data['side'],
                'move' => $data['move'],
                'points' => $data['points'],
                'result' => $newValue,
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.wrestling_point_scored', $session, [...$this->context($session), ...$data]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Set the wrestling period/rest countdown clock — wrestling only.
     * Same manual anchor+ticker shape and `phase`-vs-`seconds` split as
     * boxing's roundClock() above, just wrestling's own field names
     * (period, not round — real wrestling terminology) since this WP
     * gave wrestling its own board type rather than reusing boxing's.
     */
    public function periodClock(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertWrestling($session);

        $data = $request->validate([
            'phase' => ['nullable', Rule::in(['period', 'rest'])],
            'seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
        ]);

        $state = $session->sport_state ?? [];

        if (($data['phase'] ?? null) !== null) {
            $state['clock_phase'] = $data['phase'];
            $state['clock_seconds'] = $data['phase'] === 'period'
                ? ($state['period_duration_seconds'] ?? 180)
                : ($state['rest_duration_seconds'] ?? 30);
        } elseif (($data['seconds'] ?? null) !== null) {
            $state['clock_seconds'] = $data['seconds'];
        }

        $state['clock_updated_at'] = now()->toIso8601String();

        $session->forceFill(['sport_state' => $state])->save();

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Declare or clear a fall (pin) — wrestling only. A fall is a real,
     * decisive event worth logging (`fall_side`/`fall_declared_at` in
     * `sport_state`, plus a `score_events` row for the permanent record),
     * but — same restraint as every other sport-specific rule in this
     * controller — it never auto-ends the session or implies an official
     * result; the operator still ends the session manually, and the
     * actual win/loss is still encoded separately through Phase 3's own
     * result flow. `clear` undoes a mis-tapped declaration.
     */
    public function fall(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertWrestling($session);

        $data = $request->validate([
            'action' => ['required', Rule::in(['declare', 'clear'])],
            'side' => ['required_if:action,declare', 'nullable', Rule::in(['a', 'b'])],
        ]);

        $state = $session->sport_state ?? [];

        if ($data['action'] === 'declare') {
            $state['fall_side'] = $data['side'];
            $state['fall_declared_at'] = now()->toIso8601String();
        } else {
            $state['fall_side'] = null;
            $state['fall_declared_at'] = null;
        }

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Fall,
            'payload' => $data,
            'recorded_by' => $user->id,
        ]);

        $this->audit->record(
            $data['action'] === 'declare' ? 'scoring.fall_declared' : 'scoring.fall_cleared',
            $session,
            [...$this->context($session), ...$data],
        );

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Record a tennis point — the standard universal format only (real
     * Love/15/30/40/deuce/advantage scoring within a game, 6-game sets
     * with a tiebreak at 6-6 played to 7 points win-by-2, best-of-N sets)
     * — deliberately not the tournament-specific "no-ad"/"match tiebreak"
     * variants, same restraint as every other sport-specific rule in this
     * controller (badminton's fixed cap, softball's no-baserunner model):
     * those are real, different formats, not just configuration of this
     * one, and guessing which a given meet uses would be fabrication, not
     * a reasonable default.
     *
     * Unlike every other sport's correction path, a simple point delta
     * doesn't make sense here — points/games/sets/tiebreak are a single
     * derived state machine, not an independent running total. Instead,
     * every call captures the pre-mutation state as `sport_state.
     * _undo_snapshot` (a single level of nesting, never itself carrying a
     * further snapshot) so a mis-tap can be reversed exactly via
     * tennisUndo() below — a real, bounded "undo my last tap", not a full
     * history browser.
     */
    public function tennisPoint(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertTennis($session);

        $data = $request->validate([
            'side' => ['required', Rule::in(['a', 'b'])],
        ]);

        $state = $session->sport_state ?? [];
        $snapshot = $state;
        unset($snapshot['_undo_snapshot']);

        $state = $this->applyTennisPoint($state, $data['side']);
        $state['_undo_snapshot'] = $snapshot;

        $session->forceFill([
            'sport_state' => $state,
            'score_a' => $state['sets_won_a'],
            'score_b' => $state['sets_won_b'],
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::TennisPoint,
            'payload' => [
                'side' => $data['side'],
                'current_set_games_a' => $state['current_set_games_a'],
                'current_set_games_b' => $state['current_set_games_b'],
                'current_game_points_a' => $state['current_game_points_a'],
                'current_game_points_b' => $state['current_game_points_b'],
                'is_tiebreak' => $state['is_tiebreak'],
            ],
            'recorded_by' => $user->id,
        ]);

        if (count($state['sets']) > count($snapshot['sets'] ?? [])) {
            $completedSet = end($state['sets']);

            ScoreEvent::create([
                'scoring_session_id' => $session->id,
                'type' => ScoreEventType::SetComplete,
                'payload' => [
                    'set' => $completedSet['set'],
                    'score_a' => $completedSet['score_a'],
                    'score_b' => $completedSet['score_b'],
                    'sets_won_a' => $state['sets_won_a'],
                    'sets_won_b' => $state['sets_won_b'],
                ],
                'recorded_by' => $user->id,
            ]);

            $this->audit->record('scoring.set_completed', $session, [...$this->context($session), ...$completedSet]);
        }

        $this->audit->record('scoring.tennis_point_scored', $session, [...$this->context($session), 'side' => $data['side']]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Applies one point to `$side`, running the full game → set →
     * tiebreak state machine. Points always increment by exactly 1 (real
     * tennis has no "+2" concept the way basketball/wrestling do) — the
     * raw counters climb past the real 0/15/30/40 range once a game
     * reaches deuce (e.g. 4-3 represents "advantage"), the frontend
     * formats that for display; this method only needs the win
     * conditions, not the display labels.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function applyTennisPoint(array $state, string $side): array
    {
        $other = $side === 'a' ? 'b' : 'a';

        if ($state['is_tiebreak']) {
            $state["tiebreak_points_{$side}"]++;
            $mine = $state["tiebreak_points_{$side}"];
            $theirs = $state["tiebreak_points_{$other}"];

            if ($mine >= 7 && $mine - $theirs >= 2) {
                $state["current_set_games_{$side}"]++;
                $state = $this->finalizeTennisSet($state, $side);
            }

            return $state;
        }

        $state["current_game_points_{$side}"]++;
        $mine = $state["current_game_points_{$side}"];
        $theirs = $state["current_game_points_{$other}"];

        if ($mine >= 4 && $mine - $theirs >= 2) {
            $state["current_set_games_{$side}"]++;
            $state['current_game_points_a'] = 0;
            $state['current_game_points_b'] = 0;

            $gamesMine = $state["current_set_games_{$side}"];
            $gamesTheirs = $state["current_set_games_{$other}"];

            if ($gamesMine === 6 && $gamesTheirs === 6) {
                $state['is_tiebreak'] = true;
                $state['tiebreak_points_a'] = 0;
                $state['tiebreak_points_b'] = 0;
            } elseif ($gamesMine >= 6 && $gamesMine - $gamesTheirs >= 2) {
                $state = $this->finalizeTennisSet($state, $side);
            }
        }

        return $state;
    }

    /**
     * Appends the just-finished set to `sport_state.sets`, increments
     * `sets_won_{side}`, and resets the current-set counters — shared by
     * both the tiebreak and the normal-game win paths in
     * applyTennisPoint() above.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function finalizeTennisSet(array $state, string $side): array
    {
        $setNumber = count($state['sets']) + 1;
        $state['sets'][] = [
            'set' => $setNumber,
            'score_a' => $state['current_set_games_a'],
            'score_b' => $state['current_set_games_b'],
        ];

        $state["sets_won_{$side}"]++;
        $state['current_set_games_a'] = 0;
        $state['current_set_games_b'] = 0;
        $state['is_tiebreak'] = false;
        $state['tiebreak_points_a'] = 0;
        $state['tiebreak_points_b'] = 0;

        return $state;
    }

    /**
     * Reverses the most recent tennisPoint() call — restores
     * `sport_state._undo_snapshot` verbatim (dropping its own nested key,
     * so this only ever undoes one step, never a chain) and re-syncs
     * `score_a`/`score_b` to the restored `sets_won_*`. A no-op (still
     * returns success) if there's nothing to undo, e.g. right after the
     * session started.
     */
    public function tennisUndo(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertTennis($session);

        $state = $session->sport_state ?? [];

        if (! isset($state['_undo_snapshot'])) {
            return back();
        }

        $restored = $state['_undo_snapshot'];
        unset($restored['_undo_snapshot']);

        $session->forceFill([
            'sport_state' => $restored,
            'score_a' => $restored['sets_won_a'],
            'score_b' => $restored['sets_won_b'],
        ])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::TennisUndo,
            'payload' => [
                'sets_won_a' => $restored['sets_won_a'],
                'sets_won_b' => $restored['sets_won_b'],
            ],
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.tennis_point_undone', $session, $this->context($session));

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
     * Update the game control settings — a per-board-type config change,
     * not a play, so this doesn't append a score_events row/play-by-play
     * line, same reasoning as gameClock()/shotClock()/roundClock() below.
     * Basketball: minutes per period, shot clock duration, team colors,
     * quarters. Boxing (and taekwondo/wushu/pencak silat/arnis, which share
     * boxing's exact round-clock shape): round/rest duration, total
     * rounds. Softball/baseball: team colors and the regulation innings
     * count (a display
     * label only — this app doesn't enforce a game length or auto-end a
     * session from it, same restraint as every other sport-specific rule
     * documented in docs/live-scoring.md). Volleyball/sepak takraw: the
     * regular and deciding-set target points, and sets needed to win —
     * changing these mid-set is a real, if unusual, operator action (e.g.
     * correcting a meet's local variant), not something this app blocks.
     * Football/futsal: minutes per half — a display label only, same
     * restraint as softball's regulation-innings count; the match clock
     * itself is the plain elapsed-time `RunningClock` every board type
     * already gets for free, not a new per-sport countdown. Table tennis/
     * badminton: the target points per game, the hard-cap point (0 =
     * none, badminton's real 30-point ceiling), and games needed to win.
     * Wrestling: period/rest duration and total periods — its own
     * board type (not boxing's), so its own field names (period, not
     * round).
     */
    public function settings(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);

        $data = match ($session->boardType()) {
            ScoreboardType::Basketball => $request->validate([
                'minutes_per_period' => ['required', 'integer', 'min:1', 'max:20'],
                'shot_clock_duration' => ['required', 'integer', 'min:5', 'max:60'],
                'team_color_a' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'team_color_b' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'quarters' => ['required', 'integer', Rule::in([2, 4])],
            ]),
            ScoreboardType::Boxing, ScoreboardType::CombatRounds => $request->validate([
                'round_duration_seconds' => ['required', 'integer', 'min:30', 'max:600'],
                'rest_duration_seconds' => ['required', 'integer', 'min:15', 'max:300'],
                'total_rounds' => ['required', 'integer', 'min:1', 'max:12'],
            ]),
            ScoreboardType::SoftballBaseball => $request->validate([
                'team_color_a' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'team_color_b' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'innings_scheduled' => ['required', 'integer', 'min:3', 'max:15'],
            ]),
            ScoreboardType::VolleyballSepakTakraw => $request->validate([
                'set_target_points' => ['required', 'integer', 'min:5', 'max:50'],
                'deciding_set_target_points' => ['required', 'integer', 'min:5', 'max:50'],
                'sets_to_win' => ['required', 'integer', 'min:1', 'max:5'],
            ]),
            ScoreboardType::FootballFutsal => $request->validate([
                'minutes_per_half' => ['required', 'integer', 'min:5', 'max:60'],
            ]),
            ScoreboardType::RacketGames => $request->validate([
                'game_target_points' => ['required', 'integer', 'min:5', 'max:50'],
                'hard_cap_points' => ['required', 'integer', 'min:0', 'max:60'],
                'games_to_win' => ['required', 'integer', 'min:1', 'max:5'],
            ]),
            ScoreboardType::Wrestling => $request->validate([
                'period_duration_seconds' => ['required', 'integer', 'min:30', 'max:600'],
                'rest_duration_seconds' => ['required', 'integer', 'min:10', 'max:300'],
                'total_periods' => ['required', 'integer', 'min:1', 'max:5'],
            ]),
            ScoreboardType::Tennis => $request->validate([
                'sets_to_win' => ['required', 'integer', Rule::in([2, 3])],
            ]),
            ScoreboardType::GoalBall => $request->validate([
                'minutes_per_half' => ['required', 'integer', 'min:3', 'max:20'],
            ]),
            ScoreboardType::Billiard => $request->validate([
                'racks_to_win' => ['required', 'integer', 'min:1', 'max:15'],
            ]),
            ScoreboardType::Bocce => $request->validate([
                'target_score' => ['required', 'integer', 'min:1', 'max:50'],
            ]),
            default => abort(422, __('This action is not available for this board type.')),
        };

        $state = [...($session->sport_state ?? []), ...$data];

        $session->forceFill(['sport_state' => $state])->save();

        $this->audit->record('scoring.settings_updated', $session, [...$this->context($session), ...$data]);

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Set or clear the possession/serve arrow — basketball (possession)
     * and every rally/point-scoring board's serve indicator (volleyball/
     * sepak takraw, table tennis/badminton) only. Same `sport_state.
     * possession` key and endpoint for all of them — a serve indicator is
     * the same "which side" concept as a possession arrow, just a
     * different on-court meaning, so this is shared rather than
     * duplicated per sport.
     */
    public function possession(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);

        $allowedBoardTypes = [
            ScoreboardType::Basketball,
            ScoreboardType::VolleyballSepakTakraw,
            ScoreboardType::RacketGames,
            ScoreboardType::Tennis,
        ];

        if (! in_array($session->boardType(), $allowedBoardTypes, true)) {
            abort(422, __('This action is only available for a basketball, volleyball/sepak takraw, table tennis/badminton, or tennis scoring session.'));
        }

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
     * Set the round/rest countdown clock — boxing and taekwondo/wushu/
     * pencak silat (CombatRounds), which share this exact shape. Manual, not
     * server-ticking, the same anchor+ticker shape as basketball's
     * gameClock()/shotClock() above. Two distinct actions in one endpoint:
     * passing `phase` starts that phase fresh (clock reset to the
     * configured round or rest duration — the "ding, round 2 begins" or
     * "ding, rest begins" moment); passing `seconds` instead just adjusts
     * the current phase's remaining time (a manual correction), leaving
     * `clock_phase` untouched. Not a score_events row — same restraint as
     * basketball's clocks, a frequent manual correction would flood the
     * feed.
     */
    public function roundClock(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBoxingOrCombatRounds($session);

        $data = $request->validate([
            'phase' => ['nullable', Rule::in(['round', 'rest'])],
            'seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
        ]);

        $state = $session->sport_state ?? [];

        if (($data['phase'] ?? null) !== null) {
            $state['clock_phase'] = $data['phase'];
            $state['clock_seconds'] = $data['phase'] === 'round'
                ? ($state['round_duration_seconds'] ?? 120)
                : ($state['rest_duration_seconds'] ?? 60);
        } elseif (($data['seconds'] ?? null) !== null) {
            $state['clock_seconds'] = $data['seconds'];
        }

        $state['clock_updated_at'] = now()->toIso8601String();

        $session->forceFill(['sport_state' => $state])->save();

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Sound the bell — boxing and taekwondo/wushu/pencak silat/arnis (CombatRounds),
     * the sport-correct term for the same "signal" concern basketball's
     * horn() covers. Rings to start/end a round or rest period; no other
     * state change, viewers flash on a fresh `bell_sounded_at`.
     */
    public function bell(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBoxingOrCombatRounds($session);

        $state = $session->sport_state ?? [];
        $state['bell_sounded_at'] = now()->toIso8601String();

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Bell,
            'recorded_by' => $user->id,
        ]);

        $this->audit->record('scoring.bell_sounded', $session, $this->context($session));

        broadcast(new ScoreUpdated($session))->toOthers();

        return back();
    }

    /**
     * Sound a manual horn/buzzer signal — basketball and wrestling only
     * (wrestling's real mat-side signal is a horn/buzzer too, the correct
     * term for it — unlike boxing/combat-rounds, which use "Bell"). No
     * other state change; viewers flash on a fresh `horn_sounded_at`.
     */
    public function horn(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);

        if (! in_array($session->boardType(), [ScoreboardType::Basketball, ScoreboardType::Wrestling], true)) {
            abort(422, __('This action is only available for a basketball or wrestling scoring session.'));
        }

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
     * on court), `on_court: false` removes them. Only allowed while the
     * session is paused (owner instruction) — mirrors a real scorer's
     * table, where substitutions happen on a dead ball, not mid-play; the
     * operator uses the Whistle (pause) button first.
     */
    public function lineup(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->authorizeManageSession($request, $session);
        $this->assertActive($session);
        $this->assertBasketball($session);
        $this->assertPaused($session);

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
     * Rally-point defaults differ by sport even though both share one
     * board: Volleyball is standard best-of-5 (25 points, win by 2, a
     * deciding 5th set to 15); Sepak Takraw is standard best-of-3 (21
     * points, deciding 3rd set also to 21) — real per-sport conventions,
     * both fully operator-adjustable afterward via `settings()`.
     *
     * @return array<string, mixed>
     */
    private function initialRallySetsState(string $sportName): array
    {
        $isSepakTakraw = mb_strtolower($sportName) === 'sepak takraw';

        return [
            'sets' => [],
            'current_set_score_a' => 0, 'current_set_score_b' => 0,
            'sets_won_a' => 0, 'sets_won_b' => 0,
            'set_target_points' => $isSepakTakraw ? 21 : 25,
            'deciding_set_target_points' => $isSepakTakraw ? 21 : 15,
            'sets_to_win' => $isSepakTakraw ? 2 : 3,
            'possession' => null,
        ];
    }

    /**
     * Racket-games defaults differ by sport: Table Tennis is standard
     * best-of-5 games to 11 points (win by 2, uncapped — a very long
     * deuce is real and allowed); Badminton is standard best-of-3 games
     * to 21 points, win by 2, but hard-capped at 30 (the leader at 30
     * wins outright even without a 2-point lead) — `hard_cap_points: 0`
     * means "no cap" (Table Tennis). Unlike volleyball/sepak takraw,
     * neither sport reduces the target for a deciding game, so there's
     * no separate `deciding_*_target_points` field here.
     *
     * @return array<string, mixed>
     */
    private function initialRacketGamesState(string $sportName): array
    {
        $isBadminton = mb_strtolower($sportName) === 'badminton';

        return [
            'games' => [],
            'current_game_score_a' => 0, 'current_game_score_b' => 0,
            'games_won_a' => 0, 'games_won_b' => 0,
            'game_target_points' => $isBadminton ? 21 : 11,
            'hard_cap_points' => $isBadminton ? 30 : 0,
            'games_to_win' => $isBadminton ? 2 : 3,
            'possession' => null,
        ];
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

    private function assertPaused(ScoringSession $session): void
    {
        if ($session->status !== ScoringSessionStatus::Paused) {
            throw ValidationException::withMessages([
                'status' => __('Pause the game before substituting a player.'),
            ]);
        }
    }

    /**
     * Boxing and CombatRounds (taekwondo/wushu/pencak silat/arnis) share this
     * one assertion — see the round()/roundClock()/bell() docblocks and
     * store()'s CombatRounds branch for why the two board types are
     * treated identically throughout this controller.
     */
    private function assertBoxingOrCombatRounds(ScoringSession $session): void
    {
        if (! in_array($session->boardType(), [ScoreboardType::Boxing, ScoreboardType::CombatRounds], true)) {
            abort(422, __('This action is only available for a boxing, taekwondo, wushu, pencak silat, or arnis scoring session.'));
        }
    }

    private function assertSoftballBaseball(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::SoftballBaseball) {
            abort(422, __('This action is only available for a softball/baseball scoring session.'));
        }
    }

    private function assertVolleyballSepakTakraw(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::VolleyballSepakTakraw) {
            abort(422, __('This action is only available for a volleyball/sepak takraw scoring session.'));
        }
    }

    private function assertFootballFutsal(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::FootballFutsal) {
            abort(422, __('This action is only available for a football/futsal scoring session.'));
        }
    }

    private function assertRacketGames(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::RacketGames) {
            abort(422, __('This action is only available for a table tennis/badminton scoring session.'));
        }
    }

    private function assertWrestling(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::Wrestling) {
            abort(422, __('This action is only available for a wrestling scoring session.'));
        }
    }

    private function assertTennis(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::Tennis) {
            abort(422, __('This action is only available for a tennis scoring session.'));
        }
    }

    private function assertGoalBall(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::GoalBall) {
            abort(422, __('This action is only available for a goal ball scoring session.'));
        }
    }

    private function assertBilliard(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::Billiard) {
            abort(422, __('This action is only available for a billiard scoring session.'));
        }
    }

    private function assertBocce(ScoringSession $session): void
    {
        if ($session->boardType() !== ScoreboardType::Bocce) {
            abort(422, __('This action is only available for a bocce scoring session.'));
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
     * Freeze every sport countdown at its authoritative remaining value.
     * Stored seconds are the value at the matching *_updated_at anchor,
     * not necessarily the value at the time pause/end is pressed.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function materializeCountdownClocks(array $state): array
    {
        foreach ([['game_clock_seconds', 'game_clock_updated_at'], ['shot_clock_seconds', 'shot_clock_updated_at'], ['clock_seconds', 'clock_updated_at']] as [$secondsKey, $anchorKey]) {
            if (! isset($state[$secondsKey]) || empty($state[$anchorKey])) {
                continue;
            }

            $elapsed = (int) floor(max(0, Carbon::parse($state[$anchorKey])->diffInSeconds(now())));
            $state[$secondsKey] = max(0, (int) $state[$secondsKey] - $elapsed);
            $state[$anchorKey] = null;
        }

        return $state;
    }

    /** @param array<string, mixed> $state @return array<string, mixed> */
    private function restartCountdownClocks(array $state): array
    {
        $now = now()->toIso8601String();
        foreach ([['game_clock_seconds', 'game_clock_updated_at'], ['shot_clock_seconds', 'shot_clock_updated_at'], ['clock_seconds', 'clock_updated_at']] as [$secondsKey, $anchorKey]) {
            if (isset($state[$secondsKey])) {
                $state[$anchorKey] = $now;
            }
        }

        return $state;
    }

    /**
     * Mirrors the "Matches — list" authorization row, plus a Technical
     * Official or Tournament Manager scoped to their own assigned sport
     * (view and manage use the identical scope here — neither role has a
     * reason to see a match outside the sport they operate).
     */
    private function authorizeView(Request $request, EventMatch $match): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasRole(UserRole::TechnicalOfficial, UserRole::TournamentManager)) {
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
     * Admin may manage any match's scoring; a Technical Official or
     * Tournament Manager only a match whose sport they operate
     * (`ScopesToAssignedSport::userOperatesSport()`); an Organizer only a
     * match whose meet+sport they hold an active Tournament Secretary or
     * Tournament ICT `MeetSportAssignment` for — a plain Organizer with no
     * such assignment is still denied. Shared by `board()`'s `canManage`
     * flag, `authorizeView()`'s Technical Official branch, and every
     * mutating action's own authorization check below — one definition of
     * "who may run this match's scoreboard."
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
        if ($user->hasRole(UserRole::TechnicalOfficial, UserRole::TournamentManager)) {
            return app(CompetitionAccessService::class)
                ->canAccessEvent($user, $match->event, $match->meet_id);
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
            ->exists()
            && app(CompetitionAccessService::class)
                ->canAccessEvent($user, $match->event, $match->meet_id);
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
