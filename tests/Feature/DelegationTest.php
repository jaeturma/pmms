<?php

use App\Enums\DelegationStatus;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Meet;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function delegationOfficerFor(Delegation $delegation): User
{
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    return $officer;
}

test('guests are redirected from the delegations page', function () {
    $this->get('/delegations')->assertRedirect('/login');
});

test('managers see all delegations while officers see only their own', function () {
    $mine = Delegation::factory()->create();
    Delegation::factory()->create();
    $officer = delegationOfficerFor($mine);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/delegations')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('delegations/index')
            ->has('delegations.data', 2)
            ->where('canManage', true));

    $this->actingAs($officer)
        ->get('/delegations')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('delegations.data', 1)
            ->where('delegations.data.0.id', $mine->id)
            ->where('canManage', false));
});

test('the delegation list can be searched by head and school name', function () {
    $school = School::factory()->create(['name' => 'Bagong Silang Integrated School']);
    $target = Delegation::factory()->create([
        'school_id' => $school->id,
        'head_name' => 'Corazon Villareal',
    ]);
    Delegation::factory()->create(['head_name' => 'Benigno Santos']);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/delegations?search=Villareal')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('delegations.data', 1)
            ->where('delegations.data.0.id', $target->id));

    $this->actingAs($admin)
        ->get('/delegations?search=Bagong Silang')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('delegations.data', 1)
            ->where('delegations.data.0.id', $target->id));
});

test('organizers can register a delegation for an open meet', function () {
    $meet = Meet::factory()->registrationOpen()->create();
    $school = School::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/delegations', [
            'meet_id' => $meet->id,
            'school_id' => $school->id,
            'head_name' => 'Juan Dela Cruz',
            'head_phone' => '09171234567',
            'head_email' => 'head@example.com',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('delegations', [
        'meet_id' => $meet->id,
        'school_id' => $school->id,
        'status' => DelegationStatus::Draft->value,
    ]);

    expect(AuditLog::query()->where('action', 'delegation.created')->exists())->toBeTrue();
});

test('registration is blocked when the meet is not open', function () {
    $meet = Meet::factory()->create();
    $school = School::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/delegations', [
            'meet_id' => $meet->id,
            'school_id' => $school->id,
            'head_name' => 'Juan Dela Cruz',
        ])
        ->assertRedirect();

    $this->assertDatabaseMissing('delegations', ['meet_id' => $meet->id]);
});

test('a school can have only one delegation per meet', function () {
    $existing = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/delegations', [
            'meet_id' => $existing->meet_id,
            'school_id' => $existing->school_id,
            'head_name' => 'Juan Dela Cruz',
        ])
        ->assertSessionHasErrors('school_id');
});

test('officers and viewers cannot register delegations', function (User $user) {
    $meet = Meet::factory()->registrationOpen()->create();
    $school = School::factory()->create();

    $this->actingAs($user)
        ->post('/delegations', [
            'meet_id' => $meet->id,
            'school_id' => $school->id,
            'head_name' => 'Juan Dela Cruz',
        ])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('managers can assign officers with the delegation officer role', function () {
    $delegation = Delegation::factory()->create();
    $officer = User::factory()->delegationOfficer()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->put("/delegations/{$delegation->id}/officers", ['user_ids' => [$officer->id]])
        ->assertRedirect();

    expect($delegation->officers()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'delegation.officers_updated')->exists())->toBeTrue();
});

test('users without the officer role cannot be assigned', function () {
    $delegation = Delegation::factory()->create();
    $viewer = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/delegations/{$delegation->id}/officers", ['user_ids' => [$viewer->id]])
        ->assertSessionHasErrors('user_ids.0');
});

test('an assigned officer can update their draft delegation while registration is open', function () {
    $delegation = Delegation::factory()->create();
    $officer = delegationOfficerFor($delegation);

    $this->actingAs($officer)
        ->put("/delegations/{$delegation->id}", ['head_name' => 'Maria Santos'])
        ->assertRedirect();

    expect($delegation->refresh()->head_name)->toBe('Maria Santos');
});

test('officers cannot update delegations that are submitted, closed, or not theirs', function (callable $setup) {
    [$delegation, $officer] = $setup();

    $this->actingAs($officer)
        ->put("/delegations/{$delegation->id}", ['head_name' => 'Blocked Update'])
        ->assertForbidden();
})->with([
    'submitted delegation' => fn () => (function () {
        $delegation = Delegation::factory()->submitted()->create();

        return [$delegation, delegationOfficerFor($delegation)];
    })(),
    'registration closed' => fn () => (function () {
        $delegation = Delegation::factory()->create(['meet_id' => Meet::factory()->create()]);

        return [$delegation, delegationOfficerFor($delegation)];
    })(),
    'unassigned officer' => fn () => [
        Delegation::factory()->create(),
        User::factory()->delegationOfficer()->create(),
    ],
]);

test('an assigned officer can submit their draft delegation', function () {
    $delegation = Delegation::factory()->create();
    $officer = delegationOfficerFor($delegation);

    $this->actingAs($officer)
        ->patch("/delegations/{$delegation->id}/submit")
        ->assertRedirect();

    expect($delegation->refresh()->status)->toBe(DelegationStatus::Submitted)
        ->and(AuditLog::query()->where('action', 'delegation.submitted')->exists())->toBeTrue();
});

test('viewers cannot submit delegations', function () {
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->create())
        ->patch("/delegations/{$delegation->id}/submit")
        ->assertForbidden();
});

test('organizers can approve submitted delegations', function () {
    $delegation = Delegation::factory()->submitted()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/delegations/{$delegation->id}/approve")
        ->assertRedirect();

    expect($delegation->refresh()->status)->toBe(DelegationStatus::Approved)
        ->and(AuditLog::query()->where('action', 'delegation.approved')->exists())->toBeTrue();
});

test('draft delegations cannot be approved', function () {
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/delegations/{$delegation->id}/approve")
        ->assertRedirect();

    expect($delegation->refresh()->status)->toBe(DelegationStatus::Draft);
});

test('officers cannot approve their own delegation', function () {
    $delegation = Delegation::factory()->submitted()->create();
    $officer = delegationOfficerFor($delegation);

    $this->actingAs($officer)
        ->patch("/delegations/{$delegation->id}/approve")
        ->assertForbidden();
});

test('organizers can return submitted delegations to draft', function () {
    $delegation = Delegation::factory()->submitted()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/delegations/{$delegation->id}/return")
        ->assertRedirect();

    expect($delegation->refresh()->status)->toBe(DelegationStatus::Draft)
        ->and(AuditLog::query()->where('action', 'delegation.returned')->exists())->toBeTrue();
});

test('draft delegations can be deleted by managers but approved ones cannot', function () {
    $draft = Delegation::factory()->create();
    $approved = Delegation::factory()->approved()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete("/delegations/{$draft->id}")->assertRedirect();
    $this->assertDatabaseMissing('delegations', ['id' => $draft->id]);

    $this->actingAs($admin)->delete("/delegations/{$approved->id}")->assertForbidden();
});

test('schools with delegations cannot be deleted', function () {
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/schools/{$delegation->school_id}")
        ->assertRedirect();

    $this->assertDatabaseHas('schools', ['id' => $delegation->school_id]);
});
