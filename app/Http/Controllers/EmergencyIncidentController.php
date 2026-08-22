<?php

namespace App\Http\Controllers;

use App\Enums\DrrmCategory;
use App\Enums\EmergencyIncidentStatus;
use App\Enums\ManagementTeamType;
use App\Http\Controllers\Concerns\ScopesToManagementTeam;
use App\Models\EmergencyCommunicationLog;
use App\Models\EmergencyIncident;
use App\Models\Meet;
use App\Models\Venue;
use App\Policies\DrrmPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management UI for live emergency-incident response (WP-REALIGN-12) —
 * a separate page from `DrrmPlanController`'s pre-event planning/
 * readiness page, since incident response is a fundamentally different,
 * real-time workflow. See docs/medical-drrm.md.
 */
class EmergencyIncidentController extends Controller
{
    use ScopesToManagementTeam;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly DrrmPolicy $policy,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($this->policy->viewAny($user), 403);

        $meetId = Meet::current()->id;
        $accessibleMeetIds = $this->accessibleMeetIds($user, ManagementTeamType::DRRM);

        $incidents = EmergencyIncident::query()
            ->with(['meet:id,name', 'venue:id,name', 'reportedBy:id,name', 'communicationLogs.sentBy:id,name'])
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('meet_id', $accessibleMeetIds))
            ->when($meetId > 0, fn ($q) => $q->where('meet_id', $meetId))
            ->orderByDesc('reported_at')
            ->get();

        $meetOptions = Meet::query()
            ->whereKey($meetId)
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('id', $accessibleMeetIds))
            ->orderByDesc('id')
            ->get(['id', 'name'])
            ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]);

        return Inertia::render('drrm/incidents', [
            'incidents' => $incidents->map(fn (EmergencyIncident $incident): array => [
                'id' => $incident->id,
                'meet_id' => $incident->meet_id,
                'meet' => $incident->meet->name,
                'venue' => $incident->venue?->name,
                'category' => $incident->category->value,
                'category_label' => $incident->category->label(),
                'description' => $incident->description,
                'status' => $incident->status->value,
                'status_label' => $incident->status->label(),
                'reported_by' => $incident->reportedBy->name,
                'reported_at' => $incident->reported_at->toDayDateTimeString(),
                'communication_logs' => $incident->communicationLogs->map(fn (EmergencyCommunicationLog $log): array => [
                    'id' => $log->id,
                    'message' => $log->message,
                    'sent_by' => $log->sentBy->name,
                    'sent_at' => $log->sent_at->toDayDateTimeString(),
                ])->values(),
            ]),
            'filters' => ['meet_id' => $meetId > 0 ? $meetId : null],
            'meetOptions' => $meetOptions,
            'venueOptions' => Venue::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
            'categoryOptions' => array_map(
                fn (DrrmCategory $category): array => ['value' => $category->value, 'label' => $category->label()],
                DrrmCategory::cases(),
            ),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'category' => ['required', Rule::enum(DrrmCategory::class)],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $meet = Meet::query()->findOrFail((int) $validated['meet_id']);
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $incident = EmergencyIncident::create([
            ...$validated,
            'status' => EmergencyIncidentStatus::Reported,
            'reported_by_user_id' => $request->user()->id,
            'reported_at' => now(),
        ]);

        $this->audit->record('emergency_incident.created', $incident, [
            'meet' => $meet->name,
            'category' => $incident->category->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident reported.')]);

        return back();
    }

    public function updateStatus(Request $request, EmergencyIncident $emergencyIncident): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $emergencyIncident->meet), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(EmergencyIncidentStatus::class)],
        ]);

        $status = EmergencyIncidentStatus::from($validated['status']);

        $emergencyIncident->fill([
            'status' => $status,
            'resolved_at' => $status === EmergencyIncidentStatus::Resolved ? now() : null,
        ])->save();

        $this->audit->record('emergency_incident.status_updated', $emergencyIncident, [
            'meet' => $emergencyIncident->meet->name,
            'status' => $emergencyIncident->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident status updated.')]);

        return back();
    }
}
