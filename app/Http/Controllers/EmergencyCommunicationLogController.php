<?php

namespace App\Http\Controllers;

use App\Models\EmergencyCommunicationLog;
use App\Models\EmergencyIncident;
use App\Policies\DrrmPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Append a message to an incident's communication log — no update/
 * delete, same append-only discipline as every other transactional log
 * in this app. See docs/medical-drrm.md.
 */
class EmergencyCommunicationLogController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly DrrmPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'emergency_incident_id' => ['required', 'integer', Rule::exists('emergency_incidents', 'id')],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $incident = EmergencyIncident::query()->findOrFail((int) $validated['emergency_incident_id']);
        abort_unless($this->policy->manage($request->user(), $incident->meet), 403);

        $log = EmergencyCommunicationLog::create([
            'emergency_incident_id' => $incident->id,
            'message' => $validated['message'],
            'sent_by_user_id' => $request->user()->id,
            'sent_at' => now(),
        ]);

        $this->audit->record('emergency_communication_log.created', $log, ['meet' => $incident->meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Message logged.')]);

        return back();
    }
}
