<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

test('an administrator can manage user roles and reset passwords', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->get('/system/users')->assertOk();
    $this->actingAs($admin)->post('/system/users', [
        'name' => 'New Secretary',
        'username' => 'new.secretary',
        'email' => null,
        'role' => UserRole::Organizer->value,
    ])->assertSessionHasNoErrors();
    $this->actingAs($admin)->put("/system/users/{$user->id}", [
        'name' => 'Updated User',
        'username' => 'updated.user',
        'email' => 'updated@example.test',
        'role' => UserRole::Organizer->value,
        'disabled' => false,
    ])->assertSessionHasNoErrors();

    $this->actingAs($admin)->post("/system/users/{$user->id}/reset-password")->assertSessionHasNoErrors();

    $pending = User::factory()->create(['approval_status' => 'pending']);
    $this->actingAs($admin)->post("/system/users/{$pending->id}/approve")->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('Updated User')
        ->and($user->fresh()->role)->toBe(UserRole::Organizer)
        ->and(Hash::check('DdOPaa2026!', $user->fresh()->password))->toBeTrue()
        ->and(User::query()->where('username', 'new.secretary')->exists())->toBeTrue();
    expect($pending->fresh()->approval_status)->toBe('approved')
        ->and($pending->fresh()->approved_by)->toBe($admin->id);
});

test('only an administrator can switch into an active approved ICT account and return', function () {
    $admin = User::factory()->admin()->create();
    $ict = User::factory()->create([
        'role' => UserRole::TournamentICT,
        'approval_status' => 'approved',
        'must_change_password' => true,
    ]);

    $this->actingAs($admin)->get('/system/users')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('users.data', fn ($users) => collect($users)
                ->firstWhere('id', $ict->id)['can_impersonate'] === true));

    $this->actingAs($admin)->post("/system/users/{$ict->id}/impersonate")
        ->assertRedirect('/results')
        ->assertSessionHas('impersonator_user_id', $admin->id);
    $this->assertAuthenticatedAs($ict);
    $this->get('/dashboard')->assertOk();

    $this->post('/impersonation/stop')->assertRedirect('/system/users');
    $this->assertAuthenticatedAs($admin);
    expect(AuditLog::query()->where('action', 'user.impersonation_started')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'user.impersonation_stopped')->exists())->toBeTrue();

    $nonAdmin = User::factory()->create();
    $this->actingAs($nonAdmin)->post("/system/users/{$ict->id}/impersonate")->assertForbidden();
});

test('an administrator can assign multiple roles including tournament ICT and secretary', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['role' => UserRole::TechnicalOfficial]);

    $this->actingAs($admin)->put("/system/users/{$user->id}", [
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'role' => UserRole::TechnicalOfficial->value,
        'additional_roles' => [
            UserRole::TournamentICT->value,
            UserRole::TournamentSecretary->value,
        ],
        'disabled' => false,
    ])->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->role)->toBe(UserRole::TechnicalOfficial)
        ->and($user->hasRole(UserRole::TechnicalOfficial))->toBeTrue()
        ->and($user->hasRole(UserRole::TournamentICT))->toBeTrue()
        ->and($user->hasRole(UserRole::TournamentSecretary))->toBeTrue();
});

test('users can be filtered by role and role totals include additional roles', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['role' => UserRole::Coach, 'name' => 'Primary Coach']);
    User::factory()->create([
        'role' => UserRole::Viewer,
        'additional_roles' => [UserRole::Coach->value],
        'name' => 'Additional Coach',
    ]);
    User::factory()->create(['role' => UserRole::TournamentICT, 'name' => 'ICT Only']);

    $this->actingAs($admin)
        ->get('/system/users?role=coach')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.role', 'coach')
            ->has('users.data', 2)
            ->where('roles', fn ($roles) => collect($roles)->firstWhere('value', 'coach')['count'] === 2));
});

test('an administrator can accept a coach from user registration and activate the requested event scope', function () {
    $admin = User::factory()->admin()->create();
    $meetSport = MeetSport::factory()->create();
    $event = Event::factory()->create(['sport_id' => $meetSport->sport_id]);
    $meetSport->meet->events()->attach($event);
    $delegation = Delegation::factory()->create(['meet_id' => $meetSport->meet_id]);
    $coach = User::factory()->create(['approval_status' => 'pending']);
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'district_id' => $delegation->district_id,
        'event_id' => $event->id,
        'status' => 'pending',
    ]);
    $onboarding->events()->attach($event);

    $this->actingAs($admin)
        ->post("/system/users/{$coach->id}/approve")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($coach->fresh()->role)->toBe(UserRole::Coach)
        ->and($coach->fresh()->approval_status)->toBe('approved')
        ->and($coach->fresh()->approved_by)->toBe($admin->id)
        ->and($onboarding->fresh()->status)->toBe('approved')
        ->and(CoachAssignmentRequest::query()
            ->where('user_id', $coach->id)
            ->where('event_id', $event->id)
            ->where('status', 'approved')->exists())->toBeTrue();
});

