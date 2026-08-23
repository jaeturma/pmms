<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\TransportRequestStatus;
use App\Enums\TransportTripStatus;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\TransportRequest;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use Inertia\Testing\AssertableInertia;

/**
 * Transport (WP-REALIGN-11) — vehicle roster + request/trip queue,
 * same three-tier authorization shape as Billeting, plus one real manage
 * action a DelegationOfficer gets: filing their own request. See
 * docs/food-billeting-transport.md.
 */
function transportMember(?Meet $meet = null): User
{
    $meet ??= Meet::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::Transport]);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return $member->user;
}

// --- Model relationships ---

test('a meet has many vehicles, a vehicle has many trips', function () {
    $meet = Meet::factory()->create();
    $vehicle = Vehicle::factory()->create(['meet_id' => $meet->id]);
    $trip = TransportTrip::factory()->create(['vehicle_id' => $vehicle->id]);

    expect($meet->vehicles()->first()->id)->toBe($vehicle->id)
        ->and($vehicle->trips()->first()->id)->toBe($trip->id);
});

// --- VehicleController::index() ---

test('guests are redirected from the transport page', function () {
    $this->get('/transport')->assertRedirect('/login');
});

test('a plain viewer cannot view the transport page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/transport')
        ->assertForbidden();
});

test('admins, organizers, and active transport team members see every vehicle with canManage true', function () {
    $meet = Meet::factory()->create();
    $vehicle = Vehicle::factory()->create(['meet_id' => $meet->id]);
    TransportTrip::factory()->create(['meet_id' => $meet->id, 'vehicle_id' => $vehicle->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/transport')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('transport/index')
            ->has('vehicles', 1)
            ->has('vehicles.0.trips', 1)
            ->where('canManage', true));

    $this->actingAs(transportMember($meet))
        ->get('/transport')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', true));
});

test('a delegation officer sees only trips/requests tied to their own delegation, read-only', function () {
    $meet = Meet::factory()->create();
    $vehicle = Vehicle::factory()->create(['meet_id' => $meet->id]);

    $ownDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $otherDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    TransportTrip::factory()->create(['meet_id' => $meet->id, 'vehicle_id' => $vehicle->id, 'delegation_id' => $ownDelegation->id]);
    TransportTrip::factory()->create(['meet_id' => $meet->id, 'vehicle_id' => $vehicle->id, 'delegation_id' => $otherDelegation->id]);
    TransportRequest::factory()->create(['meet_id' => $meet->id, 'delegation_id' => $ownDelegation->id]);
    TransportRequest::factory()->create(['meet_id' => $meet->id, 'delegation_id' => $otherDelegation->id]);

    $officer = User::factory()->delegationOfficer()->create();
    $ownDelegation->officers()->attach($officer->id);

    $this->actingAs($officer)
        ->get('/transport')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('transport/index')
            ->has('vehicles', 1)
            ->has('vehicles.0.trips', 1)
            ->has('requests', 1)
            ->where('canManage', false));
});

// --- VehicleController mutations ---

test('organizers can create, update, and remove a vehicle', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/vehicles', ['meet_id' => $meet->id, 'plate_number' => 'ABC-1234'])
        ->assertSessionHasNoErrors();

    $vehicle = Vehicle::query()->where('plate_number', 'ABC-1234')->firstOrFail();
    expect(AuditLog::query()->where('action', 'vehicle.created')->exists())->toBeTrue();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/vehicles/{$vehicle->id}", ['driver_name' => 'Juan Dela Cruz'])
        ->assertSessionHasNoErrors();

    expect($vehicle->fresh()->driver_name)->toBe('Juan Dela Cruz');

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/vehicles/{$vehicle->id}")
        ->assertSessionHasNoErrors();

    expect(Vehicle::query()->whereKey($vehicle->id)->exists())->toBeFalse();
});

test('a delegation officer cannot create a vehicle', function () {
    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->post('/vehicles', ['meet_id' => Meet::factory()->create()->id, 'plate_number' => 'XYZ-0001'])
        ->assertForbidden();
});

// --- TransportRequestController ---

