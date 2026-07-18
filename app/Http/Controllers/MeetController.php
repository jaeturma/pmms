<?php

namespace App\Http\Controllers;

use App\Enums\MeetStatus;
use App\Http\Requests\MeetRequest;
use App\Models\Event;
use App\Models\Meet;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MeetController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * List all meets.
     */
    public function index(): Response
    {
        return Inertia::render('meets/index', [
            'meets' => Meet::query()
                ->with('events:id')
                ->orderByDesc('starts_at')
                ->get()
                ->map(fn (Meet $meet): array => [
                    'id' => $meet->id,
                    'name' => $meet->name,
                    'school_year' => $meet->school_year,
                    'starts_at' => $meet->starts_at->toDateString(),
                    'ends_at' => $meet->ends_at->toDateString(),
                    'venue' => $meet->venue,
                    'status' => $meet->status->value,
                    'status_label' => $meet->status->label(),
                    'event_ids' => $meet->events->pluck('id')->all(),
                    'allowed_transitions' => array_map(
                        fn (MeetStatus $status): array => [
                            'value' => $status->value,
                            'label' => $status->actionLabel(),
                        ],
                        $meet->status->allowedTransitions(),
                    ),
                ])
                ->values(),
            'eventOptions' => Event::query()
                ->where('active', true)
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
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create a meet (always starts as draft).
     */
    public function store(MeetRequest $request): RedirectResponse
    {
        $meet = Meet::create($request->validated());

        $this->audit->record('meet.created', $meet, ['name' => $meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet created.')]);

        return back();
    }

    /**
     * Update a meet's details (status changes go through updateStatus).
     */
    public function update(MeetRequest $request, Meet $meet): RedirectResponse
    {
        $meet->update($request->validated());

        $this->audit->record('meet.updated', $meet, ['name' => $meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet updated.')]);

        return back();
    }

    /**
     * Move the meet through its lifecycle, rejecting invalid transitions.
     */
    public function updateStatus(Request $request, Meet $meet): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(MeetStatus::class)],
        ]);

        $target = MeetStatus::from($validated['status']);

        if (! $meet->status->canTransitionTo($target)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('That status change is not allowed.'),
            ]);

            return back();
        }

        $from = $meet->status;
        $meet->forceFill(['status' => $target])->save();

        $this->audit->record('meet.status_changed', $meet, [
            'name' => $meet->name,
            'from' => $from->value,
            'to' => $target->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet status updated.')]);

        return back();
    }

    /**
     * Replace the set of catalog events that run in this meet.
     */
    public function syncEvents(Request $request, Meet $meet): RedirectResponse
    {
        $validated = $request->validate([
            'event_ids' => ['array'],
            'event_ids.*' => ['integer', Rule::exists('events', 'id')],
        ]);

        /** @var array<int, int> $eventIds */
        $eventIds = $validated['event_ids'] ?? [];

        $meet->events()->sync($eventIds);

        $this->audit->record('meet.events_updated', $meet, [
            'name' => $meet->name,
            'event_count' => count($eventIds),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet events updated.')]);

        return back();
    }

    /**
     * Delete a meet that is still a draft.
     */
    public function destroy(Meet $meet): RedirectResponse
    {
        if ($meet->status !== MeetStatus::Draft) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only draft meets can be deleted.'),
            ]);

            return back();
        }

        $meet->delete();

        $this->audit->record('meet.deleted', $meet, ['name' => $meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet deleted.')]);

        return back();
    }
}
