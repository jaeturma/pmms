<?php

namespace App\Http\Controllers;

use App\Models\EvacuationRoute;
use App\Models\Meet;
use App\Policies\DrrmPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * `EvacuationRoute` itself has no `meet_id` — it describes the venue,
 * not one meet's data (see `DrrmPlanController::index()`'s own note) —
 * so `meet_id` here is validated only to determine which meet's DRRM
 * Team authorization applies to this mutation, never persisted onto the
 * route. See docs/medical-drrm.md.
 */
class EvacuationRouteController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly DrrmPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'venue_id' => ['required', 'integer', Rule::exists('venues', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $meet = Meet::current();
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $route = EvacuationRoute::create([
            'venue_id' => $validated['venue_id'],
            'name' => $validated['name'],
            'description' => $validated['description'],
        ]);

        $this->audit->record('evacuation_route.created', $route, ['meet' => $meet->name, 'name' => $route->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evacuation route added.')]);

        return back();
    }

    public function update(Request $request, EvacuationRoute $evacuationRoute): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $meet = Meet::current();
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $evacuationRoute->fill([
            'name' => $validated['name'],
            'description' => $validated['description'],
        ])->save();

        $this->audit->record('evacuation_route.updated', $evacuationRoute, ['meet' => $meet->name, 'name' => $evacuationRoute->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evacuation route updated.')]);

        return back();
    }

    public function destroy(Request $request, EvacuationRoute $evacuationRoute): RedirectResponse
    {
        $meet = Meet::current();
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $context = ['meet' => $meet->name, 'name' => $evacuationRoute->name];

        $evacuationRoute->delete();

        $this->audit->record('evacuation_route.deleted', $evacuationRoute, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evacuation route removed.')]);

        return back();
    }
}
