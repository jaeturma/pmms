<?php

use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\School;
use App\Models\User;
use App\Services\RegistrationDataConsistencyService;
use Inertia\Testing\AssertableInertia;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutVite();
});

test('system administrators and tournament ICT can view problematic registration data', function (User $user) {
    $delegationSchool = School::factory()->create();
    $otherSchool = School::factory()->create();
    $delegation = Delegation::factory()->create(['school_id' => $delegationSchool->id, 'district_id' => null]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $otherSchool->id]);

    $this->actingAs($user)->get('/data-repair')->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('data-repair/index')
            ->has('issues', fn (AssertableInertia $issues) => $issues
                ->where('0.type', 'athlete')
                ->where('0.id', $athlete->id)
                ->where('0.code', 'school_mismatch')
                ->where('0.repair.code', 'use_delegation_school')
                ->etc()));
})->with([
    'admin' => fn () => User::factory()->admin()->create(),
    'ICT' => fn () => User::factory()->create(['role' => UserRole::TournamentICT]),
]);

test('unambiguous athlete school inconsistencies can be repaired automatically', function () {
    $delegationSchool = School::factory()->create();
    $delegation = Delegation::factory()->create(['school_id' => $delegationSchool->id, 'district_id' => null]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => School::factory()->create()->id]);

    $this->actingAs(User::factory()->admin()->create())->post('/data-repair/repair', [
        'type' => 'athlete', 'id' => $athlete->id, 'code' => 'use_delegation_school',
    ])->assertSessionHasNoErrors();

    expect($athlete->fresh()->school_id)->toBe($delegationSchool->id)
        ->and(app(RegistrationDataConsistencyService::class)->issues()
            ->where('type', 'athlete')->where('id', $athlete->id)->where('code', 'school_mismatch'))->toBeEmpty();
});

test('ambiguous municipality school inconsistencies require manual selection', function () {
    $municipality = District::factory()->create();
    $otherMunicipality = District::factory()->create();
    $delegation = Delegation::factory()->create(['school_id' => null, 'district_id' => $municipality->id]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => School::factory()->create(['district_id' => $otherMunicipality->id])->id,
    ]);

    $issue = app(RegistrationDataConsistencyService::class)->issues()
        ->first(fn (array $issue): bool => $issue['type'] === 'athlete' && $issue['id'] === $athlete->id && $issue['code'] === 'municipality_mismatch');

    expect($issue)->not->toBeNull()->and($issue['repair'])->toBeNull();
});

test('ordinary users cannot access data repair', function () {
    $this->actingAs(User::factory()->create())->get('/data-repair')->assertForbidden();
});

test('authenticated server failures provide ICT with a data repair solution', function () {
    config()->set('app.debug', false);
    Route::middleware('web')->get('/test-data-failure', fn () => throw new RuntimeException('broken linked test data'));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/test-data-failure')
        ->assertStatus(500)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('error')
            ->where('status', 500)
            ->where('canRepair', true)
            ->where('title', 'Unable to complete this information'));
});
