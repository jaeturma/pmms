<?php

namespace App\Http\Controllers;

use App\Models\Meet;
use App\Models\VenueEmergencyPlan;
use App\Policies\DrrmPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * See docs/medical-drrm.md.
 */
class VenueEmergencyPlanController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly DrrmPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'venue_id' => ['required', 'integer', Rule::exists('venues', 'id')],
            'plan_detail' => ['required', 'string', 'max:2000'],
        ]);

        $meet = Meet::query()->findOrFail((int) $validated['meet_id']);
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $plan = VenueEmergencyPlan::create($validated);

        $this->audit->record('venue_emergency_plan.created', $plan, ['meet' => $meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue emergency plan added.')]);

        return back();
    }

    public function update(Request $request, VenueEmergencyPlan $venueEmergencyPlan): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $venueEmergencyPlan->meet), 403);

        $validated = $request->validate([
            'plan_detail' => ['required', 'string', 'max:2000'],
        ]);

        $venueEmergencyPlan->fill($validated)->save();

        $this->audit->record('venue_emergency_plan.updated', $venueEmergencyPlan, ['meet' => $venueEmergencyPlan->meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue emergency plan updated.')]);

        return back();
    }

    public function destroy(Request $request, VenueEmergencyPlan $venueEmergencyPlan): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $venueEmergencyPlan->meet), 403);

        $context = ['meet' => $venueEmergencyPlan->meet->name];

        $venueEmergencyPlan->delete();

        $this->audit->record('venue_emergency_plan.deleted', $venueEmergencyPlan, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Venue emergency plan removed.')]);

        return back();
    }
}
