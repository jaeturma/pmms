<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Enums\MeetStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EntryController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Entry list filterable by event and delegation, officer-scoped.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Entry::class);

        /** @var User $user */
        $user = $request->user();

        $eventId = $request->integer('event_id');
        $delegationId = $request->integer('delegation_id');

        $query = Entry::query()
            ->with([
                'athlete:id,first_name,last_name,sex,birthdate,grade_level',
                'athlete.eligibilityReview:id,athlete_id,status',
                'event.sport:id,name',
                'delegation.school:id,name',
                'delegation.meet:id,name',
            ])
            ->orderByDesc('id');

        if ($user->role === UserRole::DelegationOfficer) {
            $query->whereHas(
                'delegation.officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }

        if ($delegationId > 0) {
            $query->where('delegation_id', $delegationId);
        }

        $delegationScope = Delegation::query()->with(['school:id,name', 'meet:id,name']);

        if ($user->role === UserRole::DelegationOfficer) {
            $delegationScope->whereHas(
                'officers',
                fn ($officers) => $officers->whereKey($user->getKey()),
            );
        }

        $delegations = $delegationScope->get();

        $athleteScope = Athlete::query()
            ->with(['delegation.school:id,name', 'delegation.meet:id,name'])
            ->whereIn(
                'delegation_id',
                $delegations
                    ->filter(fn (Delegation $delegation): bool => $user->can('create', [Entry::class, $delegation]))
                    ->pluck('id'),
            )
            ->orderBy('last_name');

        return Inertia::render('entries/index', [
            'entries' => $query->paginate(15)->withQueryString()
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
                    'school' => $entry->delegation->school->name,
                    'meet' => $entry->delegation->meet->name,
                    'status' => $entry->status->value,
                    'status_label' => $entry->status->label(),
                    'eligibility_approved' => $entry->athlete->eligibilityReview?->status === EligibilityStatus::Approved,
                    'can_confirm' => $entry->status === EntryStatus::Submitted
                        && $user->can('confirm', $entry),
                    'can_withdraw' => $entry->status !== EntryStatus::Withdrawn
                        && $user->can('withdraw', $entry),
                    'can_delete' => $user->can('delete', $entry),
                ]),
            'filters' => [
                'event_id' => $eventId > 0 ? $eventId : null,
                'delegation_id' => $delegationId > 0 ? $delegationId : null,
            ],
            'eventFilterOptions' => Event::query()
                ->whereHas('meets')
                ->with('sport:id,name')
                ->orderBy('name')
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division'])
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
                    'label' => "{$delegation->school->name} — {$delegation->meet->name}",
                ])
                ->sortBy('label')
                ->values(),
            'athleteOptions' => $athleteScope->get()
                ->map(fn (Athlete $athlete): array => [
                    'id' => $athlete->id,
                    'meet_id' => $athlete->delegation->meet->id,
                    'label' => "{$athlete->fullName()} — {$athlete->delegation->school->name}",
                ])
                ->values(),
            'eventOptionsByMeet' => Event::query()
                ->whereHas('meets', fn ($meets) => $meets->where('status', MeetStatus::RegistrationOpen->value))
                ->with(['sport:id,name', 'meets:id'])
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division'])
                ->flatMap(fn (Event $event) => $event->meets->map(fn (Meet $meet): array => [
                    'id' => $event->id,
                    'meet_id' => $meet->id,
                    'label' => sprintf(
                        '%s — %s (%s, %s)',
                        $event->sport->name,
                        $event->name,
                        $event->gender->label(),
                        $event->age_division->label(),
                    ),
                ]))
                ->values(),
        ]);
    }

    /**
     * Submit an athlete into an event, enforcing every registration rule.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'athlete_id' => ['required', 'integer', Rule::exists('athletes', 'id')],
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
        ]);

        $athlete = Athlete::query()
            ->with('delegation.meet')
            ->findOrFail($request->integer('athlete_id'));

        $delegation = $athlete->delegation;

        Gate::authorize('create', [Entry::class, $delegation]);

        $event = Event::query()->findOrFail($request->integer('event_id'));

        if (! $delegation->meet->events()->whereKey($event->id)->exists()) {
            throw ValidationException::withMessages([
                'event_id' => __('That event is not part of the athlete\'s meet.'),
            ]);
        }

        if (! $event->gender->accepts($athlete->sex)) {
            throw ValidationException::withMessages([
                'event_id' => __('The athlete\'s sex does not match this event\'s gender category.'),
            ]);
        }

        if ($event->age_division !== $athlete->ageDivision()) {
            throw ValidationException::withMessages([
                'event_id' => __('The athlete\'s grade level does not match this event\'s age division.'),
            ]);
        }

        if (Entry::query()->where('athlete_id', $athlete->id)->where('event_id', $event->id)->exists()) {
            throw ValidationException::withMessages([
                'event_id' => __('This athlete is already entered in that event.'),
            ]);
        }

        $activeEntries = Entry::query()
            ->where('delegation_id', $delegation->id)
            ->where('event_id', $event->id)
            ->where('status', '!=', EntryStatus::Withdrawn->value)
            ->count();

        if ($activeEntries >= $event->max_entries_per_delegation) {
            throw ValidationException::withMessages([
                'event_id' => __('The delegation has reached this event\'s entry cap.'),
            ]);
        }

        $entry = Entry::create([
            'delegation_id' => $delegation->id,
            'athlete_id' => $athlete->id,
            'event_id' => $event->id,
        ]);

        $this->audit->record('entry.submitted', $entry, [
            'athlete' => $athlete->fullName(),
            'event' => $event->name,
            'school' => $delegation->school->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Entry submitted.')]);

        return back();
    }

    /**
     * Confirm a submitted entry (organizer decision).
     */
    public function confirm(Entry $entry): RedirectResponse
    {
        Gate::authorize('confirm', $entry);

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
