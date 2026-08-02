<?php

use App\Enums\BilletingAssignmentStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Models\AuditLog;
use App\Models\BilletingAssignment;
use App\Models\BilletingVenue;
use App\Models\Delegation;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia;

/**
 * Billeting (WP-REALIGN-11) — one status-tracked assignment per (meet,
 * delegation), plus a real third authorization tier `SupplyPolicy`/
 * `FoodPolicy` don't need: a DelegationOfficer may read (not manage)
 * their own delegation's assignment. See
 * docs/food-billeting-transport.md.
 */
function billetingMember(?Meet $meet = null): User
{
    $meet ??= Meet::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::Billeting]);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return $member->user;
}

// --- Model relationships ---

test('a meet has many billeting venues, a venue has many assignments', function () {
    $meet = Meet::factory()->create();
    $venue = BilletingVenue::factory()->create(['meet_id' => $meet->id]);
    $assignment = BilletingAssignment::factory()->create(['billeting_venue_id' => $venue->id]);

    expect($meet->billetingVenues()->first()->id)->toBe($venue->id)
        ->and($venue->assignments()->first()->id)->toBe($assignment->id);
});

test('a meet cannot have two billeting venues with the same name', function () {
    $meet = Meet::factory()->create();
    BilletingVenue::factory()->create(['meet_id' => $meet->id, 'name' => 'North Lodge']);

    expect(fn () => BilletingVenue::factory()->create(['meet_id' => $meet->id, 'name' => 'North Lodge']))
        ->toThrow(QueryException::class);
});

test('a delegation cannot be billeted twice for the same meet', function () {
    $meet = Meet::factory()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    BilletingAssignment::factory()->create(['meet_id' => $meet->id, 'delegation_id' => $delegation->id]);

    expect(fn () => BilletingAssignment::factory()->create(['meet_id' => $meet->id, 'delegation_id' => $delegation->id]))
        ->toThrow(QueryException::class);
});

// --- BilletingVenueController::index() ---

test('guests are redirected from the billeting page', function () {
    $this->get('/billeting')->assertRedirect('/login');
});

test('a plain viewer cannot view the billeting page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/billeting')
        ->assertForbidden();
});

test('admins, organizers, and active billeting team members see every venue with canManage true', function () {
    $meet = Meet::factory()->create();
    $venue = BilletingVenue::factory()->create(['meet_id' => $meet->id]);
    BilletingAssignment::factory()->create(['billeting_venue_id' => $venue->id, 'meet_id' => $meet->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/billeting')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('billeting/index')
            ->has('venues', 1)
            ->has('venues.0.assignments', 1)
            ->where('canManage', true));

    $this->actingAs(billetingMember($meet))
        ->get('/billeting')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', true));
});

test('a delegation officer sees only their own delegation\'s assignment, read-only, with other delegations filtered out', function () {
    $meet = Meet::factory()->create();
    $venue = BilletingVenue::factory()->create(['meet_id' => $meet->id]);

    $ownDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $otherDelegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    BilletingAssignment::factory()->create([
        'billeting_venue_id' => $venue->id,
        'meet_id' => $meet->id,
        'delegation_id' => $ownDelegation->id,
        'room_detail' => 'Room 12',
    ]);
    BilletingAssignment::factory()->create([
        'billeting_venue_id' => $venue->id,
        'meet_id' => $meet->id,
        'delegation_id' => $otherDelegation->id,
        'room_detail' => 'Room 99',
    ]);

    $officer = User::factory()->delegationOfficer()->create();
    $ownDelegation->officers()->attach($officer->id);

    $this->actingAs($officer)
        ->get('/billeting')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('billeting/index')
            ->has('venues', 1)
            ->has('venues.0.assignments', 1)
            ->where('venues.0.assignments.0.room_detail', 'Room 12')
            ->where('canManage', false));
});

test('a delegation officer with no billeting assignment sees an empty venue list', function () {
    $officer = User::factory()->delegationOfficer()->create();

    $this->actingAs($officer)
        ->get('/billeting')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('venues', 0));
});

test('a technical official cannot view the billeting page', function () {
    $this->actingAs(User::factory()->technicalOfficial()->create())
        ->get('/billeting')
        ->assertForbidden();
});

// --- BilletingVenueController mutations ---

test('organizers can create, update, and remove a venue', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/billeting-venues', ['meet_id' => $meet->id, 'name' => 'South Lodge', 'capacity' => 50])
        ->assertSessionHasNoErrors();

    $venue = BilletingVenue::query()->where('name', 'South Lodge')->firstOrFail();
    expect(AuditLog::query()->where('action', 'billeting_venue.created')->exists())->toBeTrue();

    $this->actingAs(User::factory()->organizer()->create())
        ->put("/billeting-venues/{$venue->id}", ['name' => 'South Lodge', 'capacity' => 75])
        ->assertSessionHasNoErrors();

    expect($venue->fresh()->capacity)->toBe(75);

    $this->actingAs(User::factory()->organizer()->create())
        ->delete("/billeting-venues/{$venue->id}")
        ->assertSessionHasNoErrors();

    expect(BilletingVenue::query()->whereKey($venue->id)->exists())->toBeFalse();
});

test('a delegation officer cannot create a venue or assignment', function () {
    $meet = Meet::factory()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer->id);

    $this->actingAs($officer)
        ->post('/billeting-venues', ['meet_id' => $meet->id, 'name' => 'X'])
        ->assertForbidden();

    $venue = BilletingVenue::factory()->create(['meet_id' => $meet->id]);

    $this->actingAs($officer)
        ->post('/billeting-assignments', ['billeting_venue_id' => $venue->id, 'delegation_id' => $delegation->id])
        ->assertForbidden();
});

// --- BilletingAssignmentController ---

test('organizers can assign a delegation and update its status', function () {
    $meet = Meet::factory()->create();
    $venue = BilletingVenue::factory()->create(['meet_id' => $meet->id]);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/billeting-assignments', [
            'billeting_venue_id' => $venue->id,
            'delegation_id' => $delegation->id,
            'room_detail' => 'Room 4',
        ])
        ->assertSessionHasNoErrors();

    $assignment = BilletingAssignment::query()->where('delegation_id', $delegation->id)->firstOrFail();
    expect($assignment->status)->toBe(BilletingAssignmentStatus::Assigned);

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/billeting-assignments/{$assignment->id}/status", ['status' => BilletingAssignmentStatus::CheckedIn->value])
        ->assertSessionHasNoErrors();

    expect($assignment->fresh()->status)->toBe(BilletingAssignmentStatus::CheckedIn);
    expect(AuditLog::query()->where('action', 'billeting_assignment.status_updated')->exists())->toBeTrue();
});

test('assigning a delegation from a different meet fails with a field error', function () {
    $venue = BilletingVenue::factory()->create();
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/billeting-assignments', ['billeting_venue_id' => $venue->id, 'delegation_id' => $delegation->id])
        ->assertSessionHasErrors('delegation_id');
});

test('assigning an already-billeted delegation fails with a field error', function () {
    $meet = Meet::factory()->create();
    $venue = BilletingVenue::factory()->create(['meet_id' => $meet->id]);
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    BilletingAssignment::factory()->create(['billeting_venue_id' => $venue->id, 'meet_id' => $meet->id, 'delegation_id' => $delegation->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/billeting-assignments', ['billeting_venue_id' => $venue->id, 'delegation_id' => $delegation->id])
        ->assertSessionHasErrors('delegation_id');
});
