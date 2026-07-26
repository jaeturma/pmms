<?php

namespace App\Http\Controllers;

use App\Enums\MatchStatus;
use App\Enums\ScoreboardType;
use App\Enums\ScoreEventType;
use App\Enums\ScoringSessionStatus;
use App\Enums\UserRole;
use App\Events\ScoreUpdated;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
 * "Matches — list" rule exactly (docs/authorization.md): Admin/Organizer
 * any match, Delegation Officer their own delegation's matches only,
 * Viewer never. Mutations reuse the same role:admin,organizer gate match
 * management already uses.
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

        $match->loadMissing([
            'meet:id,name',
            'event.sport:id,name',
            'entries.athlete:id,first_name,last_name,school_id',
            'entries.athlete.school:id,name',
        ]);

        $session = $match->scoringSessions()->latest('id')->first();
        $entries = $match->entries;

        return Inertia::render('scoring/show', [
            'match' => [
                'id' => $match->id,
                'meet' => $match->meet->name,
                'event' => sprintf('%s — %s', $match->event->sport->name, $match->event->name),
                'round_label' => $match->round_label,
                'status' => $match->status->value,
                'is_scheduled' => $match->status === MatchStatus::Scheduled,
            ],
            'suggestedLabels' => $entries->count() === 2 ? [
                $entries[0]->athlete->school->name,
                $entries[1]->athlete->school->name,
            ] : [null, null],
            'session' => $session === null ? null : $session->toLivePayload(),
            'channel' => "match.{$match->id}.scoring",
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Start a new session for a scheduled match. Only one active
     * (non-ended) session per match is allowed.
     */
    public function store(Request $request, EventMatch $match): RedirectResponse
    {
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
        ]);

        /** @var User $user */
        $user = $request->user();

        $session = ScoringSession::create([
            'match_id' => $match->id,
            'side_a_label' => $data['side_a_label'],
            'side_b_label' => $data['side_b_label'],
            'status' => ScoringSessionStatus::InProgress,
            'started_by' => $user->id,
            'started_at' => now(),
        ]);

        if ($session->boardType() === ScoreboardType::Basketball) {
            $session->forceFill(['sport_state' => ['fouls_a' => 0, 'fouls_b' => 0]])->save();
        }

        $this->audit->record('scoring.started', $session, $this->context($session));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Live scoring started.')]);

        return back();
    }

    /**
     * Record a point or a correction. Corrections require a reason.
     */
    public function score(Request $request, ScoringSession $session): RedirectResponse
    {
        $this->assertActive($session);

        $data = $request->validate([
            'type' => ['required', Rule::in([ScoreEventType::Point->value, ScoreEventType::Correction->value])],
            'side' => ['required', Rule::in(['a', 'b'])],
            'delta' => ['required', 'integer'],
            'reason' => ['required_if:type,'.ScoreEventType::Correction->value, 'nullable', 'string', 'max:500'],
        ]);

        $column = $data['side'] === 'a' ? 'score_a' : 'score_b';
        $newValue = max(0, $session->{$column} + $data['delta']);

        $session->forceFill([$column => $newValue])->save();

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
        $this->assertActive($session);

        $session->forceFill(['status' => ScoringSessionStatus::Paused])->save();

        $this->recordSimpleEvent($request, $session, ScoreEventType::Paused, 'scoring.paused');

        return back();
    }

    public function resume(Request $request, ScoringSession $session): RedirectResponse
    {
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
        $this->assertActive($session);
        $this->assertBasketball($session);

        $data = $request->validate([
            'action' => ['required', Rule::in(['add', 'reset'])],
            'side' => ['required_if:action,add', 'nullable', Rule::in(['a', 'b'])],
        ]);

        $state = $session->sport_state ?? ['fouls_a' => 0, 'fouls_b' => 0];

        if ($data['action'] === 'add') {
            $column = $data['side'] === 'a' ? 'fouls_a' : 'fouls_b';
            $state[$column] = ($state[$column] ?? 0) + 1;
        } else {
            $state['fouls_a'] = 0;
            $state['fouls_b'] = 0;
        }

        $session->forceFill(['sport_state' => $state])->save();

        /** @var User $user */
        $user = $request->user();

        ScoreEvent::create([
            'scoring_session_id' => $session->id,
            'type' => ScoreEventType::Foul,
            'payload' => [...$data, 'fouls_a' => $state['fouls_a'], 'fouls_b' => $state['fouls_b']],
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
     * Mirrors the "Matches — list" authorization row exactly.
     */
    private function authorizeView(Request $request, EventMatch $match): void
    {
        Gate::authorize('viewAny', Entry::class);

        /** @var User $user */
        $user = $request->user();

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
