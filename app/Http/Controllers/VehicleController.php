<?php

namespace App\Http\Controllers;

use App\Enums\ManagementTeamType;
use App\Enums\TransportRequestStatus;
use App\Http\Controllers\Concerns\ScopesToManagementTeam;
use App\Models\Delegation;
use App\Models\Meet;
use App\Models\TransportRequest;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use App\Policies\TransportPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Management UI for the Transport Team's vehicle roster and trip/request
 * queue (WP-REALIGN-11) — see docs/food-billeting-transport.md. `Vehicle`
 * is this page's primary entity (the roster Transport Team maintains,
 * same role `EquipmentCategory` plays for Supply); trips/requests are
 * managed inline via `TransportTripController`/`TransportRequestController`.
 */
class VehicleController extends Controller
{
    use ScopesToManagementTeam;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TransportPolicy $policy,
    ) {}

    /**
     * Managers see every vehicle/trip/request for their accessible
     * meets. A DelegationOfficer with no Transport Team membership sees
     * only vehicles/trips tied to their own delegation, and only their
     * own delegation's requests.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($this->policy->viewAny($user), 403);

        $meetId = $request->integer('meet_id');
        $accessibleMeetIds = $this->accessibleMeetIds($user, ManagementTeamType::Transport);
        $canManage = $accessibleMeetIds === null || $accessibleMeetIds->isNotEmpty();

        $vehicleQuery = Vehicle::query()
            ->with([
                'meet:id,name',
                'trips.delegation.school:id,name',
                'trips.delegation.district:id,name',
                'trips.transportRequest',
            ]);

        $requestQuery = TransportRequest::query()
            ->with(['meet:id,name', 'delegation.school:id,name', 'delegation.district:id,name'])
            ->where('status', TransportRequestStatus::Pending);

        if ($accessibleMeetIds !== null && $accessibleMeetIds->isNotEmpty()) {
            $vehicleQuery->whereIn('meet_id', $accessibleMeetIds);
            $requestQuery->whereIn('meet_id', $accessibleMeetIds);
        } elseif (! $canManage) {
            $vehicleQuery->whereHas('trips.delegation.officers', fn ($q) => $q->whereKey($user->id));
            $requestQuery->whereHas('delegation.officers', fn ($q) => $q->whereKey($user->id));
        }

        $vehicleQuery->when($meetId > 0, fn ($q) => $q->where('meet_id', $meetId));
        $requestQuery->when($meetId > 0, fn ($q) => $q->where('meet_id', $meetId));

        $vehicles = $vehicleQuery->orderBy('plate_number')->get();
        $requests = $requestQuery->orderBy('requested_at')->get();

        $meetOptions = Meet::query()
            ->when($accessibleMeetIds !== null, fn ($q) => $q->whereIn('id', $accessibleMeetIds))
            ->orderByDesc('id')
            ->get(['id', 'name'])
            ->map(fn (Meet $meet): array => ['id' => $meet->id, 'label' => $meet->name]);

        return Inertia::render('transport/index', [
            'vehicles' => $vehicles->map(fn (Vehicle $vehicle): array => $this->vehicleRow($vehicle, $user, $canManage)),
            'requests' => $requests->map(fn (TransportRequest $tr): array => [
                'id' => $tr->id,
                'meet_id' => $tr->meet_id,
                'meet' => $tr->meet->name,
                'delegation_id' => $tr->delegation_id,
                'delegation' => $tr->delegation->registrantName(),
                'pickup_location' => $tr->pickup_location,
                'dropoff_location' => $tr->dropoff_location,
                'requested_at' => $tr->requested_at->toDayDateTimeString(),
                'passenger_count' => $tr->passenger_count,
                'notes' => $tr->notes,
            ])->values(),
            'filters' => ['meet_id' => $meetId > 0 ? $meetId : null],
            'meetOptions' => $meetOptions,
            'delegationOptions' => Delegation::query()->with(['school:id,name', 'district:id,name'])
                ->when(! $canManage, fn ($q) => $q->whereHas('officers', fn ($o) => $o->whereKey($user->id)))
                ->get()
                ->map(fn (Delegation $delegation): array => [
                    'id' => $delegation->id,
                    'meet_id' => $delegation->meet_id,
                    'label' => $delegation->registrantName(),
                ]),
            'canManage' => $canManage,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function vehicleRow(Vehicle $vehicle, User $user, bool $canManage): array
    {
        $trips = $canManage
            ? $vehicle->trips
            : $vehicle->trips->filter(fn (TransportTrip $t): bool => $t->delegation?->hasOfficer($user) === true);

        return [
            'id' => $vehicle->id,
            'meet_id' => $vehicle->meet_id,
            'meet' => $vehicle->meet->name,
            'plate_number' => $vehicle->plate_number,
            'type' => $vehicle->type,
            'capacity' => $vehicle->capacity,
            'driver_name' => $vehicle->driver_name,
            'driver_phone' => $vehicle->driver_phone,
            'notes' => $vehicle->notes,
            'trips' => $trips->map(fn (TransportTrip $t): array => [
                'id' => $t->id,
                'delegation' => $t->delegation?->registrantName(),
                'pickup_location' => $t->pickup_location,
                'dropoff_location' => $t->dropoff_location,
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'scheduled_at' => $t->scheduled_at->toDayDateTimeString(),
                'fulfills_request_id' => $t->transport_request_id,
            ])->values()->all(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'plate_number' => ['required', 'string', 'max:20'],
            'type' => ['nullable', 'string', 'max:30'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'driver_name' => ['nullable', 'string', 'max:120'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $meet = Meet::query()->findOrFail((int) $validated['meet_id']);
        abort_unless($this->policy->manage($request->user(), $meet), 403);

        if (Vehicle::query()
            ->where('meet_id', $validated['meet_id'])
            ->where('plate_number', $validated['plate_number'])
            ->exists()) {
            throw ValidationException::withMessages([
                'plate_number' => __('This meet already has a vehicle with that plate number.'),
            ]);
        }

        $vehicle = Vehicle::create($validated);

        $this->audit->record('vehicle.created', $vehicle, [
            'meet' => $meet->name,
            'plate_number' => $vehicle->plate_number,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle added.')]);

        return back();
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $vehicle->meet), 403);

        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:30'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'driver_name' => ['nullable', 'string', 'max:120'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $vehicle->fill($validated)->save();

        $this->audit->record('vehicle.updated', $vehicle, [
            'meet' => $vehicle->meet->name,
            'plate_number' => $vehicle->plate_number,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle updated.')]);

        return back();
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $vehicle->meet), 403);

        $context = ['meet' => $vehicle->meet->name, 'plate_number' => $vehicle->plate_number];

        $vehicle->delete();

        $this->audit->record('vehicle.deleted', $vehicle, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vehicle removed.')]);

        return back();
    }
}
