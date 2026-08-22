<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\UserRole;
use App\Models\AccountProvision;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\DdOPAA2026UserProvisioningSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('production provision creates one username account and preserves its password on rerun', function () {
    config()->set('pmms.accounts.default_reset_password', 'Configured-Initial-Password-2026!');

    $person = Person::query()->create([
        'source_key' => 'REAL_PERSON',
        'full_name' => 'REAL PERSON',
        'normalized_name' => 'REAL PERSON',
    ]);
    $assignment = MeetSportAssignment::factory()->create([
        'meet_sport_id' => MeetSport::factory()->create()->id,
        'person_id' => $person->id,
        'user_id' => null,
        'role' => MeetSportAssignmentRole::TournamentSecretary,
    ]);
    $provision = AccountProvision::query()->create([
        'person_id' => $person->id,
        'suggested_username' => 'real.person',
        'email' => null,
        'target_role' => 'sport_personnel',
        'status' => 'pending',
        'reason' => 'Production source',
    ]);

    $this->seed(DdOPAA2026UserProvisioningSeeder::class);

    $user = User::query()->where('username', 'real.person')->sole();
    expect($user->email)->toBeNull()
        ->and($user->role)->toBe(UserRole::Organizer)
        ->and($user->must_change_password)->toBeFalse()
        ->and(Hash::check('Configured-Initial-Password-2026!', $user->password))->toBeTrue()
        ->and($person->fresh()->user_id)->toBe($user->id)
        ->and($assignment->fresh()->user_id)->toBe($user->id)
        ->and($provision->fresh()->linked_user_id)->toBe($user->id)
        ->and($provision->fresh()->status)->toBe('active');

    $user->forceFill([
        'password' => Hash::make('Personal-Password-After-Activation!'),
        'must_change_password' => false,
        'password_changed_at' => now(),
    ])->save();

    $this->seed(DdOPAA2026UserProvisioningSeeder::class);

    expect(User::query()->where('username', 'real.person')->count())->toBe(1)
        ->and(Hash::check('Personal-Password-After-Activation!', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->must_change_password)->toBeFalse()
        ->and($provision->fresh()->status)->toBe('active');
});

test('password changes are optional after login', function () {
    $user = User::factory()->create([
        'username' => 'real.official',
        'password' => Hash::make('Initial-Password-2026!'),
        'must_change_password' => true,
    ]);

    $this->actingAs($user)->get('/dashboard')->assertOk();
    $this->actingAs($user)->get('/change-password')->assertOk();

    $this->actingAs($user)->put('/change-password', [
        'password' => 'Replacement-Password-2026!',
        'password_confirmation' => 'Replacement-Password-2026!',
    ])->assertRedirect('/dashboard');

    expect($user->fresh()->must_change_password)->toBeFalse()
        ->and($user->fresh()->password_changed_at)->not->toBeNull()
        ->and(Hash::check('Replacement-Password-2026!', $user->fresh()->password))->toBeTrue();
});

test('a provisioned account authenticates with its production username', function () {
    $user = User::factory()->create([
        'username' => 'real.tournament.manager',
        'email' => null,
        'password' => Hash::make('Initial-Password-2026!'),
        'must_change_password' => true,
    ]);

    $this->post('/login', [
        'email' => 'real.tournament.manager',
        'password' => 'Initial-Password-2026!',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
    $this->get('/dashboard')->assertOk();
});

test('provisioning refuses records without an approved assignment scope', function () {
    config()->set('pmms.accounts.default_reset_password', 'Configured-Initial-Password-2026!');

    $person = Person::query()->create([
        'source_key' => 'UNSCOPED_PERSON',
        'full_name' => 'UNSCOPED PERSON',
        'normalized_name' => 'UNSCOPED PERSON',
    ]);
    $provision = AccountProvision::query()->create([
        'person_id' => $person->id,
        'suggested_username' => 'unscoped.person',
        'target_role' => 'sport_personnel',
        'status' => 'pending',
    ]);

    $this->seed(DdOPAA2026UserProvisioningSeeder::class);

    expect(User::query()->where('username', 'unscoped.person')->exists())->toBeFalse()
        ->and($provision->fresh()->status)->toBe('failed');
});
