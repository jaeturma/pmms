<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Models\Sport;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * List all events.
     */
    public function index(): Response
    {
        return Inertia::render('catalog/events', [
            'events' => Event::query()
                ->with('sport:id,name')
                ->orderBy('name')
                ->get([
                    'id',
                    'sport_id',
                    'name',
                    'gender',
                    'age_division',
                    'is_team_event',
                    'max_entries_per_delegation',
                    'active',
                ]),
            'sports' => Sport::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'canManage' => Gate::allows('manage-meet-data'),
        ]);
    }

    /**
     * Create an event.
     */
    public function store(EventRequest $request): RedirectResponse
    {
        $event = Event::create($request->validated());

        $this->audit->record('event.created', $event, ['name' => $event->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')]);

        return back();
    }

    /**
     * Update an event.
     */
    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

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
    public function destroy(Event $event): RedirectResponse
    {
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
}