test('the users role column includes sports and events for coaches without assignment payloads', function () {
    $admin = User::factory()->admin()->create();
    $meetSport = MeetSport::factory()->create();
    $event = Event::factory()->create([
        'sport_id' => $meetSport->sport_id,
        'name' => 'Girls Doubles',
    ]);
    $delegation = Delegation::factory()->create(['meet_id' => $meetSport->meet_id]);
    $coach = User::factory()->coach()->create(['name' => 'Scoped Coach']);
    CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'approved',
        'assigned_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/system/users?search=Scoped%20Coach')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.coach_scopes.0', $meetSport->sport->name.' — Girls Doubles')
            ->where('users.data.0.role_scopes.0.role', 'Coach')
            ->where('users.data.0.role_scopes.0.sport', $meetSport->sport->name)
            ->where('users.data.0.role_scopes.0.events.0', 'Girls Doubles')
            ->missing('users.data.0.assignments'));
});

test('the users role column includes a technical officials sport and modal events', function () {
    $admin = User::factory()->admin()->create();
    $meetSport = MeetSport::factory()->create();
    $event = Event::factory()->create([
        'sport_id' => $meetSport->sport_id,
        'name' => 'Light Flyweight',
    ]);
    $meetSport->meet->events()->attach($event);
    $official = User::factory()->technicalOfficial()->create(['name' => 'Boxing Official']);
    MeetSportAssignment::factory()->create([
        'user_id' => $official->id,
        'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($admin)
        ->get('/system/users?search=Boxing%20Official')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('users.data.0.role_scopes.0.role', 'Technical Official')
            ->where('users.data.0.role_scopes.0.sport', $meetSport->sport->name)
            ->where('users.data.0.role_scopes.0.events.0', 'Light Flyweight'));
});

test('an active ICT team member can manage system users', function () {
    $ict = User::factory()->create();
    $team = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::ICT]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    $this->actingAs($ict)->get('/system/users')->assertOk();
});

test('a tournament secretary can approve coach registrations but only an account administrator can reset passwords', function () {
    config()->set('pmms.accounts.default_reset_password', 'TestDefaultPassword123!');
    $meetSport = MeetSport::factory()->create();
    $secretary = User::factory()->create();
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $secretary->id,
        'role' => MeetSportAssignmentRole::TournamentSecretary,
        'status' => MeetSportAssignmentStatus::Active,
    ]);
    $delegation = Delegation::factory()->create(['meet_id' => $meetSport->meet_id]);
    $event = Event::factory()->create(['sport_id' => $meetSport->sport_id]);
    $meetSport->meet->events()->attach($event);
    $coach = User::factory()->coach()->create(['password' => Hash::make('OldPassword!')]);
    $coachRequest = CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => schoolForDelegation($delegation)->id,
        'status' => 'pending',
    ]);
    $pendingCoach = User::factory()->create(['approval_status' => 'pending']);
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $pendingCoach->id,
        'district_id' => $delegation->district_id,
        'event_id' => $event->id,
        'status' => 'pending',
    ]);
    $onboarding->events()->attach($event);

    $this->actingAs($secretary)->get('/coach/assignment-requests')->assertOk();
    $this->actingAs($secretary)->patch("/coach/onboarding-requests/{$onboarding->id}", ['status' => 'approved'])->assertSessionHasNoErrors();
    $this->actingAs($secretary)->post("/coach/assignment-requests/{$coachRequest->id}/reset-password")->assertForbidden();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post("/system/users/{$coach->id}/reset-password")->assertSessionHasNoErrors();

    expect(Hash::check(config('pmms.accounts.default_reset_password'), $coach->fresh()->password))->toBeTrue()
        ->and($coach->fresh()->must_change_password)->toBeTrue()
        ->and($pendingCoach->fresh()->approval_status)->toBe('approved')
        ->and($pendingCoach->fresh()->role)->toBe(UserRole::Coach)
        ->and($onboarding->fresh()->status)->toBe('approved');
});

