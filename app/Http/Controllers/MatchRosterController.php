<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\MatchRosterPlayer;
use App\Models\MeetSportAssignment;
use App\Models\ScoringSession;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * A match's basketball roster (starters + bench), sourced from real
 * Confirmed Entry rows for the match's event — never free text. Persists
 * independently of any ScoringSession's lifecycle. Authorization mirrors
 * ScoringSessionController::canManage() exactly (same idiom
 * ResultController::authorizeEncode() already established: re-implemented
 * per controller rather than shared, see docs/live-scoring.md).
 */
class MatchRosterController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * The full roster (both sides, starters and bench) plus the pool of
     * still-eligible confirmed entries — fetched on demand only, by the
     * operator console's substitution/manage-roster modal when it opens.
     * Deliberately not part of `board()`'s Inertia props or the live-polled
     * `scoring.show`/Reverb payload (`ScoringSession::onCourtPayload()` is
     * the lightweight one baked into those): the full roster is real data
     * an operator needs only while actively substituting, not on every 5s
     * tick, so this keeps the hot path down to just the players on court.
     */
    public function show(Request $request, EventMatch $match): JsonResponse
    {
        $this->authorizeManage($request, $match);

        $match->loadMissing('event');
        $entries = $match->entries()->with('athlete:id,school_id')->get();

        return response()->json([
            'roster' => MatchRosterPlayer::payloadForMatch($match->id),
            'eligibleAthletes' => $match->event->is_team_event
                ? $this->eligibleTeamAthletes($match)
                : $this->eligibleAthletes($match, $entries),
        ]);
    }

    /**
     * Add a registered, confirmed entry to the match's roster for a side.
     */
    public function store(Request $request, EventMatch $match): RedirectResponse
    {
        $this->authorizeManage($request, $match);

        $match->loadMissing('event');

        $data = $request->validate([
            'entry_id' => ['required', 'integer', Rule::exists('entries', 'id')],
            'side' => ['required', Rule::in(['a', 'b'])],
            'jersey_number' => ['nullable', 'string', 'max:10'],
            'is_starter' => ['nullable', 'boolean'],
        ]);

        $entry = Entry::query()->with('athlete.school')->findOrFail($data['entry_id']);

        if ($entry->event_id !== $match->event_id) {
            throw ValidationException::withMessages([
                'entry_id' => __('This entry is not registered for this match\'s event.'),
            ]);
        }

        if ($entry->status !== EntryStatus::Confirmed) {
            throw ValidationException::withMessages([
                'entry_id' => __('Only a confirmed entry can join the roster.'),
            ]);
        }

        $sideDelegationId = $match->event->is_team_event ? $this->sideDelegationId($match, $data['side']) : null;
        $validSide = $match->event->is_team_event
            ? ($sideDelegationId !== null
                ? $sideDelegationId === $entry->delegation_id
                : ($this->sideSchoolId($match, $data['side']) !== null
                    && $this->sideSchoolId($match, $data['side']) === $entry->athlete->school_id))
            : ($this->sideSchoolId($match, $data['side']) !== null
                && $this->sideSchoolId($match, $data['side']) === $entry->athlete->school_id);

        if (! $validSide) {
            throw ValidationException::withMessages([
                'entry_id' => __('This athlete does not belong to the selected team side.'),
            ]);
        }

        if (MatchRosterPlayer::query()->where('match_id', $match->id)->where('entry_id', $entry->id)->exists()) {
            throw ValidationException::withMessages([
                'entry_id' => __('This athlete is already on the roster.'),
            ]);
        }

        if (MatchRosterPlayer::query()->where('match_id', $match->id)->where('side', $data['side'])->count() >= 15) {
            throw ValidationException::withMessages([
                'entry_id' => __('This side\'s roster is already at the 15-player cap.'),
            ]);
        }

        $rosterPlayer = MatchRosterPlayer::create([
            'match_id' => $match->id,
            'entry_id' => $entry->id,
            'side' => $data['side'],
            'jersey_number' => $data['jersey_number'] ?? null,
            'is_starter' => $data['is_starter'] ?? false,
        ]);

        $this->audit->record('match_roster.added', $rosterPlayer, [
            'match_id' => $match->id,
            'athlete' => $entry->athlete->fullName(),
            'side' => $data['side'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player added to roster.')]);

        return back();
    }

    /**
     * Update a rostered player's jersey number and/or starter flag.
     */
    public function update(Request $request, MatchRosterPlayer $rosterPlayer): RedirectResponse
    {
        $rosterPlayer->loadMissing('match');
        $this->authorizeManage($request, $rosterPlayer->match);

        $data = $request->validate([
            'jersey_number' => ['nullable', 'string', 'max:10'],
            'is_starter' => ['nullable', 'boolean'],
        ]);

        $rosterPlayer->forceFill([
            'jersey_number' => $data['jersey_number'] ?? $rosterPlayer->jersey_number,
            'is_starter' => $data['is_starter'] ?? $rosterPlayer->is_starter,
        ])->save();

        $this->audit->record('match_roster.updated', $rosterPlayer, [
            'match_id' => $rosterPlayer->match_id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Roster player updated.')]);

        return back();
    }

    /**
     * Remove a player from the roster — blocked while they're on court or
     * have recorded stats in any session's sport_state, same "protect
     * deletion when dependent data exists" convention EntryController::
     * destroy() already uses for matches/placements.
     */
    public function destroy(Request $request, MatchRosterPlayer $rosterPlayer): RedirectResponse
    {
        $rosterPlayer->loadMissing('match', 'entry.athlete');
        $this->authorizeManage($request, $rosterPlayer->match);

        $hasLiveStats = ScoringSession::query()
            ->where('match_id', $rosterPlayer->match_id)
            ->get()
            ->contains(function (ScoringSession $session) use ($rosterPlayer): bool {
                $state = $session->sport_state ?? [];
                $id = (string) $rosterPlayer->id;

                return in_array($rosterPlayer->id, $state['on_court_a'] ?? [], true)
                    || in_array($rosterPlayer->id, $state['on_court_b'] ?? [], true)
                    || ($state['player_points'][$id] ?? 0) > 0
                    || ($state['player_fouls'][$id] ?? 0) > 0;
            });

        if ($hasLiveStats) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This player has recorded stats or is on court — bench them first.'),
            ]);

            return back();
        }

        $context = [
            'match_id' => $rosterPlayer->match_id,
            'athlete' => $rosterPlayer->entry->athlete->fullName(),
        ];

        $rosterPlayer->delete();

        $this->audit->record('match_roster.removed', null, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Player removed from roster.')]);

        return back();
    }

    /**
     * The school id representing a given side, derived exactly the way
     * ScoringSessionController::board()'s `suggestedLabels` already does:
     * the two representative match_entries rows, positionally A/B — only
     * meaningful when the match has exactly two confirmed entries.
     */
    private function sideSchoolId(EventMatch $match, string $side): ?int
    {
        $entries = $match->entries()->with('athlete:id,school_id')->get();

        if ($entries->count() !== 2) {
            return null;
        }

        return $side === 'a' ? $entries[0]->athlete->school_id : $entries[1]->athlete->school_id;
    }

    private function sideDelegationId(EventMatch $match, string $side): ?int
    {
        $teams = $match->teamEntries()->orderBy('match_team_entries.id')->get();

        if ($teams->count() !== 2) {
            return null;
        }

        return $side === 'a' ? $teams[0]->delegation_id : $teams[1]->delegation_id;
    }

    /** @return array{a: array<int, array{id: int, label: string}>, b: array<int, array{id: int, label: string}>} */
    private function eligibleTeamAthletes(EventMatch $match): array
    {
        $teams = $match->teamEntries()->with('members.entry.athlete')->orderBy('match_team_entries.id')->get();
        if ($teams->count() !== 2) {
            return $this->eligibleAthletes($match, $match->entries()->with('athlete:id,school_id')->get());
        }

        $rosteredEntryIds = MatchRosterPlayer::query()->where('match_id', $match->id)->pluck('entry_id');
        $payload = fn ($team): array => $team->members
            ->whereNotIn('entry_id', $rosteredEntryIds)
            ->filter(fn ($member): bool => $member->entry?->status === EntryStatus::Confirmed)
            ->map(fn ($member): array => [
                'id' => $member->entry_id,
                'label' => $member->entry->athlete->fullName(),
            ])->values()->all();

        return ['a' => $payload($teams[0]), 'b' => $payload($teams[1])];
    }

    /**
     * Confirmed entries for this match's event, per side, minus whoever's
     * already rostered — the pool `store()` can add from. Only meaningful
     * when the match has exactly two representative entries (same guard
     * `sideSchoolId()` uses); anything else returns both sides empty so
     * the frontend can prompt the operator to set match participants
     * first.
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
     * Same shape as ScoringSessionController::canManage() — Admin any
     * match, a Technical Official scoped to their own assigned sport, an
     * Organizer only via an active Tournament Secretary/ICT assignment.
     * Deliberately duplicated rather than shared (see class docblock).
     */
    private function canManage(User $user, EventMatch $match): bool
    {
        if ($user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->role === UserRole::TechnicalOfficial) {
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

    private function authorizeManage(Request $request, EventMatch $match): void
    {
        /** @var User $user */
        $user = $request->user();

        $match->loadMissing('event');

        abort_unless($this->canManage($user, $match), 403);
    }
}
