<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamStatus;
use App\Enums\ManagementTeamType;
use App\Models\AuditLog;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia;

/**
 * ManagementTeam / ManagementTeamMember (WP-REALIGN-09) — the
 * administrative shell only (who is on the team, what is its mandate).
 * Team-specific operational data (Medical records, Equipment, Meals,
 * etc.) is deliberately out of scope, per ManagementTeamType's own
 * docblock — later work packages build those.
 */

// --- Model relationships ---

test('a meet has many management teams, and a team has many members', function () {
    $meet = Meet::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id]);
    $member = ManagementTeamMember::factory()->create(['management_team_id' => $team->id]);

    expect($meet->managementTeams()->first()->id)->toBe($team->id)
        ->and($team->members()->first()->id)->toBe($member->id)
        ->and($member->managementTeam->id)->toBe($team->id)
        ->and($member->user->managementTeamMemberships()->first()->id)->toBe($member->id);
});

test('a meet can have multiple twg units of the same broad type but not the same source unit', function () {
    $meet = Meet::factory()->create();
    ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::MeetManagement, 'source_code' => 'SECRETARIAT']);
    ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::MeetManagement, 'source_code' => 'LOGISTICS']);

    expect($meet->managementTeams()->count())->toBe(2)
        ->and(fn () => ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::MeetManagement, 'source_code' => 'SECRETARIAT']))
        ->toThrow(QueryException::class);
});

test('a person cannot be added to the same team twice', function () {
    $team = ManagementTeam::factory()->create();
    $user = User::factory()->create();
    ManagementTeamMember::factory()->create(['management_team_id' => $team->id, 'user_id' => $user->id]);

    expect(fn () => ManagementTeamMember::factory()->create(['management_team_id' => $team->id, 'user_id' => $user->id]))
        ->toThrow(QueryException::class);
});

test('hasMember reflects team membership', function () {
    $team = ManagementTeam::factory()->create();
    $member = ManagementTeamMember::factory()->create(['management_team_id' => $team->id]);
    $stranger = User::factory()->create();

    expect($team->hasMember($member->user))->toBeTrue()
        ->and($team->hasMember($stranger))->toBeFalse();
});

test('deleting a team deletes its members', function () {
    $team = ManagementTeam::factory()->create();
    $member = ManagementTeamMember::factory()->create(['management_team_id' => $team->id]);

    $team->delete();

    expect(ManagementTeamMember::query()->whereKey($member->id)->exists())->toBeFalse();
});

// --- ManagementTeamController ---

test('guests are redirected from the management teams page', function () {
    $this->get('/management-teams')->assertRedirect('/login');
});

test('the management teams page is viewable by any authenticated role, including viewers', function () {
    $team = ManagementTeam::factory()->create();
    ManagementTeamMember::factory()->create(['management_team_id' => $team->id]);

    $this->actingAs(User::factory()->create())
        ->get('/management-teams')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('management-teams/index')
            ->has('teams', 13)
            ->where('canManage', false));

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/management-teams')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', false));
});

test('the management teams page can be filtered by meet', function () {
    $meetA = Meet::factory()->create();
    $meetB = Meet::factory()->create();
    ManagementTeam::factory()->create(['meet_id' => $meetA->id]);
    ManagementTeam::factory()->create(['meet_id' => $meetB->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get("/management-teams?meet_id={$meetA->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page->has('teams', 13));
});

test('organizers can create a team', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/management-teams', [
            'meet_id' => $meet->id,
            'team_type' => ManagementTeamType::Medical->value,
            'name' => 'Medical Team',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('management_teams', [
        'meet_id' => $meet->id,
        'team_type' => ManagementTeamType::Medical->value,
        'name' => 'Medical Team',
        'status' => ManagementTeamStatus::Forming->value,
    ]);

    expect(AuditLog::query()->where('action', 'management_team.created')->exists())->toBeTrue();
});

test('creating a second team of the same type for the same meet fails with a field error', function () {
    $team = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::Supply]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/management-teams', [
            'meet_id' => $team->meet_id,
            'team_type' => ManagementTeamType::Supply->value,
            'name' => 'Second Supply Team',
        ])
        ->assertSessionHasErrors('team_type');
});

test('non-managers cannot create a team', function (User $user) {
    $meet = Meet::factory()->create();

    $this->actingAs($user)
        ->post('/management-teams', [
            'meet_id' => $meet->id,
            'team_type' => ManagementTeamType::Food->value,
            'name' => 'Food Team',
        ])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
    'coach' => fn () => User::factory()->coach()->create(),
]);