test('a delegation officer can file a request for their own delegation', function () {
    $meet = Meet::factory()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer->id);

    $this->actingAs($officer)
        ->post('/transport-requests', [
            'delegation_id' => $delegation->id,
            'pickup_location' => 'Gym A',
            'dropoff_location' => 'Airport',
            'requested_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('transport_requests', [
        'delegation_id' => $delegation->id,
        'status' => TransportRequestStatus::Pending->value,
    ]);
    expect(AuditLog::query()->where('action', 'transport_request.created')->exists())->toBeTrue();
});

test('a delegation officer cannot file a request for a delegation that is not their own', function () {
    $delegation = Delegation::factory()->create();
    $officer = User::factory()->delegationOfficer()->create();

    $this->actingAs($officer)
        ->post('/transport-requests', [
            'delegation_id' => $delegation->id,
            'pickup_location' => 'Gym A',
            'dropoff_location' => 'Airport',
            'requested_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertForbidden();
});

test('a delegation officer cannot cancel or delete a request, even their own', function () {
    $delegation = Delegation::factory()->create();
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer->id);
    $request = TransportRequest::factory()->create(['meet_id' => $delegation->meet_id, 'delegation_id' => $delegation->id]);

    $this->actingAs($officer)
        ->patch("/transport-requests/{$request->id}/status", ['status' => TransportRequestStatus::Cancelled->value])
        ->assertForbidden();

    $this->actingAs($officer)
        ->delete("/transport-requests/{$request->id}")
        ->assertForbidden();
});

// --- TransportTripController: request fulfillment ---

test('dispatching a trip against a pending request flips the request to fulfilled', function () {
    $meet = Meet::factory()->create();
    $vehicle = Vehicle::factory()->create(['meet_id' => $meet->id]);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $request = TransportRequest::factory()->create([
        'meet_id' => $meet->id,
        'delegation_id' => $delegation->id,
        'status' => TransportRequestStatus::Pending,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/transport-trips', [
            'vehicle_id' => $vehicle->id,
            'delegation_id' => $delegation->id,
            'transport_request_id' => $request->id,
            'pickup_location' => 'Gym A',
            'dropoff_location' => 'Airport',
            'scheduled_at' => now()->addHours(3)->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors();

    $trip = TransportTrip::query()->where('transport_request_id', $request->id)->firstOrFail();
    expect($trip->status)->toBe(TransportTripStatus::Dispatched)
        ->and($request->fresh()->status)->toBe(TransportRequestStatus::Fulfilled);

    expect(AuditLog::query()->where('action', 'transport_trip.created')->exists())->toBeTrue();
});

test('dispatching a trip without a request works (e.g. an officials shuttle)', function () {
    $meet = Meet::factory()->create();
    $vehicle = Vehicle::factory()->create(['meet_id' => $meet->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/transport-trips', [
            'vehicle_id' => $vehicle->id,
            'pickup_location' => 'Hotel',
            'dropoff_location' => 'Venue',
            'scheduled_at' => now()->addHour()->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('transport_trips', ['vehicle_id' => $vehicle->id, 'delegation_id' => null]);
});

test('dispatching against an already-fulfilled request fails with a field error', function () {
    $meet = Meet::factory()->create();
    $vehicle = Vehicle::factory()->create(['meet_id' => $meet->id]);
    $request = TransportRequest::factory()->create(['meet_id' => $meet->id, 'status' => TransportRequestStatus::Fulfilled]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/transport-trips', [
            'vehicle_id' => $vehicle->id,
            'transport_request_id' => $request->id,
            'pickup_location' => 'A',
            'dropoff_location' => 'B',
            'scheduled_at' => now()->addHour()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('transport_request_id');
});

test('organizers can update a trip\'s status', function () {
    $trip = TransportTrip::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/transport-trips/{$trip->id}/status", ['status' => TransportTripStatus::EnRoute->value])
        ->assertSessionHasNoErrors();

    expect($trip->fresh()->status)->toBe(TransportTripStatus::EnRoute);
    expect(AuditLog::query()->where('action', 'transport_trip.status_updated')->exists())->toBeTrue();
});
