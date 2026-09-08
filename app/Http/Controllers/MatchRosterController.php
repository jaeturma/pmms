<?php

namespace App\Http\Controllers;

use App\Enums\DelegationStatus;
use App\Enums\EntryStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Delegation;
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

        // The operator may explicitly load a chosen team's athletes — the
        // way out when the match itself carries no usable Team Entry / entry
        // links. `a_delegation_id` / `b_delegation_id` (when given) replace
        // that side's pool with every Confirmed Entry that Delegation holds
        // for this event.
        $data = $request->validate([
            'a_delegation_id' => ['nullable', 'integer', Rule::exists('delegations', 'id')],
            'b_delegation_id' => ['nullable', 'integer', Rule::exists('delegations', 'id')],
        ]);

        $default = ($match->event?->is_team_event ?? false)
            ? $this->eligibleTeamAthletes($match)
            : $this->eligibleAthletes($match, $entries);

        return response()->json([
            'roster' => MatchRosterPlayer::payloadForMatch($match->id),
            'eligibleAthletes' => [
                'a' => isset($data['a_delegation_id'])
                    ? $this->eligibleForDelegation($match, (int) $data['a_delegation_id'])
                    : $default['a'],
                'b' => isset($data['b_delegation_id'])
                    ? $this->eligibleForDelegation($match, (int) $data['b_delegation_id'])
                    : $default['b'],
            ],
            // Every active Delegation in the Meet, so the console can offer a
            // "load this team's athletes" picker for either side.
            'teamOptions' => $this->meetDelegationOptions($match),
            // What each side resolves to today (an explicit pick, else the
            // Team Entry / representative-entry Delegation) so the picker can
            // show the current selection.
            'selectedDelegations' => [
                'a' => isset($data['a_delegation_id'])
                    ? (int) $data['a_delegation_id']
                    : $this->derivedDelegationId($match, 'a', $entries),
                'b' => isset($data['b_delegation_id'])
                    ? (int) $data['b_delegation_id']
                    : $this->derivedDelegationId($match, 'b', $entries),
            ],
        ]);
    }

    /**
     * Add a player to the match's roster for a side. Normally a registered,
     * Confirmed Entry; when the operator supplies `manual_name` instead
     * (the fallback for a match with no usable registration link) the row
     * is stored with a null `entry_id` and the hand-typed name.
     */
    public function store(Request $request, EventMatch $match): RedirectResponse
    {
        $this->authorizeManage($request, $match);

        $match->loadMissing('event');

        $data = $request->validate([
            'entry_id' => ['nullable', 'required_without:manual_name', 'integer', Rule::exists('entries', 'id')],
            'manual_name' => ['nullable', 'required_without:entry_id', 'string', 'max:60'],
            // The operator's assertion of which Delegation this side is —
            // lets a linked Entry be rostered even when the match carries no
            // Team Entry / representative entries to derive the side from.
            'delegation_id' => ['nullable', 'integer', Rule::exists('delegations', 'id')],
            'side' => ['required', Rule::in(['a', 'b'])],
            'jersey_number' => ['nullable', 'string', 'max:10'],
            'is_starter' => ['nullable', 'boolean'],
        ]);

        if (($data['manual_name'] ?? null) !== null) {
            return $this->storeManualPlayer($match, $data);
        }

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

        // A soft-deleted athlete is not selectable — deleted entities do
        // not regain operational authority (they may still appear as a
        // name in historical roster display, but never here).
        if ($entry->athlete === null) {
            throw ValidationException::withMessages([
                'entry_id' => __('This entry’s athlete record is unavailable. Repair the athlete link first.'),
            ]);
        }

        if (($data['delegation_id'] ?? null) !== null) {
            // The operator explicitly loaded this side from a chosen team —
            // trust that assertion, only checking the entry really is that
            // Delegation's.
            if ($entry->delegation_id !== (int) $data['delegation_id']) {
                throw ValidationException::withMessages([
                    'entry_id' => __('This athlete is not registered under the selected team.'),
                ]);
            }
        } else {
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
     * Add a hand-typed player — the operator's fallback when a team's
     * registration link is missing or broken and there is no Entry to
     * roster. Stored with a null `entry_id`; still subject to the same
     * 15-per-side cap and audited (`manual: true`).
     *
     * @param  array<string, mixed>  $data
     */
    private function storeManualPlayer(EventMatch $match, array $data): RedirectResponse
    {
        $name = trim((string) $data['manual_name']);

        if ($name === '') {
            throw ValidationException::withMessages([
                'manual_name' => __('Enter the player\'s name.'),
            ]);
        }

        if (MatchRosterPlayer::query()->where('match_id', $match->id)->where('side', $data['side'])->count() >= 15) {
            throw ValidationException::withMessages([
                'manual_name' => __('This side\'s roster is already at the 15-player cap.'),
            ]);
        }

        $rosterPlayer = MatchRosterPlayer::create([
            'match_id' => $match->id,
            'entry_id' => null,
            'manual_name' => $name,
            'side' => $data['side'],
            'jersey_number' => $data['jersey_number'] ?? null,
            'is_starter' => $data['is_starter'] ?? false,
        ]);

        $this->audit->record('match_roster.added', $rosterPlayer, [
            'match_id' => $match->id,
            'athlete' => $name,
            'side' => $data['side'],
            'manual' => true,
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
            'athlete' => $rosterPlayer->entry?->athlete?->fullName() ?? $rosterPlayer->manual_name ?? __('Data incomplete'),
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

        return $side === 'a' ? $entries[0]->athlete?->school_id : $entries[1]->athlete?->school_id;
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

        $rosteredEntryIds = MatchRosterPlayer::query()->where('match_id', $match->id)->whereNotNull('entry_id')->pluck('entry_id');
        $payload = fn ($team): array => $team->members
            ->whereNotIn('entry_id', $rosteredEntryIds)
            ->filter(fn ($member): bool => $member->entry?->status === EntryStatus::Confirmed && $member->entry?->athlete !== null)
            ->map(fn ($member): array => [
                'id' => $member->entry_id,
                'label' => $member->entry?->athlete?->fullName() ?? __('Missing athlete'),
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
            ->whereNotNull('entry_id')
            ->pluck('entry_id');

        $schoolIdFor = fn (int $index): ?int => $entries[$index]->athlete?->school_id;

        $poolFor = function (?int $schoolId) use ($match, $rosteredEntryIds): array {
            if ($schoolId === null) {
                return [];
            }

            return Entry::query()
                ->where('event_id', $match->event_id)
                ->where('status', EntryStatus::Confirmed->value)
                ->whereHas('athlete', fn ($query) => $query->where('school_id', $schoolId))
                ->whereNotIn('id', $rosteredEntryIds)
                ->with('athlete')
                ->get()
                ->filter(fn (Entry $entry): bool => $entry->athlete !== null)
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
     * Every Confirmed Entry a given Delegation holds for this match's
     * event, minus whoever is already rostered — the pool the operator
     * gets after explicitly loading a chosen team for a side. Real
     * registration data only; empty when that Delegation registered
     * nobody for the event (the operator then adds players by hand).
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function eligibleForDelegation(EventMatch $match, int $delegationId): array
    {
        $rosteredEntryIds = MatchRosterPlayer::query()
            ->where('match_id', $match->id)
            ->whereNotNull('entry_id')
            ->pluck('entry_id');

        return Entry::query()
            ->where('event_id', $match->event_id)
            ->where('delegation_id', $delegationId)
            ->where('status', EntryStatus::Confirmed->value)
            ->whereNotIn('id', $rosteredEntryIds)
            ->with('athlete')
            ->get()
            ->filter(fn (Entry $entry): bool => $entry->athlete !== null)
            ->map(fn (Entry $entry): array => [
                'id' => (int) $entry->id,
                'label' => $entry->athlete->fullName(),
            ])
            ->sortBy('label')
            ->values()
            ->all();
    }

    /**
     * The Delegation a side resolves to without an explicit pick — its
     * Team Entry's Delegation (team event) or its representative entry's
     * Delegation, or null when neither is set.
     *
     * @param  Collection<int, Entry>  $entries
     */
    private function derivedDelegationId(EventMatch $match, string $side, $entries): ?int
    {
        if ($match->event?->is_team_event) {
            $id = $this->sideDelegationId($match, $side);

            return $id === null ? null : (int) $id;
        }

        if ($entries->count() !== 2) {
            return null;
        }

        $id = $side === 'a' ? $entries[0]->delegation_id : $entries[1]->delegation_id;

        return $id === null ? null : (int) $id;
    }

    /**
     * Every active Delegation in this match's Meet — the pool the console
     * offers for "load this team's athletes". Mirrors
     * ScoringSessionController::meetDelegationOptions() (duplicated per
     * controller, see class docblock).
     *
     * @return array<int, array{id: int, label: string}>
     */
    private function meetDelegationOptions(EventMatch $match): array
    {
        return Delegation::query()
            ->where('meet_id', $match->meet_id)
            ->whereIn('status', [DelegationStatus::Submitted->value, DelegationStatus::Approved->value])
            ->get()
            ->map(fn (Delegation $delegation): array => [
                'id' => $delegation->id,
                'label' => $delegation->registrantName() ?? __('Missing delegation'),
            ])
            ->sortBy('label')
            ->values()
            ->all();
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

        // A Tournament ICT / Technical Official / Tournament Manager /
        // Tournament Secretary manages a match's roster whenever they can
        // access its event — the same rule
        // `ScoringSessionController::canManage()` uses for the scoreboard
        // itself, so the substitution modal never 403s on an operator who
        // can already run the board.
        if ($user->hasRole(
            UserRole::TechnicalOfficial,
            UserRole::TournamentManager,
            UserRole::TournamentICT,
            UserRole::TournamentSecretary,
        )) {
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