test('organizers can update a team\'s name, description, and status', function () {
    $team = ManagementTeam::factory()->create(['status' => ManagementTeamStatus::Forming]);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/management-teams/{$team->id}", [
            'name' => 'Renamed Team',
            'description' => 'Now fully staffed.',
            'status' => ManagementTeamStatus::Active->value,
        ])
        ->assertSessionHasNoErrors();

    expect($team->fresh())
        ->name->toBe('Renamed Team')
        ->description->toBe('Now fully staffed.')
        ->status->toBe(ManagementTeamStatus::Active)
        ->and(AuditLog::query()->where('action', 'management_team.updated')->exists())->toBeTrue();
});

test('non-managers cannot update a team', function (User $user) {
    $team = ManagementTeam::factory()->create();

    $this->actingAs($user)
        ->put("/management-teams/{$team->id}", ['name' => 'X', 'status' => ManagementTeamStatus::Active->value])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
]);

test('organizers can remove a team', function () {
    $team = ManagementTeam::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/management-teams/{$team->id}")
        ->assertSessionHasNoErrors();

    expect(ManagementTeam::query()->whereKey($team->id)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'management_team.deleted')->exists())->toBeTrue();
});

test('non-managers cannot remove a team', function (User $user) {
    $team = ManagementTeam::factory()->create();

    $this->actingAs($user)
        ->delete("/management-teams/{$team->id}")
        ->assertForbidden();

    expect(ManagementTeam::query()->whereKey($team->id)->exists())->toBeTrue();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

// --- ManagementTeamMemberController ---

test('organizers can add any role of user as a team member — not restricted like Technical Official assignments', function () {
    $team = ManagementTeam::factory()->create();
    $delegationOfficer = User::factory()->delegationOfficer()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/management-team-members', [
            'management_team_id' => $team->id,
            'user_id' => $delegationOfficer->id,
            'role_title' => 'Logistics Coordinator',
            'is_head' => true,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('management_team_members', [
        'management_team_id' => $team->id,
        'user_id' => $delegationOfficer->id,
        'role_title' => 'Logistics Coordinator',
        'is_head' => true,
        'status' => ManagementTeamMemberStatus::Pending->value,
    ]);

    expect(AuditLog::query()->where('action', 'management_team_member.added')->exists())->toBeTrue();
});

test('the same person cannot be added to the same team twice, with a field error', function () {
    $team = ManagementTeam::factory()->create();
    $user = User::factory()->create();
    ManagementTeamMember::factory()->create(['management_team_id' => $team->id, 'user_id' => $user->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/management-team-members', [
            'management_team_id' => $team->id,
            'user_id' => $user->id,
        ])
        ->assertSessionHasErrors('user_id');
});

test('non-managers cannot add a team member', function (User $user) {
    $team = ManagementTeam::factory()->create();

    $this->actingAs($user)
        ->post('/management-team-members', [
            'management_team_id' => $team->id,
            'user_id' => User::factory()->create()->id,
        ])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'coach' => fn () => User::factory()->coach()->create(),
]);

test('organizers can update a member\'s status', function () {
    $member = ManagementTeamMember::factory()->create(['status' => ManagementTeamMemberStatus::Pending]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/management-team-members/{$member->id}/status", ['status' => ManagementTeamMemberStatus::Active->value])
        ->assertSessionHasNoErrors();

    expect($member->fresh()->status)->toBe(ManagementTeamMemberStatus::Active)
        ->and(AuditLog::query()->where('action', 'management_team_member.status_updated')->exists())->toBeTrue();
});

test('non-managers cannot update a member\'s status', function (User $user) {
    $member = ManagementTeamMember::factory()->create();

    $this->actingAs($user)
        ->patch("/management-team-members/{$member->id}/status", ['status' => ManagementTeamMemberStatus::Active->value])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
]);

test('organizers can remove a member', function () {
    $member = ManagementTeamMember::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/management-team-members/{$member->id}")
        ->assertSessionHasNoErrors();

    expect(ManagementTeamMember::query()->whereKey($member->id)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'management_team_member.removed')->exists())->toBeTrue();
});

test('non-managers cannot remove a member', function (User $user) {
    $member = ManagementTeamMember::factory()->create();

    $this->actingAs($user)
        ->delete("/management-team-members/{$member->id}")
        ->assertForbidden();

    expect(ManagementTeamMember::query()->whereKey($member->id)->exists())->toBeTrue();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);
