<?php

namespace App\Http\Controllers;

use App\Enums\TransportRequestStatus;
use App\Enums\TransportTripStatus;
use App\Models\TransportRequest;
use App\Models\TransportTrip;
use App\Models\Vehicle;
use App\Policies\TransportPolicy;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Dispatch a vehicle for a trip, optionally fulfilling a
 * `TransportRequest` (WP-REALIGN-11) — Transport Team/Admin/Organizer
 * only. Creating a trip against a `transport_request_id` also flips that
 * request's status to Fulfilled, the same "creating the fulfilling
 * record updates the fulfilled record's status" shape
 * `EquipmentReturnController` already uses for `EquipmentIssue::status`.
 * See docs/food-billeting-transport.md.
 */
class TransportTripController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TransportPolicy $policy,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'delegation_id' => ['nullable', 'integer', Rule::exists('delegations', 'id')],
            'transport_request_id' => ['nullable', 'integer', Rule::exists('transport_requests', 'id')],
            'pickup_location' => ['required', 'string', 'max:200'],
            'dropoff_location' => ['required', 'string', 'max:200'],
            'scheduled_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        abort_unless($this->policy->manage($request->user(), $vehicle->meet), 403);

        $transportRequest = null;

        if (! empty($validated['transport_request_id'])) {
            $transportRequest = TransportRequest::query()->findOrFail((int) $validated['transport_request_id']);

            if ($transportRequest->meet_id !== $vehicle->meet_id) {
                throw ValidationException::withMessages([
                    'transport_request_id' => __('That request belongs to a different meet.'),
                ]);
            }

            if ($transportRequest->status !== TransportRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'transport_request_id' => __('That request is no longer pending.'),
                ]);
            }
        }

        $trip = DB::transaction(function () use ($vehicle, $validated, $transportRequest): TransportTrip {
            $trip = TransportTrip::create([
                ...$validated,
                'meet_id' => $vehicle->meet_id,
                'status' => TransportTripStatus::Dispatched,
            ]);

            $transportRequest?->forceFill(['status' => TransportRequestStatus::Fulfilled])->save();

            return $trip;
        });

        $this->audit->record('transport_trip.created', $trip, [
            'vehicle' => $vehicle->plate_number,
            'fulfills_request_id' => $trip->transport_request_id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trip dispatched.')]);

        return back();
    }

    public function updateStatus(Request $request, TransportTrip $transportTrip): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $transportTrip->meet), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TransportTripStatus::class)],
        ]);

        $transportTrip->forceFill($validated)->save();

        $this->audit->record('transport_trip.status_updated', $transportTrip, [
            'vehicle' => $transportTrip->vehicle->plate_number,
            'status' => $transportTrip->status->value,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trip status updated.')]);

        return back();
    }

    public function destroy(Request $request, TransportTrip $transportTrip): RedirectResponse
    {
        abort_unless($this->policy->manage($request->user(), $transportTrip->meet), 403);

        $context = ['vehicle' => $transportTrip->vehicle->plate_number];

        $transportTrip->delete();

        $this->audit->record('transport_trip.deleted', $transportTrip, $context);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Trip removed.')]);

        return back();
    }
}
