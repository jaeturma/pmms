<?php

use App\Enums\DrrmCategory;
use App\Enums\EmergencyIncidentStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Models\AuditLog;
use App\Models\DrrmPlan;
use App\Models\EmergencyIncident;
use App\Models\EvacuationRoute;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\ReadinessChecklist;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueEmergencyPlan;
use Inertia\Testing\AssertableInertia;

/**
 * DRRM (WP-REALIGN-12) — standard two-tier authorization (unlike
 * Medical), split across two pages: pre-event planning/readiness and
 * live incident response. See docs/medical-drrm.md.
 */
function drrmTeamMember(?Meet $meet = null): User
{
    $meet ??= Meet::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::DRRM]);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return $member->user;
}

// --- Model relationships ---

test('a meet has many drrm plans; a venue has many emergency plans and routes', function () {
    $meet = Meet::factory()->create();
    $plan = DrrmPlan::factory()->create(['meet_id' => $meet->id]);
    $venue = Venue::factory()->create();
    $venuePlan = VenueEmergencyPlan::factory()->create(['venue_id' => $venue->id]);
    $route = EvacuationRoute::factory()->create(['venue_id' => $venue->id]);

    expect($meet->drrmPlans()->first()->id)->toBe($plan->id)
        ->and($venue->emergencyPlans()->first()->id)->toBe($venuePlan->id)
        ->and($venue->evacuationRoutes()->first()->id)->toBe($route->id);
});

test('a venue with an emergency plan or evacuation route cannot be deleted', function () {
    $venueWithPlan = Venue::factory()->create();
    VenueEmergencyPlan::factory()->create(['venue_id' => $venueWithPlan->id]);

    $venueWithRoute = Venue::factory()->create();
    EvacuationRoute::factory()->create(['venue_id' => $venueWithRoute->id]);

    expect($venueWithPlan->isInUse())->toBeTrue()
        ->and($venueWithRoute->isInUse())->toBeTrue();
});

// --- DrrmPlanController::index() ---

test('guests are redirected from the drrm plans page', function () {
    $this->get('/drrm/plans')->assertRedirect('/login');
});

test('a plain viewer cannot view the drrm plans page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/drrm/plans')
        ->assertForbidden();
});

test('admins, organizers, and active drrm team members can view the plans page', function () {
    $meet = Meet::factory()->create();
    DrrmPlan::factory()->create(['meet_id' => $meet->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/drrm/plans')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('drrm/plans')
            ->has('plans', 1));

    $this->actingAs(drrmTeamMember($meet))
        ->get('/drrm/plans')
        ->assertInertia(fn (AssertableInertia $page) => $page->has('plans', 1));
});

// --- CRUD across the 8 tables (representative coverage) ---

test('organizers can create a drrm plan', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/drrm-plans', [
            'meet_id' => $meet->id,
            'category' => DrrmCategory::Weather->value,
            'title' => 'Typhoon Response Plan',
            'description' => 'Evacuate to the covered court.',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('drrm_plans', ['meet_id' => $meet->id, 'title' => 'Typhoon Response Plan']);
    expect(AuditLog::query()->where('action', 'drrm_plan.created')->exists())->toBeTrue();
});

test('a delegation officer cannot create a drrm plan', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->post('/drrm-plans', [
            'meet_id' => $meet->id,
            'category' => DrrmCategory::Security->value,
            'title' => 'X',
            'description' => 'Y',
        ])
        ->assertForbidden();
});

test('organizers can add a venue emergency plan and an evacuation route', function () {
    $meet = Meet::factory()->create();
    $venue = Venue::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/venue-emergency-plans', [
            'meet_id' => $meet->id,
            'venue_id' => $venue->id,
            'plan_detail' => 'Assembly point at the parking lot.',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('venue_emergency_plans', ['venue_id' => $venue->id, 'meet_id' => $meet->id]);

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/evacuation-routes', [
            'meet_id' => $meet->id,
            'venue_id' => $venue->id,
            'name' => 'Route A',
            'description' => 'Exit via the east gate.',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('evacuation_routes', ['venue_id' => $venue->id, 'name' => 'Route A']);
});

test('organizers can add an emergency contact and drrm equipment', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/emergency-contacts', [
            'meet_id' => $meet->id,
            'name' => 'Barangay Health Center',
            'phone' => '0912-345-6789',
            'category' => DrrmCategory::Medical->value,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('emergency_contacts', ['meet_id' => $meet->id, 'name' => 'Barangay Health Center']);

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/drrm-equipment', [
            'meet_id' => $meet->id,
            'name' => 'First Aid Kit',
            'quantity' => 5,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('drrm_equipment', ['meet_id' => $meet->id, 'name' => 'First Aid Kit', 'quantity' => 5]);
});

test('organizers can add a readiness checklist item and toggle it complete', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/readiness-checklists', [
            'meet_id' => $meet->id,
            'category' => DrrmCategory::Weather->value,
            'item' => 'Confirm covered-court capacity.',
        ])
        ->assertSessionHasNoErrors();

    $checklist = ReadinessChecklist::query()->where('meet_id', $meet->id)->firstOrFail();
    expect($checklist->is_complete)->toBeFalse();

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/readiness-checklists/{$checklist->id}/status", ['is_complete' => true])
        ->assertSessionHasNoErrors();

    expect($checklist->fresh())
        ->is_complete->toBeTrue()
        ->completed_by_user_id->not->toBeNull();
});

// --- Emergency incidents + communication log ---

test('organizers can report an incident and it defaults to reported status', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/emergency-incidents', [
            'meet_id' => $meet->id,
            'category' => DrrmCategory::Security->value,
            'description' => 'Unauthorized person near the venue perimeter.',
        ])
        ->assertSessionHasNoErrors();

    $incident = EmergencyIncident::query()->where('meet_id', $meet->id)->firstOrFail();
    expect($incident->status)->toBe(EmergencyIncidentStatus::Reported);
});

test('resolving an incident sets resolved_at', function () {
    $incident = EmergencyIncident::factory()->create(['status' => EmergencyIncidentStatus::Responding]);

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/emergency-incidents/{$incident->id}/status", ['status' => EmergencyIncidentStatus::Resolved->value])
        ->assertSessionHasNoErrors();

    expect($incident->fresh())
        ->status->toBe(EmergencyIncidentStatus::Resolved)
        ->resolved_at->not->toBeNull();
});

test('a message can be appended to an incident\'s communication log', function () {
    $incident = EmergencyIncident::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/emergency-communication-logs', [
            'emergency_incident_id' => $incident->id,
            'message' => 'Evacuated Gym per Plan A.',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('emergency_communication_logs', [
        'emergency_incident_id' => $incident->id,
        'message' => 'Evacuated Gym per Plan A.',
    ]);
    expect(AuditLog::query()->where('action', 'emergency_communication_log.created')->exists())->toBeTrue();
});

test('a technical official cannot manage drrm data', function (string $method, string $uri, array $payload) {
    $this->actingAs(User::factory()->technicalOfficial()->create())
        ->{$method}($uri, $payload)
        ->assertForbidden();
})->with([
    'create plan' => fn () => ['post', '/drrm-plans', ['meet_id' => Meet::factory()->create()->id, 'category' => DrrmCategory::Weather->value, 'title' => 'X', 'description' => 'Y']],
    'report incident' => fn () => ['post', '/emergency-incidents', ['meet_id' => Meet::factory()->create()->id, 'category' => DrrmCategory::Weather->value, 'description' => 'Y']],
]);
