<?php

namespace App\Http\Controllers;

use App\Models\MedicalAccessLog;
use App\Models\MedicalClearance;
use App\Policies\MedicalPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Break-glass emergency access to Medical detail (WP-REALIGN-12) — any
 * staff role may invoke this for one specific clearance record with a
 * required reason; the detail is returned via a one-shot Inertia flash
 * (`emergencyAccess`), the same pattern the existing `toast` flash
 * already uses, so nothing needs to be persisted client-side or
 * session-tracked beyond a normal redirect-back. `review()` closes the
 * loop decision #2's mandatory post-use review requires. See
 * docs/medical-drrm.md.
 */
class MedicalAccessController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly MedicalPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'medical_clearance_id' => ['required', 'integer', Rule::exists('medical_clearances', 'id')],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        abort_unless($this->policy->requestEmergencyAccess($user), 403);

        $clearance = MedicalClearance::query()->findOrFail((int) $validated['medical_clearance_id']);

        $log = MedicalAccessLog::create([
            'medical_clearance_id' => $clearance->id,
            'accessed_by_user_id' => $user->id,
            'reason' => $validated['reason'],
            'accessed_at' => now(),
        ]);

        $this->audit->record('medical_access.requested', $log, [
            'person' => $clearance->personName(),
            'reason' => $validated['reason'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Emergency access granted — logged for review.')]);
        Inertia::flash('emergencyAccess', [
            'clearance_id' => $clearance->id,
            'person' => $clearance->personName(),
            'conditions' => $clearance->conditions,
            'emergency_contact_name' => $clearance->emergency_contact_name,
            'emergency_contact_phone' => $clearance->emergency_contact_phone,
            'notes' => $clearance->notes,
        ]);

        return back();
    }

    public function review(Request $request, MedicalAccessLog $medicalAccessLog): RedirectResponse
    {
        $medicalAccessLog->loadMissing('medicalClearance.meet');

        abort_unless($this->policy->reviewAccess($request->user(), $medicalAccessLog->medicalClearance->meet), 403);

        $validated = $request->validate([
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $medicalAccessLog->forceFill([
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ])->save();

        $this->audit->record('medical_access.reviewed', $medicalAccessLog, [
            'person' => $medicalAccessLog->medicalClearance->personName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Access reviewed.')]);

        return back();
    }
}
