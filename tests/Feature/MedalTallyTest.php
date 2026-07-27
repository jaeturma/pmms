<?php

use App\Enums\AgeDivision;
use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\Sport;
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

test('a municipal delegation\'s medals split correctly across its own schools', function () {
    // The definitive proof for the Division initiative rewrite: one
    // municipal (district-rooted) delegation pooling athletes from two
    // different schools must produce two separate school-level rows with
    // their own correct medal counts, which still roll up into a single
    // municipality row — never one merged "municipality" school row, and
    // never medals mis-attributed to the wrong school.
    $result = EventResult::factory()->validated()->create();

    $district = District::factory()->create(['name' => 'Maco']);
    $schoolA = School::factory()->create(['district_id' => $district->id, 'name' => 'Maco Central School']);
    $schoolB = School::factory()->create(['district_id' => $district->id, 'name' => 'Maco East School']);

    $delegation = Delegation::factory()->approved()->create([
        'meet_id' => $result->meet_id,
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

    $this->actingAs(User::factory()->create())
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 2)
            ->where('schools.0.school', 'Maco Central School')
            ->where('schools.0.gold', 1)
            ->where('schools.0.silver', 0)
            ->where('schools.1.school', 'Maco East School')
            ->where('schools.1.gold', 0)
            ->where('schools.1.silver', 1)
            ->has('districts', 1)
            ->where('districts.0.district', 'Maco')
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

test('district points are weighted gold=3/silver=2/bronze=1 and never change the official rank order', function () {
    // A single gold (3 points) still outranks two silvers (4 points) —
    // proves `points` (WP-08-05) is display-only and the app's documented
    // gold-then-silver-then-bronze rank order (docs/medal-tally.md) is
    // untouched by it.
    $goldDistrict = District::factory()->create(['name' => 'Gold District']);
    $silverDistrict = District::factory()->create(['name' => 'Silver District']);

    $goldSchool = School::factory()->create(['district_id' => $goldDistrict->id]);
    $silverSchool = School::factory()->create(['district_id' => $silverDistrict->id]);

    $first = EventResult::factory()->validated()->create();
    placeSchool($first, $goldSchool, 1);

    $second = EventResult::factory()->validated()->create(['meet_id' => $first->meet_id]);
    placeSchool($second, $silverSchool, 2);
    $third = EventResult::factory()->validated()->create(['meet_id' => $first->meet_id]);
    placeSchool($third, $silverSchool, 2);

    $this->actingAs(User::factory()->create())
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('districts.0.district', 'Gold District')
            ->where('districts.0.position', 1)
            ->where('districts.0.points', 3)
            ->where('districts.1.district', 'Silver District')
            ->where('districts.1.position', 2)
            ->where('districts.1.points', 4)
            ->where('totals.gold', 1)
            ->where('totals.silver', 2)
            ->where('totals.total', 3)
            ->where('topByPoints.0.district', 'Silver District'));
});

test('the tally can be filtered by age division', function () {
    $elementaryEvent = Event::factory()->create(['age_division' => AgeDivision::Elementary]);
    $secondaryEvent = Event::factory()->create(['age_division' => AgeDivision::Secondary]);

    $elementaryResult = EventResult::factory()->validated()->create(['event_id' => $elementaryEvent->id]);
    $elementarySchool = School::factory()->create(['name' => 'Elementary School']);
    placeSchool($elementaryResult, $elementarySchool, 1);

    $secondaryResult = EventResult::factory()->validated()->create(['meet_id' => $elementaryResult->meet_id, 'event_id' => $secondaryEvent->id]);
    $secondarySchool = School::factory()->create(['name' => 'Secondary School']);
    placeSchool($secondaryResult, $secondarySchool, 1);

    $this->actingAs(User::factory()->create())
        ->get('/tally?age_division=elementary')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 1)
            ->where('schools.0.school', 'Elementary School')
            ->where('filters.age_division', 'elementary'));
});

test('an invalid age division filter is ignored rather than erroring', function () {
    $result = EventResult::factory()->validated()->create();
    placeSchool($result, School::factory()->create(), 1);

    $this->actingAs(User::factory()->create())
        ->get('/tally?age_division=not-a-real-division')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 1)
            ->where('filters.age_division', null));
});

test('medals by sport groups placements by their event\'s sport', function () {
    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $chess = Sport::factory()->create(['name' => 'Chess']);

    $basketballEvent = Event::factory()->create(['sport_id' => $basketball->id]);
    $chessEvent = Event::factory()->create(['sport_id' => $chess->id]);

    $basketballResult = EventResult::factory()->validated()->create(['event_id' => $basketballEvent->id]);
    placeSchool($basketballResult, School::factory()->create(), 1);
    placeSchool($basketballResult, School::factory()->create(), 2);

    $chessResult = EventResult::factory()->validated()->create(['meet_id' => $basketballResult->meet_id, 'event_id' => $chessEvent->id]);
    placeSchool($chessResult, School::factory()->create(), 1);

    $this->actingAs(User::factory()->create())
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('bySport', 2)
            ->where('bySport.0.sport', 'Basketball')
            ->where('bySport.0.total', 2)
            ->where('bySport.1.sport', 'Chess')
            ->where('bySport.1.total', 1));
});

test('recent medals only count placements validated within the last 24 hours', function () {
    $recent = EventResult::factory()->validated()->create();
    placeSchool($recent, School::factory()->create(), 1);

    $stale = EventResult::factory()->validated()->create([
        'meet_id' => $recent->meet_id,
        'validated_at' => now()->subDays(3),
    ]);
    placeSchool($stale, School::factory()->create(), 1);

    $this->actingAs(User::factory()->create())
        ->get('/tally')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('totals.gold', 2)
            ->where('recentMedals.gold', 1)
            ->where('recentMedals.total', 1));
});
