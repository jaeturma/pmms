<?php

namespace App\Http\Controllers;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Http\Controllers\Concerns\SearchesAndPaginates;
use App\Models\Incident;
use App\Models\Meet;
use App\Models\User;
use App\Models\Venue;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    use SearchesAndPaginates;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Meet-day incident log, kept by managers (routes are manager-gated).
     */
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $meetId = $request->integer('meet_id');

        $query = Incident::query()
            ->with(['meet:id,name', 'venue:id,name', 'reportedBy:id,name'])
            ->orderByDesc('id');

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($meetId > 0) {
            $query->where('meet_id', $meetId);
        }

        return Inertia::render('incidents/index', [
            'incidents' => $query->paginate($this->registryPageSize)->withQueryString()
                ->through(fn (Incident $incident): array => [
                    'id' => $incident->id,
                    'meet_id' => $incident->meet_id,
                    'venue_id' => $incident->venue_id,
                    'meet' => $incident->meet->name,
                    'venue' => $incident->venue?->name,
                    'description' => $incident->description,
                    'severity' => $incident->severity->value,
                    'severity_label' => $incident->severity->label(),
                    'medical_referral' => $incident->medical_referral,
                    'status' => $incident->status->value,
                    'status_label' => $incident->status->label(),
                    'reported_by' => $incident->reportedBy?->name,
                    'reported_at' => $incident->created_at?->toDayDateTimeString(),
                    'resolved_at' => $incident->resolved_at?->toDayDateTimeString(),
                ]),
            'filters' => [
                'status' => $status !== '' ? $status : null,
                'meet_id' => $meetId > 0 ? $meetId : null,
            ],
            'severityOptions' => array_map(
                fn (IncidentSeverity $severity): array => [
                    'value' => $severity->value,
                    'label' => $severity->label(),
                ],
                IncidentSeverity::cases(),
            ),
            'meetOptions' => Meet::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]),
            'venueOptions' => Venue::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
        ]);
    }

    /**
     * Log an incident. Medical incidents carry only the referral flag —
     * never medical details.
     */
    public function store(Request $request): RedirectResponse
    {
        $incident = Incident::create($this->validated($request));

        /** @var User $user */
        $user = $request->user();

        $incident->forceFill(['reported_by' => $user->id])->save();

        $this->audit->record('incident.reported', $incident, $this->context($incident));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident logged.')]);

        return back();
    }

    /**
     * Update an incident's details.
     */
    public function update(Request $request, Incident $incident): RedirectResponse
    {
        $incident->update($this->validated($request));

        $this->audit->record('incident.updated', $incident, $this->context($incident));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident updated.')]);

        return back();
    }

    /**
     * Mark an incident resolved.
     */
    public function resolve(Incident $incident): RedirectResponse
    {
        if ($incident->status === IncidentStatus::Resolved) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This incident is already resolved.'),
            ]);

            return back();
        }

        $incident->forceFill([
            'status' => IncidentStatus::Resolved,
            'resolved_at' => now(),
        ])->save();

        $this->audit->record('incident.resolved', $incident, $this->context($incident));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident resolved.')]);

        return back();
    }

    /**
     * Reopen a resolved incident.
     */
    public function reopen(Incident $incident): RedirectResponse
    {
        if ($incident->status === IncidentStatus::Open) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This incident is already open.'),
            ]);

            return back();
        }

        $incident->forceFill([
            'status' => IncidentStatus::Open,
            'resolved_at' => null,
        ])->save();

        $this->audit->record('incident.reopened', $incident, $this->context($incident));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident reopened.')]);

        return back();
    }

    /**
     * Delete an incident log entry.
     */
    public function destroy(Incident $incident): RedirectResponse
    {
        $context = $this->context($incident);

        $incident->delete();

        $this->audit->record('incident.deleted', $incident, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Incident deleted.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'description' => ['required', 'string', 'max:500'],
            'severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'medical_referral' => ['boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Incident $incident): array
    {
        $incident->loadMissing(['meet:id,name', 'venue:id,name']);

        return [
            'meet' => $incident->meet->name,
            'venue' => $incident->venue?->name,
            'severity' => $incident->severity->value,
            'medical_referral' => $incident->medical_referral,
        ];
    }
}
