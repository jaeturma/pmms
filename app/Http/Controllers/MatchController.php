<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\MatchStatus;
use App\Enums\ScoreboardType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ScopesToAssignedSport;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\MatchRequest;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    use ScopesToAssignedSport, SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Match list, mirroring entry visibility: managers see all, officers
     * only matches involving their delegation, viewers none. A Technical
     * Official or Tournament Manager is a different kind of scoped access
     * (the sport(s) they operate, not a delegation) and doesn't go through
     * Entry's viewAny gate at all — neither has any business seeing the
     * Entries page this gate also protects, only the matches for the
     * sport(s) they operate.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->tournamentEventIds()->isEmpty()) {
            Gate::authorize('viewAny', Entry::class);
        }

        $canManageAll = Gate::allows('manage-meet-data');
        $visibleEventIds = $user->tournamentEventIds();
        $coachDelegationIds = collect();
        if ($user->role === UserRole::Coach) {
            $visibleEventIds = $user->approvedCoachEventIds();
            $coachDelegationIds = $user->approvedCoachDelegationIds();
        }
        $isTournamentScoped = $user->role === UserRole::Coach
            || (! $user->isAdmin() && $visibleEventIds->isNotEmpty());
        $access = app(CompetitionAccessService::class);
        $canManageAssignedCompetition = $access->hasAssignmentRole(
            $user,
            $access->competitionManagerRoles(),
            Meet::current()->id,
        );

        $eventId = $request->integer('event_id');

        $query = EventMatch::query()->real()
            ->with([
                'event.sport:id,name',
                'schedule.venue:id,name',
                'result:id,match_id',
                'entries.athlete:id,first_name,last_name,school_id',
                'entries.athlete.school:id,name',
            ])
            ->orderBy('event_id')
            ->orderBy('sequence')
            ->orderByDesc('id');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'entries.delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        if ($user->role === UserRole::Coach) {
            $query->whereIn('event_id', $visibleEventIds)
                ->whereHas('entries', fn ($entries) => $entries->whereIn('delegation_id', $coachDelegationIds));
        }

        if ($isTournamentScoped) {
            $query->whereIn('event_id', $visibleEventIds);
        }

        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }

        return Inertia::render('matches/index', [
            'matches' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (EventMatch $match): array => [
                    'id' => $match->id,
                    'event_id' => $match->event_id,
                    'event_schedule_id' => $match->event_schedule_id,
                    'event' => $this->eventLabel($match->event),
                    'round_label' => $match->round_label,
                    'sequence' => $match->sequence,
                    'competition_area' => $match->competition_area,
                    'live_scoring_enabled' => $match->live_scoring_enabled,
                    'live_score_available' => ScoreboardType::supportsLiveSport($match->event->sport->name),
                    'awards_medals' => $match->awards_medals,
                    'status' => $match->status->value,
                    'status_label' => $match->status->label(),
                    'schedule_label' => $match->schedule === null ? null : sprintf(
                        '%s %s–%s · %s',
                        $match->schedule->scheduled_date->format('M j'),
                        substr($match->schedule->starts_at, 0, 5),
                        substr($match->schedule->ends_at, 0, 5),
                        $match->schedule->venue->name,
                    ),
                    'participants' => $match->entries
                        ->map(fn (Entry $entry): array => [
                            'entry_id' => $entry->id,
                            'name' => $entry->athlete->fullName(),
                            'school' => $entry->athlete->school->name,
                        ])
                        ->sortBy('name')
                        ->values()
                        ->all(),
                    'transitions' => $match->status->allowedTransitions() === [] ? [] : array_map(
                        fn (MatchStatus $status): array => [
                            'value' => $status->value,
                            'action_label' => $status->actionLabel(),
                        ],
                        $match->status->allowedTransitions(),
                    ),
                    'is_scheduled' => $match->status === MatchStatus::Scheduled,
                    'can_remove' => $this->canRemoveMatch($user, $match),
                    'can_delete' => $match->result === null && $this->canRemoveMatch($user, $match),
                ]),
            'filters' => [
                'event_id' => $eventId > 0 ? $eventId : null,
            ],
            'eventOptions' => Event::query()->real()
                ->whereHas('meets', fn ($meets) => $meets->whereKey(Meet::current()->id))
                ->when($isTournamentScoped, fn ($query) => $query->whereKey($visibleEventIds))
                ->with('sport:id,name')
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division'])
                ->map(fn (Event $event): array => [
                    'id' => $event->id,
                    'label' => $this->eventLabel($event),
                    'live_score_available' => ScoreboardType::supportsLiveSport($event->sport->name),
                ])
                ->values(),
            'scheduleOptions' => EventSchedule::query()->real()
                ->when(
                    $isTournamentScoped,
                    fn ($query) => $query->whereIn('event_id', $visibleEventIds),
                )
                ->with('venue:id,name')
                ->get()
                ->map(fn (EventSchedule $slot): array => [
                    'id' => $slot->id,
                    'event_id' => $slot->event_id,
                    'label' => sprintf(
                        '%s %s–%s · %s',
                        $slot->scheduled_date->format('M j'),
                        substr($slot->starts_at, 0, 5),
                        substr($slot->ends_at, 0, 5),
                        $slot->venue->name,
                    ),
                ])
                ->values(),
            'entryOptions' => Entry::query()
                ->where('status', EntryStatus::Confirmed->value)
                ->when(
                    $isTournamentScoped,
                    fn ($query) => $query->whereIn('event_id', $visibleEventIds),
                )
                ->when($user->role === UserRole::Coach, fn ($query) => $query
                    ->whereIn('delegation_id', $coachDelegationIds))
                ->with([
                    'athlete:id,first_name,last_name,school_id',
                    'athlete.school:id,name',
                ])
                ->get()
                ->map(fn (Entry $entry): array => [
                    'id' => $entry->id,
                    'event_id' => $entry->event_id,
                    'label' => "{$entry->athlete->fullName()} — {$entry->athlete->school->name}",
                ])
                ->sortBy('label')
                ->values(),
            'canManage' => $canManageAll || $canManageAssignedCompetition,
        ]);
    }

    /**
     * Create a match for an event that runs in the meet.
     */
    public function store(MatchRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->authorizeManage($request, (int) $data['event_id']);
        $this->assertMatchIsValid($data);

        $match = EventMatch::create($data);

        $this->audit->record('match.created', $match, $this->context($match));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match created.')]);

        return back();
    }

    /**
     * Update a match's round, sequence, or schedule slot.
     */
    public function update(MatchRequest $request, EventMatch $match): RedirectResponse
    {
        $data = $request->validated();

        $match->loadMissing('event');
        $this->authorizeManage(
            $request,
            $match->event_id,
            (int) $data['event_id'],
        );
        $this->assertMatchIsValid($data);

        $match->update($data);

        $this->audit->record('match.updated', $match, $this->context($match));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match updated.')]);

        return back();
    }

    /**
     * Replace the match's participants with confirmed entries of its event.
     */
    public function syncParticipants(Request $request, EventMatch $match): RedirectResponse
    {
        $match->loadMissing('event');
        $this->authorizeManage($request, $match->event_id);

        $validated = $request->validate([
            'entry_ids' => ['array'],
            'entry_ids.*' => ['integer', 'distinct', Rule::exists('entries', 'id')],
        ]);

        if ($match->status !== MatchStatus::Scheduled) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Participants can only be changed while the match is scheduled.'),
            ]);

            return back();
        }

        /** @var array<int, int> $entryIds */
        $entryIds = $validated['entry_ids'] ?? [];

        $entries = Entry::query()
            ->with(['athlete:id,first_name,last_name,school_id', 'delegation:id,meet_id'])
            ->whereIn('id', $entryIds)
            ->get();

        foreach ($entries as $entry) {
            if ($entry->event_id !== $match->event_id
                || $entry->delegation->meet_id !== $match->meet_id) {
                throw ValidationException::withMessages([
                    'entry_ids' => __(':name is not entered in this match\'s event.', [
                        'name' => $entry->athlete->fullName(),
                    ]),
                ]);
            }

            if ($entry->status !== EntryStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'entry_ids' => __('Only confirmed entries can join a match (:name is :status).', [
                        'name' => $entry->athlete->fullName(),
                        'status' => $entry->status->label(),
                    ]),
                ]);
            }
        }

        $match->loadMissing('event');

        // Keyed on each entry's own athlete's home school — correct
        // whether the athletes' delegation registered as a single school
        // (City) or a municipality pooling several schools (Province):
        // either way, two entries from the same school are still blocked,
        // and entries from different schools under the same municipal
        // delegation are correctly allowed.
        if ($match->event->is_team_event
            && $entries->pluck('athlete.school_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'entry_ids' => __('Team events allow one entry per school in a match.'),
            ]);
        }

        $match->entries()->sync($entryIds);

        $this->audit->record('match.participants_updated', $match, [
            ...$this->context($match),
            'participants' => count($entryIds),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match participants updated.')]);

        return back();
    }

    /**
     * Move the match to a terminal status, rejecting invalid transitions.
     */
    public function updateStatus(Request $request, EventMatch $match): RedirectResponse
    {
        $match->loadMissing('event');
        $this->authorizeManage($request, $match->event_id);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(MatchStatus::class)],
        ]);

        $target = MatchStatus::from($validated['status']);

        if (! $match->status->canTransitionTo($target)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('That status change is not allowed.'),
            ]);

            return back();
        }

        $from = $match->status;
        $match->forceFill(['status' => $target])->save();

        $this->audit->record('match.status_changed', $match, [
            ...$this->context($match),
            'from' => $from->value,
            'to' => $target->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match status updated.')]);

        return back();
    }

    /**
     * Delete a match.
     */
    public function destroy(Request $request, EventMatch $match): RedirectResponse
    {
        $match->loadMissing('event');
        /** @var User $user */
        $user = $request->user();
        abort_unless($this->canRemoveMatch($user, $match), 403);

        if ($match->result()->exists()) {
            throw ValidationException::withMessages([
                'match' => __('This match has a result. An administrator must delete the result before the match can be removed.'),
            ]);
        }

        $context = $this->context($match);

        DB::transaction(function () use ($match): void {
            $match->scoringSessions()->delete();
            $match->delete();
        }, 3);

        $this->audit->record('match.deleted', $match, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match deleted.')]);

        return back();
    }

    /**
     * Admin/Organizer may manage any match; a Tournament Manager only a
     * match whose sport(s) — the match's current sport and, on `update()`,
     * any target sport in the payload — they operate
     * (`ScopesToAssignedSport::userOperatesSport()`). A Technical Official
     * has no match-management access at all: their scope is running an
     * existing match's live scoring, not creating/editing/deleting the
     * match record itself.
     */
    private function authorizeManage(Request $request, int ...$eventIds): void
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        abort_unless(
            app(CompetitionAccessService::class)->hasAssignmentRole(
                $user,
                app(CompetitionAccessService::class)->competitionManagerRoles(),
                Meet::current()->id,
            )
                && Event::query()->whereKey($eventIds)->get()->count() === count(array_unique($eventIds))
                && Event::query()->whereKey($eventIds)->get()->every(
                    fn (Event $event): bool => app(CompetitionAccessService::class)
                        ->canAccessEvent($user, $event, Meet::current()->id),
                ),
            403,
        );
    }

    private function canRemoveMatch(User $user, EventMatch $match): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $access = app(CompetitionAccessService::class);

        return $access->hasAssignmentRole(
            $user,
            [MeetSportAssignmentRole::TournamentICT->value],
            $match->meet_id,
        ) && $access->canAccessEvent($user, $match->event, $match->meet_id);
    }

    /**
     * Shared rules for create/update: event in meet, slot of same meet+event.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertMatchIsValid(array $data): void
    {
        $meet = Meet::current();

        if (! $meet->events()->whereKey($data['event_id'])->exists()) {
            throw ValidationException::withMessages([
                'event_id' => __('That event is not part of the selected meet.'),
            ]);
        }

        if (! empty($data['event_schedule_id'])) {
            $slot = EventSchedule::query()->findOrFail((int) $data['event_schedule_id']);

            if ($slot->meet_id !== (int) $data['meet_id'] || $slot->event_id !== (int) $data['event_id']) {
                throw ValidationException::withMessages([
                    'event_schedule_id' => __('That schedule slot belongs to a different meet or event.'),
                ]);
            }
        }
    }

    private function eventLabel(Event $event): string
    {
        $event->loadMissing('sport:id,name');

        return sprintf(
            '%s — %s (%s, %s)',
            $event->sport->name,
            $event->name,
            $event->gender->label(),
            $event->age_division->label(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function context(EventMatch $match): array
    {
        $match->loadMissing(['meet:id,name', 'event:id,sport_id,name']);

        return [
            'meet' => $match->meet->name,
            'event' => $match->event->name,
            'round' => $match->round_label,
            'sequence' => $match->sequence,
        ];
    }
}
