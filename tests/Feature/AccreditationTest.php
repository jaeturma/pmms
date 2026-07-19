<?php

use App\Models\Accreditation;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\Personnel;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function eligibleAthlete(?Delegation $delegation = null): Athlete
{
    $delegation ??= Delegation::factory()->approved()->create();

    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $delegation->meet_id,
    ]);

    return $athlete;
}

test('guests are redirected from the accreditation view', function () {
    $delegation = Delegation::factory()->create();

    $this->get("/delegations/{$delegation->id}/accreditation")->assertRedirect('/login');
});

test('managers and assigned officers can view accreditation; others cannot', function () {
    $delegation = Delegation::factory()->approved()->create();

    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    $this->actingAs(User::factory()->organizer()->create())
        ->get("/delegations/{$delegation->id}/accreditation")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('accreditation/index')
            ->where('canManage', true));

    $this->actingAs($officer)
        ->get("/delegations/{$delegation->id}/accreditation")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('canManage', false));

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/delegations/{$delegation->id}/accreditation")
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get("/delegations/{$delegation->id}/accreditation")
        ->assertForbidden();
});

test('the accreditation view flags who is eligible but not yet accredited', function () {
    $delegation = Delegation::factory()->approved()->create();
    eligibleAthlete($delegation)->update(['last_name' => 'Abad']);
    Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'last_name' => 'Zamora',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get("/delegations/{$delegation->id}/accreditation")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes', 2)
            ->where('athletes.0.can_accredit', true)
            ->where('athletes.0.accreditation', null)
            ->where('athletes.1.can_accredit', false));
});

test('managers can accredit an eligible athlete of an approved delegation', function () {
    $athlete = eligibleAthlete();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/accreditations', ['athlete_id' => $athlete->id])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $accreditation = Accreditation::query()->where('athlete_id', $athlete->id)->first();

    expect($accreditation)->not->toBeNull()
        ->and($accreditation->number)->toBe(sprintf('ACR-%03d-%05d', $athlete->delegation->meet_id, $accreditation->id))
        ->and(AuditLog::query()->where('action', 'accreditation.granted')->exists())->toBeTrue();
});

test('athletes without an approved eligibility review cannot be accredited', function () {
    $delegation = Delegation::factory()->approved()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/accreditations', ['athlete_id' => $athlete->id])
        ->assertSessionHasErrors('athlete_id');

    $this->assertDatabaseCount('accreditations', 0);
});

test('members of unapproved delegations cannot be accredited', function () {
    $delegation = Delegation::factory()->submitted()->create();
    $athlete = eligibleAthlete(Delegation::factory()->create());
    $person = Personnel::factory()->create(['delegation_id' => $delegation->id]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/accreditations', ['athlete_id' => $athlete->id])
        ->assertSessionHasErrors('athlete_id');

    $this->actingAs($admin)
        ->post('/accreditations', ['personnel_id' => $person->id])
        ->assertSessionHasErrors('personnel_id');

    $this->assertDatabaseCount('accreditations', 0);
});

test('managers can accredit personnel of an approved delegation', function () {
    $delegation = Delegation::factory()->approved()->create();
    $person = Personnel::factory()->create(['delegation_id' => $delegation->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/accreditations', ['personnel_id' => $person->id])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('accreditations', [
        'personnel_id' => $person->id,
        'delegation_id' => $delegation->id,
    ]);
});

test('double accreditation is rejected', function () {
    $accreditation = Accreditation::factory()->create();

    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $accreditation->athlete_id,
        'meet_id' => $accreditation->delegation->meet_id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/accreditations', ['athlete_id' => $accreditation->athlete_id])
        ->assertSessionHasErrors('athlete_id');

    $this->assertDatabaseCount('accreditations', 1);
});

test('viewers and delegation officers cannot accredit or revoke', function (User $user) {
    $athlete = eligibleAthlete();
    $accreditation = Accreditation::factory()->create();

    $this->actingAs($user)
        ->post('/accreditations', ['athlete_id' => $athlete->id])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete("/accreditations/{$accreditation->id}")
        ->assertForbidden();

    expect(Accreditation::query()->count())->toBe(1);
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('managers can revoke and later re-accredit with a new number', function () {
    $accreditation = Accreditation::factory()->create();
    $athleteId = $accreditation->athlete_id;
    $oldNumber = $accreditation->number;

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete("/accreditations/{$accreditation->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('accreditations', ['id' => $accreditation->id]);

    expect(AuditLog::query()->where('action', 'accreditation.revoked')->exists())->toBeTrue();

    $athlete = Athlete::query()->findOrFail($athleteId);
    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $athlete->id,
        'meet_id' => $athlete->delegation->meet_id,
    ]);

    $this->actingAs($admin)
        ->post('/accreditations', ['athlete_id' => $athleteId])
        ->assertSessionHasNoErrors();

    $renewed = Accreditation::query()->where('athlete_id', $athleteId)->firstOrFail();

    expect($renewed->number)->not->toBe($oldNumber);
});

test('card views are scoped like rosters and audited', function () {
    $accreditation = Accreditation::factory()->create();
    $delegation = $accreditation->delegation;

    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    $this->actingAs($officer)
        ->get("/accreditations/{$accreditation->id}/card")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('accreditation/cards')
            ->has('cards', 1)
            ->where('cards.0.number', $accreditation->number)
            ->where('cards.0.name', $accreditation->subjectName())
            ->where('cards.0.school', $delegation->school->name));

    expect(AuditLog::query()->where('action', 'accreditation.card_viewed')->exists())->toBeTrue();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/accreditations/{$accreditation->id}/card")
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get("/accreditations/{$accreditation->id}/card")
        ->assertForbidden();
});

test('batch card printing lists every accredited member of the delegation', function () {
    $accreditation = Accreditation::factory()->create();
    $delegation = $accreditation->delegation;

    Accreditation::factory()->forPersonnel()->create(['delegation_id' => $delegation->id]);
    Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get("/delegations/{$delegation->id}/accreditation/cards")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('accreditation/cards')
            ->has('cards', 2));

    expect(AuditLog::query()->where('action', 'accreditation.cards_viewed')->exists())->toBeTrue();
});

test('athlete cards carry grade and division; personnel cards carry the role', function () {
    $delegation = Delegation::factory()->approved()->create();

    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'grade_level' => 5,
    ]);
    Accreditation::factory()->create([
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get("/delegations/{$delegation->id}/accreditation/cards")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cards.0.type_label', 'Athlete')
            ->where('cards.0.detail', 'Grade 5 — Elementary'));
});
