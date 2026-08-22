<?php

use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\AccountProvision;
use App\Models\MeetSportAssignment;
use App\Models\Person;
use App\Models\User;
use App\Notifications\AccountActivationInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

function provisionedPerson(): Person
{
    return Person::query()->create([
        'source_key' => fake()->unique()->bothify('PERSON_####'),
        'full_name' => fake()->name(),
        'normalized_name' => fake()->unique()->name(),
        'source_flags' => [],
    ]);
}

test('an administrator can send an account activation invitation', function () {
    Notification::fake();
    $person = provisionedPerson();
    $provision = AccountProvision::query()->create([
        'person_id' => $person->id,
        'suggested_username' => fake()->unique()->userName(),
        'target_role' => UserRole::TechnicalOfficial->value,
        'status' => 'pending',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('account-provisions.invite', $provision), [
            'email' => 'official@example.test',
            'target_role' => UserRole::TechnicalOfficial->value,
        ])
        ->assertRedirect();

    $provision->refresh();
    expect($provision->status)->toBe('invited')
        ->and($provision->email)->toBe('official@example.test')
        ->and($provision->activation_token_hash)->not->toBeNull()
        ->and($provision->invited_at)->not->toBeNull();

    Notification::assertSentOnDemand(AccountActivationInvitation::class);
});

test('an invited person activates and receives their assigned role and sport scope', function () {
    $token = 'a-secure-single-use-activation-token';
    $person = provisionedPerson();
    $assignment = MeetSportAssignment::factory()->create([
        'person_id' => $person->id,
        'user_id' => null,
    ]);
    $provision = AccountProvision::query()->create([
        'person_id' => $person->id,
        'suggested_username' => fake()->unique()->userName(),
        'email' => 'new.official@example.test',
        'target_role' => UserRole::TechnicalOfficial->value,
        'status' => 'invited',
    ]);
    $provision->forceFill([
        'activation_token_hash' => hash('sha256', $token),
        'invited_at' => now(),
    ])->save();

    $this->post(route('account-activation.activate', $token), [
        'password' => 'A-Strong-Password-2026!',
        'password_confirmation' => 'A-Strong-Password-2026!',
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'new.official@example.test')->firstOrFail();
    expect($user->role)->toBe(UserRole::TechnicalOfficial)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('A-Strong-Password-2026!', $user->password))->toBeTrue()
        ->and($person->fresh()->user_id)->toBe($user->id)
        ->and($assignment->fresh()->user_id)->toBe($user->id)
        ->and($assignment->fresh()->status)->toBe(MeetSportAssignmentStatus::Active)
        ->and($assignment->fresh()->start_date)->not->toBeNull()
        ->and($user->sports()->whereKey($assignment->meetSport->sport_id)->exists())->toBeTrue()
        ->and($provision->fresh()->status)->toBe('activated')
        ->and($provision->fresh()->activation_token_hash)->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('expired and already used activation links cannot create accounts', function (string $status, int $daysAgo) {
    $token = fake()->sha256();
    $person = provisionedPerson();
    $provision = AccountProvision::query()->create([
        'person_id' => $person->id,
        'suggested_username' => fake()->unique()->userName(),
        'email' => fake()->unique()->safeEmail(),
        'target_role' => UserRole::TournamentManager->value,
        'status' => $status,
    ]);
    $provision->forceFill([
        'activation_token_hash' => hash('sha256', $token),
        'invited_at' => now()->subDays($daysAgo),
    ])->save();

    $this->get(route('account-activation.show', $token))->assertStatus(410);
})->with([
    'expired' => ['invited', 8],
    'already activated' => ['activated', 0],
]);
