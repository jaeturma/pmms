<?php

namespace App\Http\Controllers;

use App\Enums\ManagementTeamType;
use App\Http\Controllers\Concerns\ScopesToManagementTeam;
use App\Models\BilletingAssignment;
use App\Models\BilletingVenue;
use App\Models\Delegation;
use App\Models\Meet;
use App\Models\User;
use App\Models\Venue;
use App\Policies\BilletingPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management UI for the Billeting Team's venue roster (WP-REALIGN-11) —
 * see docs/food-billeting-transport.md. `BilletingVenue` is this page's
 * primary entity; `BilletingAssignment`s are managed inline from here via
 * `BilletingAssignmentController`.
 */
class BilletingVenueController extends Controller
{
    use ScopesToManagementTeam;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly BilletingPolicy $policy,
    ) {}

    /**
     * Managers (Admin/Organizer/Billeting Team) see every venue for their
     * accessible meets, with every assignment. A DelegationOfficer with
     * no Billeting Team membership sees only the venue(s) their own
     * delegation is assigned to, with every OTHER delegation's assignment
     * filtered out of that venue's list — row-level scoping, not a
     * separate view.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($this->policy->viewAny($user), 403);

        $meetId = $request->integer('meet_id');
        $accessibleMeetIds = $this->accessibleMeetIds($user, ManagementTeamType::Billeting);
        $canManage = $accessibleMeetIds === null || $accessibleMeetIds->isNotEmpty();

        $query = BilletingVenue::query()
            ->with([
                'meet:id,name',
                'venue:id,name',
                'assignments.delegation.school:id,name',
                'assignments.delegation.district:id,name',
            ]);

        if ($accessibleMeetIds !== null && $accessibleMeetIds->isNotEmpty()) {
            $query->whereIn('meet_id', $accessibleMeetIds);
        } elseif (! $canManage) {
            $query->whereHas('assignments.delegation.officers', fn ($q) => $q->whereKey($user->id));
        }

        $query->when($meetId > 0, fn ($q) => $q->where('meet_id', $meetId));

        $venues = $query->orderBy('name')->get();

        $meetOptions = Meet::query()
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('id', $accessibleMeetIds))
            ->orderByDesc('id')
            ->get(['id', 'name'])
            ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]);

        return Inertia::render('billeting/index', [
            'venues' => $venues->map(fn (BilletingVenue $venue): array => $this->venueRow($venue, $user, $canManage)),
            'filters' => ['meet_id' => $meetId > 0 ? $meetId : null],
            'meetOptions' => $meetOptions,
            'venueOptions' => Venue::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (Venue $venue): array => ['id' => $venue->id, 'label' => $venue->name]),
            'delegationOptions' => $canManage
                ? Delegation::query()->with(['school:id,name', 'district:id,name'])->get()
                    ->map(fn (Delegation $delegation): array => [
                        'id' => $delegation->id,
                        'meet_id' => $delegation->meet_id,
                        'label' => $delegation->registrantName(),
                    ])
                : [],
            'canManage' => $canManage,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function venueRow(BilletingVenue $venue, User $user, bool $canManage): array
    {
        $assignments = $canManage
            ? $venue->assignments
            : $venue->assignments->filter(fn (BilletingAssignment $a): bool => $a->delegation->hasOfficer($user));

        return [
            'id' => $venue->id,
            'meet_id' => $venue->meet_id,
            'meet' => $venue->meet->name,
            'name' => $venue->name,
            'address' => $venue->address,
            'capacity' => $venue->capacity,
            'contact_name' => $venue->contact_name,
            'contact_phone' => $venue->contact_phone,
            'notes' => $venue->notes,
            'assignments' => $assignments->map(fn (BilletingAssignment $a): array => [
                'id' => $a->id,
                'delegation_id' => $a->delegation_id,
                'delegation' => $a->delegation->registrantName(),
                'room_detail' => $a->room_detail,
                'contact_name' => $a->contact_name,
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
            ])->values()->all(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'venue_id' => ['nullable', 'integer', Rule::exists('venues', 'id')],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $meet = Meet::query()->findOrFail((int) $validated['meet_id']);
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        if (BilletingVenue::query()
            ->where('meet_id', $validated['meet_id'])
            ->where('name', $validated['name'])
            ->exists()) {
            throw ValidationException::withMessages([
                'name' => __('This meet already has a billeting venue with that name.'),
            ]);
        }

        $venue = BilletingVenue::create($validated);

        $this->audit->record('billeting_venue.created', $venue, [
            'meet' => $meet->name,
            'name' => $venue->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Billeting venue added.')]);

        return back();
    }

    public function update(Request $request, BilletingVenue $billetingVenue): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $billetingVenue->meet), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $billetingVenue->fill($validated)->save();

        $this->audit->record('billeting_venue.updated', $billetingVenue, [
            'meet' => $billetingVenue->meet->name,
            'name' => $billetingVenue->name,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Billeting venue updated.')]);

        return back();
    }

    public function destroy(Request $request, BilletingVenue $billetingVenue): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $billetingVenue->meet), 403);

        $context = ['meet' => $billetingVenue->meet->name, 'name' => $billetingVenue->name];

        $billetingVenue->delete();

        $this->audit->record('billeting_venue.deleted', $billetingVenue, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Billeting venue removed.')]);

        return back();
    }
}
