<?php

namespace App\Http\Controllers;

use App\Enums\MeetStatus;
use App\Enums\ScoringSessionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ScopesToAssignedSport;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\ScheduleRequest;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\SportCategory;
use App\Models\User;
use App\Models\Venue;
use App\Services\AuditLogger;
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

        $canManageAll = Gate::allows('manage-meet-data');
        $isTournamentManager = $user->role === UserRole::TournamentManager && $user->managedSport !== null;
        $managedSportId = $isTournamentManager ? $user->managedSport->id : null;

        $search = $this->searchTerm($request);
        $venueId = $request->integer('venue_id');
        $date = $request->string('date')->toString();

        $query = EventSchedule::query()
            ->with(['event.sport:id,name', 'sportCategory:id,display_name', 'venue:id,name'])
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

        $slots = $query->paginate($this->registryPageSize)->withQueryString();

        $matchesBySchedule = $this->matchesForSlots($slots->pluck('id'), $user);

        return Inertia::render('schedule/index', [
            'schedules' => $slots
                ->through(function (EventSchedule $schedule) use ($matchesBySchedule, $canManageAll, $isTournamentManager, $managedSportId): array {
                    $match = $matchesBySchedule->get($schedule->id);

                    return [
                        'id' => $schedule->id,
                        'event_id' => $schedule->event_id,
                        'sport_category_id' => $schedule->sport_category_id,
                        'venue_id' => $schedule->venue_id,
                        'event' => sprintf(
                            '%s — %s (%s, %s)',
                            $schedule->event->sport->name,
                            $schedule->event->name,
                            $schedule->event->gender->label(),
                            $schedule->event->age_division->label(),
                        ),
                        'sport_category' => $schedule->sportCategory?->display_name,
                        'venue' => $schedule->venue->name,
                        'date' => $schedule->scheduled_date->toDateString(),
                        'date_label' => $schedule->scheduled_date->format('D, M j, Y'),
                        'starts_at' => substr($schedule->starts_at, 0, 5),
                        'ends_at' => substr($schedule->ends_at, 0, 5),
                        'note' => $schedule->note,
                        'match_id' => $match?->id,
                        'is_live' => $match !== null && $match->scoringSessions->isNotEmpty(),
                        'can_manage' => $canManageAll
                            || ($isTournamentManager && $schedule->event->sport_id === $managedSportId),
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
            'eventOptions' => Event::query()
                ->whereHas('meets', fn ($meets) => $meets->whereKey($meet->id))
                ->when($managedSportId !== null, fn ($query) => $query->where('sport_id', $managedSportId))
                ->with('sport:id,name')
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division'])
                ->map(fn (Event $event): array => [
                    'id' => $event->id,
                    'sport_id' => $event->sport_id,
                    'label' => sprintf(
                        '%s — %s (%s, %s)',
                        $event->sport->name,
                        $event->name,
                        $event->gender->label(),
                        $event->age_division->label(),
                    ),
                ])
                ->values(),
            'venueOptions' => Venue::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
            'sportCategoryOptions' => SportCategory::query()->where('active', true)->orderBy('display_name')
                ->get(['id', 'sport_id', 'display_name'])
                ->map(fn (SportCategory $category): array => [
                    'id' => $category->id,
                    'sport_id' => $category->sport_id,
                    'label' => $category->display_name,
                ]),
            'canManage' => $canManageAll || $isTournamentManager,
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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule slot created.')]);

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
        abort_unless($this->canManageSlot($user, $schedule->event->sport_id), 403);

        $data = $request->slotData();

        $this->assertSlotIsValid($data, $user, $schedule);

        $schedule->update($data);

        $this->audit->record('schedule.updated', $schedule, $this->context($schedule));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule slot updated.')]);

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
        abort_unless($this->canManageSlot($user, $schedule->event->sport_id), 403);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule slot deleted.')]);

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

        if (! $meet->events()->whereKey($data['event_id'])->exists()) {
            throw ValidationException::withMessages([
                'event_id' => __('That event is not part of the selected meet.'),
            ]);
        }

        $sportId = (int) Event::query()->whereKey($data['event_id'])->value('sport_id');
        abort_unless($this->canManageSlot($user, $sportId), 403);

        $conflict = EventSchedule::query()
            ->where('venue_id', $data['venue_id'])
            ->whereDate('scheduled_date', $data['scheduled_date'])
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
            ->with('event:id,name')
            ->first();

        if ($conflict !== null) {
            throw ValidationException::withMessages([
                'starts_at' => __('The venue is already booked :start–:end that day for :event.', [
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
    private function canManageSlot(User $user, int $sportId): bool
    {
        if ($user->hasRole(UserRole::Admin, UserRole::Organizer)) {
            return true;
        }

        return $user->role === UserRole::TournamentManager && $this->userOperatesSport($user, $sportId);
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

        $query = EventMatch::query()
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
            'date' => $schedule->scheduled_date->toDateString(),
            'time' => substr($schedule->starts_at, 0, 5).'–'.substr($schedule->ends_at, 0, 5),
        ];
    }
}
