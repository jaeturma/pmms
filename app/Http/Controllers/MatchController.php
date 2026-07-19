<?php

namespace App\Http\Controllers;

use App\Enums\EntryStatus;
use App\Enums\MatchStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\MatchRequest;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventSchedule;
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

class MatchController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Match list, mirroring entry visibility: managers see all, officers
     * only matches involving their delegation, viewers none.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Entry::class);

        /** @var User $user */
        $user = $request->user();

        $meetId = $request->integer('meet_id');
        $eventId = $request->integer('event_id');

        $query = EventMatch::query()
            ->with([
                'meet:id,name',
                'event.sport:id,name',
                'schedule.venue:id,name',
                'entries.athlete:id,first_name,last_name',
                'entries.delegation.school:id,name',
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

        if ($meetId > 0) {
            $query->where('meet_id', $meetId);
        }

        if ($eventId > 0) {
            $query->where('event_id', $eventId);
        }

        return Inertia::render('matches/index', [
            'matches' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (EventMatch $match): array => [
                    'id' => $match->id,
                    'meet_id' => $match->meet_id,
                    'event_id' => $match->event_id,
                    'event_schedule_id' => $match->event_schedule_id,
                    'meet' => $match->meet->name,
                    'event' => $this->eventLabel($match->event),
                    'round_label' => $match->round_label,
                    'sequence' => $match->sequence,
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
                            'school' => $entry->delegation->school->name,
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
                ]),
            'filters' => [
                'meet_id' => $meetId > 0 ? $meetId : null,
                'event_id' => $eventId > 0 ? $eventId : null,
            ],
            'meetOptions' => Meet::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'eventOptionsByMeet' => Event::query()
                ->whereHas('meets')
                ->with(['sport:id,name', 'meets:id'])
                ->get(['id', 'sport_id', 'name', 'gender', 'age_division'])
                ->flatMap(fn (Event $event) => $event->meets->map(fn (Meet $meet): array => [
                    'id' => $event->id,
                    'meet_id' => $meet->id,
                    'label' => $this->eventLabel($event),
                ]))
                ->values(),
            'scheduleOptions' => EventSchedule::query()
                ->with('venue:id,name')
                ->get()
                ->map(fn (EventSchedule $slot): array => [
                    'id' => $slot->id,
                    'meet_id' => $slot->meet_id,
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
                ->with(['athlete:id,first_name,last_name', 'delegation:id,meet_id,school_id', 'delegation.school:id,name'])
                ->get()
                ->map(fn (Entry $entry): array => [
                    'id' => $entry->id,
                    'event_id' => $entry->event_id,
                    'meet_id' => $entry->delegation->meet_id,
                    'label' => "{$entry->athlete->fullName()} — {$entry->delegation->school->name}",
                ])
                ->sortBy('label')
                ->values(),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create a match for an event that runs in the meet.
     */
    public function store(MatchRequest $request): RedirectResponse
    {
        $data = $request->validated();

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
            ->with(['athlete:id,first_name,last_name', 'delegation:id,meet_id,school_id'])
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

        if ($match->event->is_team_event
            && $entries->pluck('delegation.school_id')->duplicates()->isNotEmpty()) {
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
    public function destroy(EventMatch $match): RedirectResponse
    {
        $context = $this->context($match);

        $match->delete();

        $this->audit->record('match.deleted', $match, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Match deleted.')]);

        return back();
    }

    /**
     * Shared rules for create/update: event in meet, slot of same meet+event.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertMatchIsValid(array $data): void
    {
        $meet = Meet::query()->findOrFail((int) $data['meet_id']);

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
