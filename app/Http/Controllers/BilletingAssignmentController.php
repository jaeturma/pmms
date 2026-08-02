<?php

namespace App\Http\Controllers;

use App\Enums\BilletingAssignmentStatus;
use App\Models\BilletingAssignment;
use App\Models\BilletingVenue;
use App\Models\Delegation;
use App\Policies\BilletingPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Assign a delegation to a billeting venue (WP-REALIGN-11) — Billeting
 * Team/Admin/Organizer only, never a DelegationOfficer (read-only via
 * `BilletingVenueController::index()`'s row-scoping). See
 * docs/food-billeting-transport.md.
 */
class BilletingAssignmentController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly BilletingPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'billeting_venue_id' => ['required', 'integer', Rule::exists('billeting_venues', 'id')],
            'delegation_id' => ['required', 'integer', Rule::exists('delegations', 'id')],
            'room_detail' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:120'],
        ]);

        $venue = BilletingVenue::query()->findOrFail((int) $validated['billeting_venue_id']);
        abort_unless($this->policy->manage($request->user(), $venue->meet), 403);

        $delegation = Delegation::query()->findOrFail((int) $validated['delegation_id']);

        if ($delegation->meet_id !== $venue->meet_id) {
            throw ValidationException::withMessages([
                'delegation_id' => __('That delegation belongs to a different meet.'),
            ]);
        }

        if (BilletingAssignment::query()
            ->where('meet_id', $venue->meet_id)
            ->where('delegation_id', $delegation->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'delegation_id' => __('This delegation is already billeted somewhere for this meet.'),
            ]);
        }

        $assignment = BilletingAssignment::create([
            'billeting_venue_id' => $venue->id,
            'delegation_id' => $delegation->id,
            'meet_id' => $venue->meet_id,
            'room_detail' => $validated['room_detail'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'status' => BilletingAssignmentStatus::Assigned,
            'assigned_at' => now(),
        ]);

        $this->audit->record('billeting_assignment.created', $assignment, [
            'venue' => $venue->name,
            'delegation' => $delegation->registrantName(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Delegation assigned.')]);

        return back();
    }

    public function updateStatus(Request $request, BilletingAssignment $billetingAssignment): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $billetingAssignment->meet), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(BilletingAssignmentStatus::class)],
        ]);

        $billetingAssignment->forceFill($validated)->save();

        $this->audit->record('billeting_assignment.status_updated', $billetingAssignment, [
            'delegation' => $billetingAssignment->delegation->registrantName(),
            'status' => $billetingAssignment->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assignment status updated.')]);

        return back();
    }

    public function destroy(Request $request, BilletingAssignment $billetingAssignment): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $billetingAssignment->meet), 403);

        $context = [
            'venue' => $billetingAssignment->billetingVenue->name,
            'delegation' => $billetingAssignment->delegation->registrantName(),
        ];

        $billetingAssignment->delete();

        $this->audit->record('billeting_assignment.deleted', $billetingAssignment, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Assignment removed.')]);

        return back();
    }
}
