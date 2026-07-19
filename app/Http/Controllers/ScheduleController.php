<?php

namespace App\Http\Controllers;

use App\Enums\MeetStatus;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Http\Requests\ScheduleRequest;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\Venue;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * The meet schedule, filterable per day and per venue, readable by all roles.
     */
    public function index(Request $request): Response
    {
        $search = $this->searchTerm($request);
        $meetId = $request->integer('meet_id');
        $venueId = $request->integer('venue_id');
        $date = $request->string('date')->toString();

        $query = EventSchedule::query()
            ->with(['event.sport:id,name', 'venue:id,name', 'meet:id,name'])
            ->orderBy('scheduled_date')
            ->orderBy('starts_at');

        if ($meetId > 0) {
            $query->where('meet_id', $meetId);
        }

        if ($venueId > 0) {
            $query->where('venue_id', $venueId);
        }

        if ($date !== '') {
            $query->whereDate('scheduled_date', $date);
        }

        $this->applySearch($query, $search, ['event.name']);

        $schedulableMeets = Meet::query()
            ->whereIn('status', [MeetStatus::RegistrationClosed->value, MeetStatus::Active->value])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('schedule/index', [
            'schedules' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (EventSchedule $schedule): array => [
                    'id' => $schedule->id,
                    'meet_id' => $schedule->meet_id,
                    'event_id' => $schedule->event_id,
                    'venue_id' => $schedule->venue_id,
                    'meet' => $schedule->meet->name,
                    'event' => sprintf(
                        '%s — %s (%s, %s)',
                        $schedule->event->sport->name,
                        $schedule->event->name,
                        $schedule->event->gender->label(),
                        $schedule->event->age_division->label(),
                    ),
                    'venue' => $schedule->venue->name,
                    'date' => $schedule->scheduled_date->toDateString(),
                    'date_label' => $schedule->scheduled_date->format('D, M j, Y'),
                    'starts_at' => substr($schedule->starts_at, 0, 5),
                    'ends_at' => substr($schedule->ends_at, 0, 5),
                    'note' => $schedule->note,
                ]),
            'filters' => [
                'search' => $search,
                'meet_id' => $meetId > 0 ? $meetId : null,
                'venue_id' => $venueId > 0 ? $venueId : null,
                'date' => $date !== '' ? $date : null,
            ],
            'meetFilterOptions' => Meet::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'venueFilterOptions' => Venue::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
            'schedulableMeets' => $schedulableMeets
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'eventOptionsByMeet' => Event::query()
                ->whereHas('meets', fn ($meets) => $meets->whereIn(
                    'meets.id',
                    $schedulableMeets->pluck('id'),
                ))
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
            'venueOptions' => Venue::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create a schedule slot.
     */
    public function store(ScheduleRequest $request): RedirectResponse
    {
        $data = $request->slotData();

        $this->assertSlotIsValid($data);

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
        $data = $request->slotData();

        $this->assertSlotIsValid($data, $schedule);

        $schedule->update($data);

        $this->audit->record('schedule.updated', $schedule, $this->context($schedule));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Schedule slot updated.')]);

        return back();
    }

    /**
     * Delete a schedule slot.
     */
    public function destroy(EventSchedule $schedule): RedirectResponse
    {
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
     * Enforce the scheduling rules: meet window, event-in-meet, venue conflict.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertSlotIsValid(array $data, ?EventSchedule $ignore = null): void
    {
        $meet = Meet::query()->findOrFail((int) $data['meet_id']);

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
