<?php

namespace App\Http\Controllers;

use App\Enums\MeetStatus;
use App\Http\Requests\MeetRequest;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MeetController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * The one meet this deployment runs — a settings page, not a list.
     * See `Meet::current()`.
     */
    public function index(): Response
    {
        $meet = Meet::current()->load('events:id');

        return Inertia::render('meets/index', [
            'meet' => [
                'id' => $meet->id,
                'name' => $meet->name,
                'school_year' => $meet->school_year,
                'starts_at' => $meet->starts_at->toDateString(),
                'ends_at' => $meet->ends_at->toDateString(),
                'venue' => $meet->venue,
                'status' => $meet->status->value,
                'status_label' => $meet->status->label(),
                'is_published' => $meet->is_published,
                'is_active' => $meet->is_active,
                'event_ids' => $meet->events->pluck('id')->all(),
                'allowed_transitions' => array_map(
                    fn (MeetStatus $status): array => [
                        'value' => $status->value,
                        'label' => $status->actionLabel(),
                    ],
                    $meet->status->allowedTransitions(),
                ),
            ],
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
     * Update the meet's details (status changes go through updateStatus).
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

        // Keeps meet_sports current as events are added — the migration
        // that created the table only backfilled from history that
        // existed at deploy time (see
        // docs/architecture/pmms-data-migration-plan.md §5); this is the
        // live equivalent for events attached afterward. Never removes a
        // meet_sports row here even if its last event is detached —
        // WP-REALIGN-07's assignments may already reference it, and
        // `MeetSport.active` exists precisely so an admin can deactivate
        // rather than lose the row.
        $sportIds = Event::query()->whereIn('id', $eventIds)->pluck('sport_id')->unique();

        foreach ($sportIds as $sportId) {
            MeetSport::query()->firstOrCreate(['meet_id' => $meet->id, 'sport_id' => $sportId]);
        }

        $this->audit->record('meet.events_updated', $meet, [
            'name' => $meet->name,
            'event_count' => count($eventIds),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet events updated.')]);

        return back();
    }

    /**
     * Publish a meet to the public portal. Draft meets cannot be
     * published — there is nothing official about them yet.
     */
    public function publish(Meet $meet): RedirectResponse
    {
        if ($meet->status === MeetStatus::Draft) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Draft meets cannot be published.'),
            ]);

            return back();
        }

        if ($meet->is_published) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This meet is already published.'),
            ]);

            return back();
        }

        $meet->forceFill(['is_published' => true])->save();

        $this->audit->record('meet.published', $meet, ['name' => $meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet published to the public portal.')]);

        return back();
    }

    /**
     * Remove a meet from the public portal, effective immediately.
     */
    public function unpublish(Meet $meet): RedirectResponse
    {
        if (! $meet->is_published) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This meet is not published.'),
            ]);

            return back();
        }

        $meet->forceFill(['is_published' => false])->save();

        $this->audit->record('meet.unpublished', $meet, ['name' => $meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet removed from the public portal.')]);

        return back();
    }

    /**
     * Make this the one meet featured on the public landing page.
     * Auto-exclusive: any other active meet is deactivated in the same
     * transaction, so at most one row ever has is_active = true.
     */
    public function activate(Meet $meet): RedirectResponse
    {
        if (! $meet->is_published) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Only published meets can be set active.'),
            ]);

            return back();
        }

        if ($meet->is_active) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This meet is already active.'),
            ]);

            return back();
        }

        DB::transaction(function () use ($meet): void {
            Meet::query()->where('is_active', true)->update(['is_active' => false]);
            $meet->forceFill(['is_active' => true])->save();
        });

        $this->audit->record('meet.activated', $meet, ['name' => $meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet set active on the public landing page.')]);

        return back();
    }

    /**
     * Stop featuring this meet on the public landing page, leaving no
     * meet active until another one is explicitly activated.
     */
    public function deactivate(Meet $meet): RedirectResponse
    {
        if (! $meet->is_active) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This meet is not active.'),
            ]);

            return back();
        }

        $meet->forceFill(['is_active' => false])->save();

        $this->audit->record('meet.deactivated', $meet, ['name' => $meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Meet removed from the public landing page.')]);

        return back();
    }
}