test('tournament ICT can reset coach passwords only within the assigned sport scope', function () {
    config()->set('pmms.accounts.default_reset_password', 'ScopedResetPassword123!');
    $assignedMeetSport = MeetSport::factory()->create();
    $otherMeetSport = MeetSport::factory()->create(['meet_id' => $assignedMeetSport->meet_id]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $assignedMeetSport->id,
        'user_id' => $ict->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $assignedCoach = User::factory()->coach()->create(['password' => Hash::make('OldPassword!')]);
    $assignedOnboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $assignedCoach->id,
        'meet_sport_id' => $assignedMeetSport->id,
        'status' => 'approved',
    ]);
    $otherCoach = User::factory()->coach()->create(['password' => Hash::make('OtherOldPassword!')]);
    $otherOnboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $otherCoach->id,
        'meet_sport_id' => $otherMeetSport->id,
        'status' => 'approved',
    ]);

    $this->actingAs($ict)
        ->post("/coach/onboarding-requests/{$assignedOnboarding->id}/reset-password")
        ->assertSessionHasNoErrors();
    $this->actingAs($ict)
        ->post("/coach/onboarding-requests/{$otherOnboarding->id}/reset-password")
        ->assertForbidden();

    expect(Hash::check('ScopedResetPassword123!', $assignedCoach->fresh()->password))->toBeTrue()
        ->and($assignedCoach->fresh()->must_change_password)->toBeTrue()
        ->and(Hash::check('OtherOldPassword!', $otherCoach->fresh()->password))->toBeTrue();
});

test('ordinary users cannot manage system accounts or reset coach passwords', function () {
    $viewer = User::factory()->create();
    $target = User::factory()->create();

    $this->actingAs($viewer)->get('/system/users')->assertForbidden();
    $this->actingAs($viewer)->post("/system/users/{$target->id}/reset-password")->assertForbidden();
});

test('a system administrator can remove regular users and coaches', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $coach = User::factory()->coach()->create();
    $meetSport = MeetSport::factory()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meetSport->meet_id]);
    $event = Event::factory()->create(['sport_id' => $meetSport->sport_id]);
    $meetSport->meet->events()->attach($event);
    $assignment = CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id, 'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id, 'event_id' => $event->id,
        'status' => 'approved', 'ended_at' => null,
    ]);
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id, 'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id, 'event_id' => $event->id,
        'status' => 'pending',
    ]);

    $this->actingAs($admin)->delete("/system/users/{$user->id}")->assertSessionHasNoErrors();
    $this->actingAs($admin)->delete("/system/users/{$coach->id}")->assertSessionHasNoErrors();

    $this->assertSoftDeleted('users', ['id' => $user->id]);
    $this->assertSoftDeleted('users', ['id' => $coach->id]);
    expect($assignment->fresh()->status)->toBe('inactive')
        ->and($assignment->fresh()->ended_at)->not->toBeNull()
        ->and($onboarding->fresh()->status)->toBe('rejected');
});

test('account removal is restricted to system administrators and they cannot remove themselves', function () {
    $admin = User::factory()->admin()->create();
    $ict = User::factory()->create();
    $team = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::ICT]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'user_id' => $ict->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);
    $target = User::factory()->create();

    $this->actingAs($ict)->delete("/system/users/{$target->id}")->assertForbidden();
    $this->actingAs($admin)->delete("/system/users/{$admin->id}")->assertStatus(422);

    expect($target->fresh())->not->toBeNull()
        ->and($admin->fresh())->not->toBeNull();
});

test('administrators and ICT can permanently remove a coach and archive related athletes', function (string $reviewerType) {
    $reviewer = $reviewerType === 'admin'
        ? User::factory()->admin()->create()
        : User::factory()->create();
    if ($reviewerType === 'ict') {
        $team = ManagementTeam::factory()->create(['team_type' => ManagementTeamType::ICT]);
        ManagementTeamMember::factory()->create([
            'management_team_id' => $team->id,
            'user_id' => $reviewer->id,
            'status' => ManagementTeamMemberStatus::Active,
        ]);
    }

    $coach = User::factory()->coach()->create();
    $onboarding = CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'status' => 'approved',
    ]);
    $personnel = Personnel::factory()->coach()->create(['user_id' => $coach->id]);
    $athletes = Athlete::factory()->count(2)->create(['registered_by' => $coach->id]);

    $this->actingAs($reviewer)
        ->delete("/coach/onboarding-requests/{$onboarding->id}", ['confirm' => true])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('users', ['id' => $coach->id]);
    $this->assertDatabaseMissing('coach_onboarding_requests', ['id' => $onboarding->id]);
    $this->assertDatabaseMissing('personnel', ['id' => $personnel->id]);
    foreach ($athletes as $athlete) {
        $this->assertSoftDeleted('athletes', ['id' => $athlete->id]);
    }
})->with(['admin', 'ict']);
