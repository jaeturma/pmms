<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Enums\MeetStatus;
use App\Enums\MedicalClearanceStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\SportRosterMember;
use App\Models\TeamEntry;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EntryController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Searchable entry list filterable by event and delegation, officer-scoped.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Entry::class);

        /** @var User $user */
        $user = $request->user();

        $search = $this->searchTerm($request);
        $eventId = $request->integer('event_id');
        $delegationId = $request->integer('delegation_id');
        $coachEventIds = collect();
        $coachDelegationIds = collect();

        if ($user->role === UserRole::Coach) {
            $coachEventIds = $user->approvedCoachEventIds();
            $coachDelegationIds = $user->approvedCoachDelegationIds();
        }

        $query = Entry::query()
            ->with([
                'athlete:id,first_name,last_name,sex,birthdate,grade_level,school_id',
                'athlete.school:id,name',
                'athlete.eligibilityReview:id,athlete_id,status',
                'event.sport:id,name',
                'delegation.meet:id,name',
            ])
            ->orderByDesc('id');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $query->whereIn('event_id', $coachEventIds)
                ->whereIn('delegation_id', $coachDelegationIds);
        } elseif ($user->tournamentEventIds()->isNotEmpty()) {
            $query->whereIn('event_id', $user->tournamentEventIds());
        }

        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }

        if ($delegationId > 0) {
            $query->where('delegation_id', $delegationId);
        }

        $this->applySearch($query, $search, ['athlete.first_name', 'athlete.last_name']);

        $delegationScope = Delegation::query()->with(['school:id,name', 'district:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegationScope->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        } elseif ($user->role === UserRole::Coach) {
            $delegationScope->whereIn('id', $coachDelegationIds);
        }

        $delegations = $delegationScope->get();

        $editableDelegationIds = $user->role === UserRole::Coach
            ? $coachDelegationIds
            : $delegations
                ->filter(fn (Delegation $delegation): bool => $user->can('create', [Entry::class, $delegation]))
                ->pluck('id');

        $athleteScope = Athlete::query()
            ->with(['school:id,name', 'delegation.meet:id,name'])
            ->whereIn('delegation_id', $editableDelegationIds)
            ->when($user->role === UserRole::Coach, fn ($athletes) => $athletes
                ->ownedBy($user)
                ->whereHas('eligibilityReview', fn ($review) => $review
                    ->where('status', EligibilityStatus::Approved->value)))
            ->orderBy('last_name');

        return Inertia::render('entries/index', [
            'entries' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Entry $entry): array => [
                    'id' => $entry->id,
                    'athlete' => $entry->athlete->fullName(),
                    'event' => sprintf(
                        '%s — %s (%s, %s)',
                        $entry->event->sport->name,
                        $entry->event->name,
                        $entry->event->gender->label(),
                        $entry->event->age_division->label(),
                    ),
                    'school' => $entry->athlete->school->name,
                    'delegation' => $entry->delegation->registrantName(),
                    'meet' => $entry->delegation->meet->name,
                    'status' => $entry->status->value,
                    'status_label' => $entry->status->label(),
                    'eligibility_approved' => $entry->athlete->eligibilityReview?->status === EligibilityStatus::Approved,
                    'can_confirm' => $entry->status === EntryStatus::Submitted
                        && $entry->athlete->eligibilityReview?->status === EligibilityStatus::Approved
                        && $user->can('confirm', $entry),
                    'can_withdraw' => $entry->status !== EntryStatus::Withdrawn
                        && $user->can('withdraw', $entry),
                    'can_delete' => $user->can('delete', $entry),
                ]),
            'filters' => [
                'search' => $search,
                'event_id' => $eventId > 0 ? $eventId : null,
                'delegation_id' => $delegationId > 0 ? $delegationId : null,
            ],
            'eventFilterOptions' => Event::query()
                ->whereHas('meets')
                ->when($user->role === UserRole::Coach, fn ($events) => $events
                    ->whereIn('id', $coachEventIds))
                ->with('sport:id,name')
                ->orderBy('name')
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division', 'is_team_event'])
                ->map(fn (Event $event): array => [
                    'id' => $event->id,
                    'label' => sprintf(
                        '%s — %s (%s, %s)',
                        $event->sport->name,
                        $event->name,
                        $event->gender->label(),
                        $event->age_division->label(),
                    ),
                ])
                ->sortBy('label')
                ->values(),
            'delegationFilterOptions' => $delegations
                ->map(fn (Delegation $delegation): array => [
                    'id' => $delegation->id,
                    'label' => "{$delegation->registrantName()} — {$delegation->meet->name}",
                ])
                ->sortBy('label')
                ->values(),
            'athleteOptions' => $athleteScope->get()
                ->map(fn (Athlete $athlete): array => [
                    'id' => $athlete->id,
                    'meet_id' => $athlete->delegation->meet->id,
                    'delegation_id' => $athlete->delegation_id,
                    'label' => "{$athlete->fullName()} — {$athlete->school->name}",
                ])
                ->values(),
            'eventOptionsByMeet' => Event::query()
                ->whereHas('meets', fn ($meets) => $meets->where('status', MeetStatus::RegistrationOpen->value))
                ->when($user->role === UserRole::Coach, fn ($events) => $events->whereIn(
                    'id', $coachEventIds,
                ))
                ->with(['sport:id,name', 'meets:id'])
                ->get(['id', 'sport_id', 'sport_category_id', 'name', 'gender', 'age_division', 'is_team_event', 'team_size'])
                ->flatMap(fn (Event $event) => $event->meets->map(fn (Meet $meet): array => [
                    'id' => $event->id,
                    'meet_id' => $meet->id,
                    'sport' => $event->sport->name,
                    'is_team_event' => $event->is_team_event,
                    'label' => sprintf(
                        '%s — %s (%s, %s)',
                        $event->sport->name,
                        $event->name,
                        $event->gender->label(),
                        $event->age_division->label(),
                    ),
                ]))
                ->values(),
            'teamEntries' => TeamEntry::query()
                ->with(['delegation.school', 'delegation.district', 'event.sport', 'event.sportCategory', 'members.athlete'])
                ->when($user->role === UserRole::Coach, fn ($teams) => $teams
                    ->whereIn('event_id', $coachEventIds)
                    ->whereIn('delegation_id', $coachDelegationIds))
                ->when($user->role !== UserRole::Coach && $user->tournamentEventIds()->isNotEmpty(), fn ($teams) => $teams
                    ->whereIn('event_id', $user->tournamentEventIds()))
                ->orderByDesc('id')
                ->get()
                ->map(function (TeamEntry $team): array {
                    $minimum = $team->event->sportCategory?->min_players ?? $team->event->team_size;
                    $maximum = $team->event->sportCategory?->max_players ?? $team->event->team_size;
                    $count = $team->members->count();

                    return [
                        'id' => $team->id,
                        'event' => $team->event->sport->name.' — '.$team->event->name,
                        'delegation' => $team->delegation->registrantName(),
                        'member_count' => $count,
                        'minimum' => $minimum,
                        'maximum' => $maximum,
                        'complete' => $minimum === null || $count >= $minimum,
                        'locked' => $team->isRosterLocked(),
                        'status' => $team->status->label(),
                        'members' => $team->members->map(fn ($member): array => [
                            'id' => $member->athlete_id,
                            'name' => $member->athlete->fullName(),
                        ])->values(),
                    ];
                })->values(),
        ]);
    }

    /**
     * Submit an athlete into an event, enforcing every registration rule.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'athlete_id' => ['required', 'integer', Rule::exists('athletes', 'id')],
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id'), 'required_without:event_ids'],
            'event_ids' => ['nullable', 'array', 'min:1', 'required_without:event_id'],
            'event_ids.*' => ['required', 'integer', 'distinct', Rule::exists('events', 'id')],
        ]);

        $athlete = Athlete::query()
            ->with(['delegation.meet', 'eligibilityReview:id,athlete_id,status', 'medicalClearance:id,athlete_id,status'])
            ->findOrFail($request->integer('athlete_id'));

        $delegation = $athlete->delegation;

        if ($request->user()?->role === UserRole::Coach
            && $athlete->eligibilityReview?->status !== EligibilityStatus::Approved) {
            throw ValidationException::withMessages([
                'athlete_id' => __('Only approved athletes may be submitted to a sports event.'),
            ]);
        }

        $eventIds = collect($request->input('event_ids', [$request->integer('event_id')]))
            ->map(fn ($id): int => (int) $id)->unique()->values();
        $events = Event::query()->whereKey($eventIds)->get()->keyBy('id');
        $errorKey = $request->has('event_ids') ? 'event_ids' : 'event_id';

        foreach ($eventIds as $eventId) {
            $event = $events->get($eventId);
            Gate::authorize('create', [Entry::class, $delegation, $event]);

            if ($event->is_team_event) {
                throw ValidationException::withMessages([
                    $errorKey => __(':event is a team or group event. Build its roster using Team entries.', ['event' => $event->name]),
                ]);
            }

            if ($event->sport()->where('code', 'SWIMMING')->exists()) {
                $meetSportId = MeetSport::query()->where('meet_id', $delegation->meet_id)
                    ->where('sport_id', $event->sport_id)->value('id');
                if ($meetSportId === null || ! SportRosterMember::query()
                    ->where('meet_sport_id', $meetSportId)->where('delegation_id', $delegation->id)
                    ->where('athlete_id', $athlete->id)->where('level', $event->age_division->value)
                    ->where('gender', $event->gender->value)->exists()) {
                    throw ValidationException::withMessages([$errorKey => __('Add this swimmer to the matching Swimming roster before assigning events.')]);
                }
            }

            if (! $delegation->meet->events()->whereKey($event->id)->exists()) {
                throw ValidationException::withMessages([$errorKey => __(':event is not part of the athlete\'s meet.', ['event' => $event->name])]);
            }
            if (! $event->gender->accepts($athlete->sex)) {
                throw ValidationException::withMessages([$errorKey => __('The athlete\'s sex does not match :event.', ['event' => $event->name])]);
            }
            if ($event->age_division !== $athlete->ageDivision()) {
                throw ValidationException::withMessages([$errorKey => __('The athlete\'s grade level does not match :event.', ['event' => $event->name])]);
            }
            if (Entry::query()->where('athlete_id', $athlete->id)->where('event_id', $event->id)->exists()) {
                if ($request->user()?->role === UserRole::Coach && $eventIds->count() === 1) {
                    Inertia::flash('toast', ['type' => 'success', 'message' => __('Entry already submitted.')]);

                    return back();
                }
                throw ValidationException::withMessages([$errorKey => __('This athlete is already entered in :event.', ['event' => $event->name])]);
            }
            if (Entry::query()->where('delegation_id', $delegation->id)->where('event_id', $event->id)
                ->where('status', '!=', EntryStatus::Withdrawn->value)->count() >= $event->max_entries_per_delegation) {
                throw ValidationException::withMessages([$errorKey => __('The delegation has reached the entry cap for :event.', ['event' => $event->name])]);
            }
        }

        $limit = $delegation->meet->max_events_per_athlete;
        $activeEntryCount = Entry::query()->where('athlete_id', $athlete->id)
            ->where('status', '!=', EntryStatus::Withdrawn->value)->count();
        if ($limit !== null && $activeEntryCount + $eventIds->count() > $limit) {
            throw ValidationException::withMessages([
                $errorKey => __('This athlete may enter at most :count events in this meet.', ['count' => $limit]),
            ]);
        }

        $created = DB::transaction(fn () => $eventIds->map(fn (int $eventId): Entry => Entry::query()->create([
            'delegation_id' => $delegation->id,
            'athlete_id' => $athlete->id,
            'event_id' => $eventId,
        ])));

        foreach ($created as $entry) {
            $event = $events->get($entry->event_id);
            $this->audit->record('entry.submitted', $entry, [
                'athlete' => $athlete->fullName(),
                'event' => $event->name,
                'event_type' => $event->is_team_event ? 'team' : 'individual',
                'registrant' => $delegation->registrantName(),
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans_choice('{1} Entry submitted.|[2,*] :count entries submitted.', $created->count(), ['count' => $created->count()]),
        ]);

        return back();
    }

    /**
     * Confirm a submitted entry (organizer decision).
     */
    public function confirm(Entry $entry): RedirectResponse
    {
        Gate::authorize('confirm', $entry);

        $entry->loadMissing(['event.sport', 'athlete.delegation.meet', 'athlete.eligibilityReview', 'athlete.medicalClearance']);
        if ($entry->athlete->eligibilityReview?->status !== EligibilityStatus::Approved) {
            throw ValidationException::withMessages([
                'entry' => __('This entry cannot be confirmed until DSAC approves the athlete’s eligibility.'),
            ]);
        }

        if ($entry->event->sport->code === 'SWIMMING'
            && $entry->athlete->delegation->meet->medical_clearance_required
            && $entry->athlete->medicalClearance?->status !== MedicalClearanceStatus::Cleared) {
            throw ValidationException::withMessages([
                'entry' => __('This entry cannot be confirmed until the athlete is medically cleared.'),
            ]);
        }

        if ($entry->status !== EntryStatus::Submitted) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only submitted entries can be confirmed.'),
            ]);

            return back();
        }

        $entry->forceFill(['status' => EntryStatus::Confirmed])->save();

        $this->audit->record('entry.confirmed', $entry, [
            'athlete' => $entry->athlete->fullName(),
            'event' => $entry->event->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Entry confirmed.')]);

        return back();
    }

    /**
     * Withdraw an entry.
     */
    public function withdraw(Entry $entry): RedirectResponse
    {
        Gate::authorize('withdraw', $entry);

        if ($entry->status === EntryStatus::Withdrawn) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This entry is already withdrawn.'),
            ]);

            return back();
        }

        $entry->forceFill(['status' => EntryStatus::Withdrawn])->save();

        $this->audit->record('entry.withdrawn', $entry, [
            'athlete' => $entry->athlete->fullName(),
            'event' => $entry->event->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Entry withdrawn.')]);

        return back();
    }

    /**
     * Delete a withdrawn entry, freeing the athlete+event slot.
     */
    public function destroy(Entry $entry): RedirectResponse
    {
        Gate::authorize('delete', $entry);

        if ($entry->matches()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This entry took part in a match and cannot be deleted.'),
            ]);

            return back();
        }

        if ($entry->placements()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This entry has recorded results and cannot be deleted.'),
            ]);

            return back();
        }

        $context = [
            'athlete' => $entry->athlete->fullName(),
            'event' => $entry->event->name,
        ];

        $entry->delete();

        $this->audit->record('entry.deleted', $entry, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Entry deleted.')]);

        return back();
    }
}
