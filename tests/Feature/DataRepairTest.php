<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\School;
use App\Models\Sport;
use App\Models\User;
use App\Services\RegistrationDataConsistencyService;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();
});

test('system administrators can view all problematic registration data', function () {
    $delegationSchool = School::factory()->create();
    $otherSchool = School::factory()->create();
    $delegation = Delegation::factory()->create(['school_id' => $delegationSchool->id, 'district_id' => null]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $otherSchool->id]);

    $this->actingAs(User::factory()->admin()->create())->get('/data-repair')->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('data-repair/index')
            ->has('issues', fn (AssertableInertia $issues) => $issues
                ->where('0.type', 'athlete')
                ->where('0.id', $athlete->id)
                ->where('0.code', 'school_mismatch')
                ->where('0.repair.code', 'use_delegation_school')
                ->etc()));
});

test('tournament ICT sees data issues only for assigned sports', function () {
    $ownSport = Sport::factory()->create();
    $otherSport = Sport::factory()->create();
    $ownEvent = Event::factory()->create(['sport_id' => $ownSport->id]);
    $otherEvent = Event::factory()->create(['sport_id' => $otherSport->id]);
    $delegationSchool = School::factory()->create();
    $delegation = Delegation::factory()->create(['school_id' => $delegationSchool->id, 'district_id' => null]);
    $ownAthlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => School::factory()->create()->id]);
    $otherAthlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => School::factory()->create()->id]);
    Entry::factory()->create(['athlete_id' => $ownAthlete->id, 'delegation_id' => $delegation->id, 'event_id' => $ownEvent->id]);
    Entry::factory()->create(['athlete_id' => $otherAthlete->id, 'delegation_id' => $delegation->id, 'event_id' => $otherEvent->id]);
    $meetSport = MeetSport::factory()->create(['meet_id' => $delegation->meet_id, 'sport_id' => $ownSport->id]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'user_id' => $ict->id,
        'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($ict)->get('/data-repair')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('issues', fn ($issues) => collect($issues)->contains(fn ($issue) => $issue['type'] === 'athlete' && $issue['id'] === $ownAthlete->id)
            && ! collect($issues)->contains(fn ($issue) => $issue['type'] === 'athlete' && $issue['id'] === $otherAthlete->id)));
});

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

test('athlete municipality school differences are not treated as repair errors', function () {
    $municipality = District::factory()->create();
    $otherMunicipality = District::factory()->create();
    $delegation = Delegation::factory()->create(['school_id' => null, 'district_id' => $municipality->id]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => School::factory()->create(['district_id' => $otherMunicipality->id])->id,
    ]);

    $issue = app(RegistrationDataConsistencyService::class)->issues()
        ->first(fn (array $issue): bool => $issue['type'] === 'athlete' && $issue['id'] === $athlete->id && $issue['code'] === 'municipality_mismatch');

    expect($issue)->toBeNull();
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
