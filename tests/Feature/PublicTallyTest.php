<?php

use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\School;
use Inertia\Testing\AssertableInertia;

/**
 * Place a school at the given rank in a result via a confirmed entry.
 */
function publicTallyPlacement(EventResult $result, School $school, int $rank, bool $tie = false): ResultPlacement
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

test('guests can view the public tally; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/tally")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/tally')
            ->has('schools', 0)
            ->has('districts', 0));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/tally")->assertNotFound();
});

test('the public tally counts validated results only, in medal order, sharing ties', function () {
    $meet = Meet::factory()->active()->published()->create();

    $validated = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    $goldSchool = School::factory()->create(['name' => 'Gold School']);
    $tieSchool = School::factory()->create(['name' => 'Tie School']);

    publicTallyPlacement($validated, $goldSchool, 1, tie: true);
    publicTallyPlacement($validated, $tieSchool, 1, tie: true);
    publicTallyPlacement($validated, $tieSchool, 3);

    $encoded = EventResult::factory()->create(['meet_id' => $meet->id]);
    publicTallyPlacement($encoded, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 2)
            ->where('schools.0.school', 'Tie School')
            ->where('schools.0.gold', 1)
            ->where('schools.0.bronze', 1)
            ->where('schools.0.total', 2)
            ->where('schools.1.school', 'Gold School')
            ->where('schools.1.gold', 1)
            ->where('schools.1.total', 1)
            ->has('districts', 2));
});

test('the public tally excludes other meets', function () {
    $meet = Meet::factory()->active()->published()->create();

    $foreign = EventResult::factory()->validated()->create();
    publicTallyPlacement($foreign, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 0));
});

test('the public tally splits a municipal delegation\'s medals across its own schools', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);

    $district = District::factory()->create(['name' => 'Nabunturan']);
    $schoolA = School::factory()->create(['district_id' => $district->id, 'name' => 'Nabunturan Central School']);
    $schoolB = School::factory()->create(['district_id' => $district->id, 'name' => 'Nabunturan East School']);

    $delegation = Delegation::factory()->approved()->create([
        'meet_id' => $meet->id,
        'school_id' => null,
        'district_id' => $district->id,
    ]);

    $athleteA = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $schoolA->id]);
    $entryA = Entry::factory()->confirmed()->create([
        'athlete_id' => $athleteA->id,
        'delegation_id' => $delegation->id,
        'event_id' => $result->event_id,
    ]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entryA->id, 'rank' => 1]);

    $athleteB = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $schoolB->id]);
    $entryB = Entry::factory()->confirmed()->create([
        'athlete_id' => $athleteB->id,
        'delegation_id' => $delegation->id,
        'event_id' => $result->event_id,
    ]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entryB->id, 'rank' => 2]);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 2)
            ->where('schools.0.school', 'Nabunturan Central School')
            ->where('schools.1.school', 'Nabunturan East School')
            ->has('districts', 1)
            ->where('districts.0.district', 'Nabunturan')
            ->where('districts.0.total', 2));
});

test('the public tally can be filtered by sport', function () {
    $meet = Meet::factory()->active()->published()->create();

    $resultA = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    $schoolA = School::factory()->create(['name' => 'Sport A School']);
    publicTallyPlacement($resultA, $schoolA, 1);

    $resultB = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    publicTallyPlacement($resultB, School::factory()->create(['name' => 'Sport B School']), 1);

    $sportA = $resultA->event->sport_id;

    $this->get("/meets/{$meet->id}/tally?sport_id={$sportA}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 1)
            ->where('schools.0.school', 'Sport A School')
            ->has('sportOptions', 2));
});
