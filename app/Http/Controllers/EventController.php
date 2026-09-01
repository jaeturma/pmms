<?php

namespace App\Http\Controllers;

use App\Enums\MeetSportAssignmentRole;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\Meet;
use App\Models\Person;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Searchable, paginated events catalog.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);
        $sportId = $request->integer('sport_id') ?: null;
        $sportIds = $this->manageableSportIds($request->user());
        $canManage = $request->user()->isAdmin() || $sportIds->isNotEmpty();

        $query = Event::query()
            ->with(['sport:id,name', 'medalConfig', 'venueAssignments.venue:id,name', 'venueAssignments.coordinators:id,full_name'])
            ->orderBy('name');

        if (! $request->user()->isAdmin() && $sportIds->isNotEmpty()) {
            $query->whereIn('sport_id', $sportIds);
        }

        if ($sportId !== null) {
            $query->where('sport_id', $sportId);
        }

        $this->applySearch($query, $search, ['name', 'sport.name']);

        return Inertia::render('catalog/events', [
            'events' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Event $event): array => [
                    'id' => $event->id,
                    'sport_id' => $event->sport_id,
                    'name' => $event->name,
                    'gender' => $event->gender->value,
                    'age_division' => $event->age_division->value,
                    'is_team_event' => $event->is_team_event,
                    'max_entries_per_delegation' => $event->max_entries_per_delegation,
                    'medal_config' => $this->medalConfigPayload($event),
                    'active' => $event->active,
                    'sport' => ['id' => $event->sport->id, 'name' => $event->sport->name],
                    'venues' => $event->venueAssignments->map(fn ($assignment): array => [
                        'venue_id' => $assignment->venue_id,
                        'venue_name' => $assignment->venue->name,
                        'playing_area_type' => $assignment->playing_area_type,
                        'playing_area_count' => $assignment->playing_area_count,
                        'coordinator_ids' => $assignment->coordinators->pluck('id')->all(),
                        'coordinator_names' => $assignment->coordinators->pluck('full_name')->all(),
                    ]),
                ]),
            'filters' => ['search' => $search, 'sport_id' => $sportId],
            'sports' => Sport::query()
                ->where('active', true)
                ->when(! $request->user()->isAdmin() && $sportIds->isNotEmpty(), fn ($sports) => $sports->whereIn('id', $sportIds))
                ->orderBy('name')
                ->get(['id', 'name']),
            'canManage' => $canManage,
            'canArchive' => $request->user()->isAdmin(),
            'venues' => Venue::query()->where('active', true)
                ->when(! $request->user()->isAdmin() && $sportIds->isNotEmpty(), fn ($venues) => $venues
                    ->whereHas('meetSportAssignments.meetSport', fn ($meetSport) => $meetSport
                        ->where('meet_id', Meet::current()->id)
                        ->whereIn('sport_id', $sportIds)))
                ->orderBy('name')->get(['id', 'name']),
            'people' => Person::query()->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    /**
     * Create an event.
     */
    public function store(EventRequest $request): RedirectResponse
    {
        $this->authorizeSport($request->user(), $request->integer('sport_id'));
        $this->authorizeVenues($request->user(), $request->integer('sport_id'), $request->validated('venues', []));

        $event = DB::transaction(function () use ($request): Event {
            $event = Event::create($request->eventData());
            if ($request->medalConfigData() !== null) {
                $event->medalConfig()->create($request->medalConfigData());
            }
            $this->syncVenues($event, $request->validated('venues', []));

            return $event;
        });

        $this->audit->record('event.created', $event, ['name' => $event->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')]);

        return back();
    }

    /**
     * Update an event.
     */
    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $this->authorizeSport($request->user(), $event->sport_id);
        $this->authorizeSport($request->user(), $request->integer('sport_id'));
        $this->authorizeVenues($request->user(), $request->integer('sport_id'), $request->validated('venues', []));

        DB::transaction(function () use ($request, $event): void {
            $event->update($request->eventData());
            if ($request->medalConfigData() !== null) {
                $event->medalConfig()->updateOrCreate([], $request->medalConfigData());
            }
            $this->syncVenues($event, $request->validated('venues', []));
        });

        $this->audit->record('event.updated', $event, ['name' => $event->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event updated.')]);

        return back();
    }

    /**
     * Archive an event instead of deleting it.
     */
    public function archive(Event $event): RedirectResponse
    {
        $event->forceFill(['active' => false])->save();

        $this->audit->record('event.archived', $event, ['name' => $event->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event archived.')]);

        return back();
    }

    /**
     * Restore an archived event.
     */
    public function restore(Event $event): RedirectResponse
    {
        $event->forceFill(['active' => true])->save();

        $this->audit->record('event.restored', $event, ['name' => $event->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event restored.')]);

        return back();
    }

    /**
     * Delete an event that no meet references.
     */
    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeSport($request->user(), $event->sport_id);

        if ($event->meets()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This event is part of a meet. Archive it instead.'),
            ]);

            return back();
        }

        $event->delete();

        $this->audit->record('event.deleted', $event, ['name' => $event->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event deleted.')]);

        return back();
    }

    private function syncVenues(Event $event, array $venues): void
    {
        $keep = [];

        foreach ($venues as $venue) {
            $assignment = $event->venueAssignments()->updateOrCreate(
                ['venue_id' => $venue['venue_id']],
                [
                    'playing_area_type' => $venue['playing_area_type'],
                    'playing_area_count' => $venue['playing_area_count'],
                ],
            );
            $assignment->coordinators()->sync($venue['coordinator_ids'] ?? []);
            $keep[] = $assignment->id;
        }

        $event->venueAssignments()->when($keep !== [], fn ($query) => $query->whereNotIn('id', $keep))->delete();
    }

    private function authorizeSport(User $user, int $sportId): void
    {
        abort_unless($user->isAdmin() || $this->manageableSportIds($user)->contains($sportId), 403);
    }

    private function authorizeVenues(User $user, int $sportId, array $venues): void
    {
        if ($user->isAdmin() || $venues === []) {
            return;
        }

        $venueIds = collect($venues)->pluck('venue_id')->map(fn ($id): int => (int) $id)->unique();
        $authorizedCount = Venue::query()
            ->whereKey($venueIds)
            ->whereHas('meetSportAssignments.meetSport', fn ($meetSport) => $meetSport
                ->where('meet_id', Meet::current()->id)
                ->where('sport_id', $sportId))
            ->count();

        abort_unless($authorizedCount === $venueIds->count(), 403);
    }

    private function manageableSportIds(User $user): Collection
    {
        return $user->meetSportAssignments()
            ->where('status', 'active')
            ->whereIn('role', [MeetSportAssignmentRole::TournamentManager, MeetSportAssignmentRole::TournamentICT, MeetSportAssignmentRole::TournamentSecretary])
            ->whereHas('meetSport.meet', fn ($meet) => $meet->whereKey(Meet::current()->id))
            ->with('meetSport:id,sport_id')
            ->get()->pluck('meetSport.sport_id')->map(fn ($id): int => (int) $id)->unique()->values();
    }

    private function medalConfigPayload(Event $event): array
    {
        $config = $event->resolvedMedalConfig();

        return [
            'awards_medals' => $config->awards_medals,
            'award_type' => $config->award_type,
            'physical_quantity_mode' => $config->physical_quantity_mode,
            'gold_physical_quantity' => $config->gold_physical_quantity,
            'silver_physical_quantity' => $config->silver_physical_quantity,
            'bronze_physical_quantity' => $config->bronze_physical_quantity,
            'gold_tally_quantity' => $config->gold_tally_quantity,
            'silver_tally_quantity' => $config->silver_tally_quantity,
            'bronze_tally_quantity' => $config->bronze_tally_quantity,
            'notes' => $config->notes,
            'status' => ! $config->awards_medals ? 'NOT_APPLICABLE' : ($config->isComplete() ? ($event->medalConfig ? 'CONFIGURED' : 'DEFAULT_INDIVIDUAL_1') : 'MEDAL_CONFIGURATION_REQUIRED'),
        ];
    }
}
