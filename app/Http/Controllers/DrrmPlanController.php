<?php

namespace App\Http\Controllers;

use App\Enums\DrrmCategory;
use App\Enums\ManagementTeamType;
use App\Http\Controllers\Concerns\ScopesToManagementTeam;
use App\Models\DrrmEquipment;
use App\Models\DrrmPlan;
use App\Models\EmergencyContact;
use App\Models\EvacuationRoute;
use App\Models\Meet;
use App\Models\ReadinessChecklist;
use App\Models\Venue;
use App\Models\VenueEmergencyPlan;
use App\Policies\DrrmPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management UI for the DRRM Team's pre-event planning and readiness
 * data (WP-REALIGN-12) — see docs/medical-drrm.md. `DrrmPlan` is this
 * page's primary entity; venue emergency plans, evacuation routes,
 * emergency contacts, equipment, and readiness checklists are bundled
 * into the same `index()` response, the same "one index() returns every
 * related collection" shape `MealScheduleController`/`VehicleController`
 * already use, and managed inline via their own single-purpose
 * controllers. Live incident response (`EmergencyIncidentController`)
 * is a separate page — see docs/medical-drrm.md's UI section for why.
 */
class DrrmPlanController extends Controller
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

        $accessibleMeetIds = $this->accessibleMeetIds($user, ManagementTeamType::DRRM);

        $scopeToMeets = fn ($query) => $query
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('meet_id', $accessibleMeetIds));

        $plans = $scopeToMeets(DrrmPlan::query()->with('meet:id,name'))->orderByDesc('id')->get();
        $venuePlans = $scopeToMeets(VenueEmergencyPlan::query()->with(['meet:id,name', 'venue:id,name']))->orderByDesc('id')->get();
        // Evacuation routes have no meet_id — they describe the venue
        // itself (facility-level reference data, like Venue's own
        // catalog), not one meet's operational data, so they're not
        // meet-scoped beyond the page-level viewAny() gate.
        $routes = EvacuationRoute::query()->with('venue:id,name')->orderByDesc('id')->get();
        $contacts = $scopeToMeets(EmergencyContact::query()->with('meet:id,name'))->orderByDesc('id')->get();
        $equipment = $scopeToMeets(DrrmEquipment::query()->with(['meet:id,name', 'venue:id,name']))->orderByDesc('id')->get();
        $checklists = $scopeToMeets(ReadinessChecklist::query()->with(['meet:id,name', 'completedBy:id,name']))->orderBy('category')->orderBy('id')->get();

        return Inertia::render('drrm/plans', [
            'plans' => $plans->map(fn (DrrmPlan $plan): array => [
                'id' => $plan->id,
                'meet_id' => $plan->meet_id,
                'meet' => $plan->meet->name,
                'category' => $plan->category->value,
                'category_label' => $plan->category->label(),
                'title' => $plan->title,
                'description' => $plan->description,
            ]),
            'venuePlans' => $venuePlans->map(fn (VenueEmergencyPlan $plan): array => [
                'id' => $plan->id,
                'meet_id' => $plan->meet_id,
                'meet' => $plan->meet->name,
                'venue_id' => $plan->venue_id,
                'venue' => $plan->venue->name,
                'plan_detail' => $plan->plan_detail,
            ]),
            'evacuationRoutes' => $routes->map(fn (EvacuationRoute $route): array => [
                'id' => $route->id,
                'venue_id' => $route->venue_id,
                'venue' => $route->venue->name,
                'name' => $route->name,
                'description' => $route->description,
            ]),
            'emergencyContacts' => $contacts->map(fn (EmergencyContact $contact): array => [
                'id' => $contact->id,
                'meet_id' => $contact->meet_id,
                'meet' => $contact->meet->name,
                'name' => $contact->name,
                'role' => $contact->role,
                'phone' => $contact->phone,
                'category' => $contact->category?->value,
                'category_label' => $contact->category?->label(),
            ]),
            'equipment' => $equipment->map(fn (DrrmEquipment $item): array => [
                'id' => $item->id,
                'meet_id' => $item->meet_id,
                'meet' => $item->meet->name,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'venue' => $item->venue?->name,
                'notes' => $item->notes,
            ]),
            'readinessChecklists' => $checklists->map(fn (ReadinessChecklist $item): array => [
                'id' => $item->id,
                'meet_id' => $item->meet_id,
                'meet' => $item->meet->name,
                'category' => $item->category->value,
                'category_label' => $item->category->label(),
                'item' => $item->item,
                'is_complete' => $item->is_complete,
                'completed_by' => $item->completedBy?->name,
            ]),
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
            'category' => ['required', Rule::enum(DrrmCategory::class)],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $meet = Meet::current();
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        $plan = DrrmPlan::create([...$validated, 'meet_id' => $meet->id]);

        $this->audit->record('drrm_plan.created', $plan, [
            'meet' => $meet->name,
            'title' => $plan->title,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('DRRM plan added.')]);

        return back();
    }

    public function update(Request $request, DrrmPlan $drrmPlan): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $drrmPlan->meet), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $drrmPlan->fill($validated)->save();

        $this->audit->record('drrm_plan.updated', $drrmPlan, ['meet' => $drrmPlan->meet->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('DRRM plan updated.')]);

        return back();
    }

    public function destroy(Request $request, DrrmPlan $drrmPlan): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $drrmPlan->meet), 403);

        $context = ['meet' => $drrmPlan->meet->name, 'title' => $drrmPlan->title];

        $drrmPlan->delete();

        $this->audit->record('drrm_plan.deleted', $drrmPlan, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('DRRM plan removed.')]);

        return back();
    }
}
