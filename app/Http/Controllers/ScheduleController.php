<?php

namespace App\Http\Controllers;

use App\Enums\MeetStatus;
use App\Enums\ScoreboardType;
use App\Enums\ScoringSessionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ScopesToAssignedSport;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\ScheduleRequest;
use App\Models\CompetitionArea;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventSchedule;
use App\Models\EventVenue;
use App\Models\Meet;
use App\Models\MeetSportVenue;
use App\Models\SportCategory;
use App\Models\SportCategoryCompetitionArea;
use App\Models\User;
use App\Models\Venue;
use App\Services\AuditLogger;
use App\Services\CompetitionAccessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    use ScopesToAssignedSport, SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * The meet schedule, filterable per day and per venue, readable by all
     * roles. Unlike Matches/Results, the list itself is never sport-scoped
     * (every role sees the whole meet schedule) — a Tournament Manager's
     * narrower "own sport only" write access is expressed per-row via
     * `can_manage` instead, since a global `canManage` boolean can't tell
     * their own sport's slots from every other sport's on this shared list.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $canManageAll = Gate::allows('manage-meet-data') || $user->canManageProductionAccounts();
        $access = app(CompetitionAccessService::class);
        $visibleEventIds = $user->tournamentEventIds();
        if ($user->role === UserRole::Coach) {
            $visibleEventIds = $user->approvedCoachEventIds();
        }
        $isTournamentScoped = $user->role === UserRole::Coach
            || (! $user->hasRole(UserRole::Admin, UserRole::Organizer)
                && $visibleEventIds->isNotEmpty());
        $canManageAssignedCompetition = $access->hasAssignmentRole(
            $user,
            $access->competitionManagerRoles(),
            Meet::current()->id,
        );

        $search = $this->searchTerm($request);
        $venueId = $request->integer('venue_id');
        $date = $request->string('date')->toString();

        $query = EventSchedule::query()->real()
            ->with(['event.sport:id,name', 'sportCategory:id,display_name', 'venue:id,name', 'competitionArea:id,name'])
            ->when($isTournamentScoped, fn ($schedules) => $schedules->whereIn('event_id', $visibleEventIds))
            ->orderBy('scheduled_date')
            ->orderBy('starts_at');

        if ($venueId > 0) {
            $query->where('venue_id', $venueId);
        }

        if ($date !== '') {
            $query->whereDate('scheduled_date', $date);
        }

        $this->applySearch($query, $search, ['event.name']);

        $meet = Meet::current();
        $meetIsSchedulable = in_array($meet->status, [MeetStatus::RegistrationClosed, MeetStatus::Active], true);

        // Older meet setup uses the explicit meet_events pivot, while the
        // production import enables whole sports through meet_sports. Both
        // are valid sources for the event picker.
        $meetSportIds = $meet->meetSports()->where('active', true)->pluck('sport_id');
        $schedulableEvents = Event::query()->real()
            ->where('active', true)
            ->where(function ($query) use ($meet, $meetSportIds): void {
                $query->whereHas('meets', fn ($meets) => $meets->whereKey($meet->id))
                    ->when($meetSportIds->isNotEmpty(), fn ($events) => $events->orWhereIn('sport_id', $meetSportIds));
            })
            ->when($isTournamentScoped, fn ($query) => $query->whereKey($visibleEventIds))
            ->with('sport:id,name')
            ->orderBy('sport_id')
            ->orderBy('name')
            ->get(['id', 'sport_id', 'name', 'gender', 'age_division']);

        $eventVenueAssignments = EventVenue::query()
            ->whereIn('event_id', $schedulableEvents->pluck('id'))
            ->whereHas('venue', fn ($venues) => $venues->where('active', true))
            ->with('venue:id,name')
            ->get()
            ->groupBy('event_id');

        $sportVenueAssignments = MeetSportVenue::query()
            ->whereHas('meetSport', fn ($meetSports) => $meetSports
                ->where('meet_id', $meet->id)
                ->where('active', true))
            ->whereHas('venue', fn ($venues) => $venues->where('active', true))
            ->with([
                'venue:id,name',
                'venue.competitionAreas:id,venue_id,area_type',
                'meetSport:id,sport_id',
            ])
            ->get()
            ->groupBy(fn (MeetSportVenue $assignment): int => $assignment->meetSport->sport_id);

        $categoryAreaAssignments = SportCategoryCompetitionArea::query()
            ->where('status', 'active')
            ->whereHas('meetSport', fn ($meetSports) => $meetSports
                ->where('meet_id', $meet->id)
                ->where('active', true))
            ->whereHas('venue', fn ($venues) => $venues->where('active', true))
            ->whereHas('competitionArea', fn ($areas) => $areas->where('status', '!=', 'unavailable'))
            ->with(['meetSport:id,sport_id', 'venue:id,name', 'competitionArea:id,venue_id,area_type'])
            ->get()
            ->groupBy(fn (SportCategoryCompetitionArea $assignment): int => $assignment->meetSport->sport_id);
        $configuredSportIds = $categoryAreaAssignments->keys();
        $configuredCategoryIds = $categoryAreaAssignments->flatten(1)->pluck('sport_category_id')->unique();

        $venueOptions = $schedulableEvents->flatMap(function (Event $event) use ($categoryAreaAssignments, $eventVenueAssignments, $sportVenueAssignments) {
            $categoryAssignments = $categoryAreaAssignments->get($event->sport_id, collect());

            if ($categoryAssignments->isNotEmpty()) {
                return $categoryAssignments
                    ->groupBy(fn (SportCategoryCompetitionArea $assignment): string => $assignment->sport_category_id.'-'.$assignment->venue_id)
                    ->map(function ($assignments) use ($event): array {
                        $assignment = $assignments->first();

                        return [
                            'id' => $assignment->venue_id,
                            'event_id' => $event->id,
                            'sport_category_id' => $assignment->sport_category_id,
                            'label' => $assignment->venue->name,
                            'playing_area_type' => $assignment->competitionArea->area_type,
                            'playing_area_count' => $assignments->count(),
                            'competition_area_ids' => $assignments->pluck('competition_area_id')->values(),
                        ];
                    });
            }

            $directAssignments = $eventVenueAssignments->get($event->id, collect());

            if ($directAssignments->isNotEmpty()) {
                return $directAssignments->map(fn (EventVenue $assignment): array => [
                    'id' => $assignment->venue_id,
                    'event_id' => $event->id,
                    'sport_category_id' => null,
                    'label' => $assignment->venue->name,
                    'playing_area_type' => $assignment->playing_area_type,
                    'playing_area_count' => $assignment->playing_area_count,
                    'competition_area_ids' => [],
                ]);
            }

            return $sportVenueAssignments->get($event->sport_id, collect())
                ->map(fn (MeetSportVenue $assignment): array => [
                    'id' => $assignment->venue_id,
                    'event_id' => $event->id,
                    'sport_category_id' => null,
                    'label' => $assignment->venue->name,
                    'playing_area_type' => $assignment->venue->competitionAreas->first()?->area_type ?? 'venue',
                    'playing_area_count' => $assignment->expected_area_count ?? 1,
                    'competition_area_ids' => [],
                ]);
        })->values();

        $slots = $query->paginate($this->registryPageSize)->withQueryString();

        $matchesBySchedule = $this->matchesForSlots($slots->pluck('id'), $user);

        return Inertia::render('schedule/index', [
            'schedules' => $slots
                ->through(function (EventSchedule $schedule) use ($matchesBySchedule, $canManageAll, $canManageAssignedCompetition, $visibleEventIds): array {
                    $match = $matchesBySchedule->get($schedule->id);

                    return [
                        'id' => $schedule->id,
                        'event_id' => $schedule->event_id,
                        'sport_category_id' => $schedule->sport_category_id,
                        'venue_id' => $schedule->venue_id,
                        'competition_area_id' => $schedule->competition_area_id,
                        'event' => sprintf(
                            '%s — %s (%s, %s)',
                            $schedule->event->sport->name,
                            $schedule->event->name,
                            $schedule->event->gender->label(),
                            $schedule->event->age_division->label(),
                        ),
                        'sport_category' => $schedule->sportCategory?->display_name,
                        'venue' => $schedule->venue->name,
                        'competition_area' => $schedule->competitionArea?->name,
                        'date' => $schedule->scheduled_date->toDateString(),
                        'date_label' => $schedule->scheduled_date->format('D, M j, Y'),
                        'starts_at' => substr($schedule->starts_at, 0, 5),
                        'ends_at' => substr($schedule->ends_at, 0, 5),
                        'note' => $schedule->note,
                        'match_id' => $match?->id,
                        'is_live' => $match !== null && $match->scoringSessions->isNotEmpty(),
                        'live_score_available' => ScoreboardType::supportsLiveSport($schedule->event->sport->name),
                        'can_manage' => $canManageAll
                            || ($canManageAssignedCompetition && $visibleEventIds->contains($schedule->event_id)),
                    ];
                }),
            'filters' => [
                'search' => $search,
                'venue_id' => $venueId > 0 ? $venueId : null,
                'date' => $date !== '' ? $date : null,
            ],
            'venueFilterOptions' => Venue::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
            'meetIsSchedulable' => $meetIsSchedulable,
            'eventOptions' => $schedulableEvents
                ->map(fn (Event $event): array => [
                    'id' => $event->id,
                    'sport_id' => $event->sport_id,
                    'sport_category_id' => $event->sport_category_id,
                    'venue_id' => $venueOptions->firstWhere('event_id', $event->id)['id'] ?? null,
                    'label' => sprintf(
                        '%s — %s (%s, %s)',
                        $event->sport->name,
                        $event->name,
                        $event->gender->label(),
                        $event->age_division->label(),
                    ),
                ])
                ->values(),
            'venueOptions' => $venueOptions,
            'competitionAreaOptions' => CompetitionArea::query()
                ->where('status', '!=', 'unavailable')
                ->orderBy('venue_id')
                ->orderBy('display_order')
                ->get(['id', 'venue_id', 'name', 'area_type'])
                ->map(fn (CompetitionArea $area): array => [
                    'id' => $area->id,
                    'venue_id' => $area->venue_id,
                    'label' => $area->name,
                    'area_type' => $area->area_type,
                ]),
            'sportCategoryOptions' => SportCategory::query()
                ->where('active', true)
                ->where(function ($categories) use ($configuredCategoryIds, $configuredSportIds): void {
                    $categories->whereNotIn('sport_id', $configuredSportIds)
                        ->orWhereIn('id', $configuredCategoryIds);
                })
                ->orderBy('display_name')
                ->get(['id', 'sport_id', 'display_name'])
                ->map(fn (SportCategory $category): array => [
                    'id' => $category->id,
                    'sport_id' => $category->sport_id,
                    'label' => $category->display_name,
                ]),
            'canManage' => $canManageAll || $canManageAssignedCompetition,
        ]);
    }

    /**
     * Create a schedule slot.
     */
    public function store(ScheduleRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->slotData();

        $this->assertSlotIsValid($data, $user);

        $schedule = EventSchedule::create($data);

        $this->audit->record('schedule.created', $schedule, $this->context($schedule));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule of Events entry created.')]);

        return back();
    }

    /**
     * Update a schedule slot.
     */
    public function update(ScheduleRequest $request, EventSchedule $schedule): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $schedule->loadMissing('event');
        if (! $this->canManageSlot($user, $schedule->event)) {
            throw ValidationException::withMessages([
                'event_id' => __('You may change only Events within your assigned competition scope.'),
            ]);
        }

        $data = $request->slotData();

        $this->assertSlotIsValid($data, $user, $schedule);

        $schedule->update($data);

        $this->audit->record('schedule.updated', $schedule, $this->context($schedule));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule of Events entry updated.')]);

        return back();
    }

    /**
     * Delete a schedule slot.
     */
    public function destroy(Request $request, EventSchedule $schedule): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $schedule->loadMissing('event');
        abort_unless($this->canManageSlot($user, $schedule->event), 403);

        if (! $this->meetIsSchedulable($schedule->meet)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('The schedule can only be changed while the meet is registration-closed or active.'),
            ]);

            return back();
        }

        $context = $this->context($schedule);

        $schedule->delete();

        $this->audit->record('schedule.deleted', $schedule, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule of Events entry deleted.')]);

        return back();
    }

    /**
     * Enforce the scheduling rules: meet window, event-in-meet, venue
     * conflict, and (for a Tournament Manager) that the target event's
     * sport is one they operate — an Admin/Organizer may schedule any
     * sport's events, so this only ever narrows a Tournament Manager, never
     * broadens anyone.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertSlotIsValid(array $data, User $user, ?EventSchedule $ignore = null): void
    {
        $meet = Meet::current();

        if (! $this->meetIsSchedulable($meet)) {
            throw ValidationException::withMessages([
                'meet_id' => __('Scheduling is only allowed while a meet is registration-closed or active.'),
            ]);
        }

        $event = Event::query()->find($data['event_id']);
        $eventBelongsToMeet = $meet->events()->whereKey($data['event_id'])->exists()
            || ($event !== null && $meet->meetSports()
                ->where('active', true)
                ->where('sport_id', $event->sport_id)
                ->exists());

        if (! $eventBelongsToMeet) {
            throw ValidationException::withMessages([
                'event_id' => __('That event is not part of the selected meet.'),
            ]);
        }

        if ($event === null || ! $this->canManageSlot($user, $event)) {
            throw ValidationException::withMessages([
                'event_id' => __('You may schedule only Events within your assigned competition scope.'),
            ]);
        }

        $conflict = EventSchedule::query()
            ->where('venue_id', $data['venue_id'])
            ->when(
                ! empty($data['competition_area_id']),
                fn ($query) => $query->where(fn ($scope) => $scope
                    ->where('competition_area_id', $data['competition_area_id'])
                    ->orWhereNull('competition_area_id')),
            )
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->with('event:id,name')
            ->first();

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'starts_at' => __('The selected venue or competition area is already booked :start–:end that day for :event.', [
                    'start' => substr($conflict->starts_at, 0, 5),
                    'end' => substr($conflict->ends_at, 0, 5),
                    'event' => $conflict->event->name,
                ]),
            ]);
        }
    }

    private function meetIsSchedulable(Meet $meet): bool
    {
        return in_array($meet->status, [MeetStatus::RegistrationClosed, MeetStatus::Active], true);
    }

    /**
     * Admin/Organizer may manage any sport's slots; a Tournament Manager
     * only a slot whose event is one they operate
     * (`ScopesToAssignedSport::userOperatesSport()`).
     */
    private function canManageSlot(User $user, Event $event): bool
    {
        if ($user->isAdmin() || $user->canManageProductionAccounts()) {
            return true;
        }

        $access = app(CompetitionAccessService::class);

        return $access->hasAssignmentRole($user, $access->competitionManagerRoles(), Meet::current()->id)
            && $access->canAccessEvent($user, $event, Meet::current()->id);
    }

    /**
     * Matches tied to the given schedule slots, keyed by
     * `event_schedule_id` — the schedule page's "watch live" link data.
     * Scoped exactly like `MatchController::index()` (delegation officers
     * see only their own delegation's matches; viewers see none, since
     * live scoring is forbidden to them regardless) so a link is never
     * shown for a match the viewer couldn't actually open.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $scheduleIds
     * @return Collection<int, EventMatch>
     */
    private function matchesForSlots(\Illuminate\Support\Collection $scheduleIds, User $user): Collection
    {
        if ($user->role === UserRole::Viewer) {
            return new Collection;
        }

        $query = EventMatch::query()->real()
            ->whereIn('event_schedule_id', $scheduleIds)
            ->with(['scoringSessions' => fn ($sessions) => $sessions
                ->where('status', '!=', ScoringSessionStatus::Ended->value)
                ->latest('id')
                ->limit(1)]);

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'entries.delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        return $query->get()->keyBy('event_schedule_id');
    }

    /**
     * @return array<string, mixed>
     */
    private function context(EventSchedule $schedule): array
    {
        return [
            'meet' => $schedule->meet->name,
            'event' => $schedule->event->name,
            'venue' => $schedule->venue->name,
            'competition_area' => $schedule->competitionArea?->name,
            'date' => $schedule->scheduled_date->toDateString(),
            'time' => substr($schedule->starts_at, 0, 5).'–'.substr($schedule->ends_at, 0, 5),
        ];
    }
}
