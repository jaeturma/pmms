<?php

use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\EventResult;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * Place a school in a result at the given rank via a confirmed entry.
 */
function placeSchool(EventResult $result, School $school, int $rank, bool $tie = false): ResultPlacement
{
    $delegation = Delegation::query()
        ->where('meet_id', $result->meet_id)
        ->where('school_id', $school->id)
        ->first()
        ?? Delegation::factory()->approved()->create([
            'meet_id' => $result->meet_id,
            'school_id' => $school->id,
        ]);

    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $result->event_id,
    ]);

    return ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'entry_id' => $entry->id,
        'rank' => $rank,
        'is_tie' => $tie,
    ]);
}

test('guests are redirected from the tally', function () {
    $this->get('/tally')->assertRedirect('/login');
});

test('every authenticated role can read the tally', function (User $user) {
    $this->actingAs($user)
        ->get('/tally')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('tally/index'));
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'admin' => fn () => User::factory()->admin()->create(),
]);

test('only validated results feed the tally, ranks above three are ignored', function () {
    $validated = EventResult::factory()->validated()->create();
    $school = School::factory()->create(['name' => 'Winner School']);
    placeSchool($validated, $school, 1);
    placeSchool($validated, $school, 4);

    $encoded = EventResult::factory()->create(['meet_id' => $validated->meet_id]);
    placeSchool($encoded, School::factory()->create(), 1);

    $this->actingAs(User::factory()->create())
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 1)
            ->where('schools.0.school', 'Winner School')
            ->where('schools.0.gold', 1)
            ->where('schools.0.silver', 0)
            ->where('schools.0.total', 1));
});

test('standings follow gold, silver, bronze, then name ordering', function () {
    $result = EventResult::factory()->validated()->create();

    $silverRich = School::factory()->create(['name' => 'Beta School']);
    $goldRich = School::factory()->create(['name' => 'Zeta School']);
    $alphaTied = School::factory()->create(['name' => 'Alpha School']);

    placeSchool($result, $goldRich, 1);
    placeSchool($result, $silverRich, 2);
    placeSchool($result, $silverRich, 2, tie: true);
    placeSchool($result, $alphaTied, 3);

    $second = EventResult::factory()->validated()->create(['meet_id' => $result->meet_id]);
    placeSchool($second, $silverRich, 3);
    placeSchool($second, $alphaTied, 3);

    $this->actingAs(User::factory()->create())
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 3)
            ->where('schools.0.school', 'Zeta School')
            ->where('schools.0.position', 1)
            ->where('schools.1.school', 'Beta School')
            ->where('schools.1.silver', 2)
            ->where('schools.1.bronze', 1)
            ->where('schools.2.school', 'Alpha School')
            ->where('schools.2.bronze', 2));
});

test('tied ranks award shared medals', function () {
    $result = EventResult::factory()->validated()->create();

    $first = School::factory()->create(['name' => 'First School']);
    $second = School::factory()->create(['name' => 'Second School']);

    placeSchool($result, $first, 1, tie: true);
    placeSchool($result, $second, 1, tie: true);

    $this->actingAs(User::factory()->create())
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 2)
            ->where('schools.0.gold', 1)
            ->where('schools.1.gold', 1));
});

test('a correction ripples into the tally automatically', function () {
    $result = EventResult::factory()->validated()->create();
    $school = School::factory()->create();
    placeSchool($result, $school, 1);

    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page->has('schools', 1));

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/results/{$result->id}/correct", ['reason' => 'Wrong athlete placed first.'])
        ->assertSessionHasNoErrors();

    expect($result->refresh()->status)->toBe(ResultStatus::Encoded);

    $this->actingAs($viewer)
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page->has('schools', 0));
});

test('district standings aggregate their schools', function () {
    $result = EventResult::factory()->validated()->create();

    $district = District::factory()->create(['name' => 'North District']);
    $schoolA = School::factory()->create(['district_id' => $district->id]);
    $schoolB = School::factory()->create(['district_id' => $district->id]);

    placeSchool($result, $schoolA, 1);
    placeSchool($result, $schoolB, 2);

    $this->actingAs(User::factory()->create())
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('districts', 1)
            ->where('districts.0.district', 'North District')
            ->where('districts.0.gold', 1)
            ->where('districts.0.silver', 1)
            ->where('districts.0.total', 2));
});

test('the tally can be filtered per meet and per sport', function () {
    $resultA = EventResult::factory()->validated()->create();
    $schoolA = School::factory()->create(['name' => 'Meet A School']);
    placeSchool($resultA, $schoolA, 1);

    $resultB = EventResult::factory()->validated()->create();
    $schoolB = School::factory()->create(['name' => 'Meet B School']);
    placeSchool($resultB, $schoolB, 1);

    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get("/tally?meet_id={$resultA->meet_id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 1)
            ->where('schools.0.school', 'Meet A School'));

    $sportId = $resultB->event->sport_id;

    $this->actingAs($viewer)
        ->get("/tally?sport_id={$sportId}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 1)
            ->where('schools.0.school', 'Meet B School'));
});
