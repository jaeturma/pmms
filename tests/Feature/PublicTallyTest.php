<?php

use App\Enums\AgeDivision;
use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\SchoolDistrict;
use App\Models\Sport;
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

/**
 * A validated (official) result whose event is fully controlled — age
 * division and sport — so a test can target one tally category exactly.
 */
function tallyResult(Meet $meet, array $eventAttributes = []): EventResult
{
    $event = Event::factory()->create($eventAttributes + [
        'age_division' => AgeDivision::Secondary,
    ]);

    return EventResult::factory()->validated()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
    ]);
}

function paragamesSport(string $name = 'Para Athletics'): Sport
{
    return Sport::factory()->create(['name' => $name, 'classification' => 'paragames']);
}

test('guests can view the public tally; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/tally")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/tally')
            ->has('categories.overall.districts', 0)
            ->has('categories.overall.schools', 0)
            ->where('categories.overall.hasResults', false)
            ->where('categories.paragames.hasResults', false));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/tally")->assertNotFound();
});

test('the public tally provides all four official categories in one payload', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('categories.overall', fn (AssertableInertia $c) => $c
                ->has('districts')->has('schools')->has('totals')
                ->has('bySport')->has('recentMedals')->has('topMedalists')
                ->has('topByPoints')
                ->where('hasResults', false))
            ->has('categories.elementary')
            ->has('categories.secondary')
            ->has('categories.paragames')
            ->has('sportOptions')
            ->has('generatedAt')
            ->where('filters.sport_id', null));
});

test('validated but unaccepted results do not contribute to the tally', function () {
    $meet = Meet::factory()->active()->published()->create();
    $event = Event::factory()->create(['age_division' => AgeDivision::Secondary]);
    $unofficial = EventResult::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'status' => ResultStatus::Validated,
        'validated_at' => now(),
    ]);

    publicTallyPlacement($unofficial, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.overall.totals.total', 0));
});

test('an Elementary medal lands in Elementary and Overall, but not Secondary or Paragames', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = tallyResult($meet, ['age_division' => AgeDivision::Elementary]);
    publicTallyPlacement($result, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.elementary.totals.gold', 1)
            ->where('categories.overall.totals.gold', 1)
            ->where('categories.secondary.totals.gold', 0)
            ->where('categories.paragames.totals.gold', 0)
            ->where('categories.elementary.hasResults', true)
            ->where('categories.paragames.hasResults', false));
});

test('a Secondary medal lands in Secondary and Overall, but not Elementary or Paragames', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
    publicTallyPlacement($result, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.secondary.totals.gold', 1)
            ->where('categories.overall.totals.gold', 1)
            ->where('categories.elementary.totals.gold', 0)
            ->where('categories.paragames.totals.gold', 0));
});

test('a Paragames medal lands in Paragames only and never touches Overall, Elementary or Secondary', function () {
    $meet = Meet::factory()->active()->published()->create();

    $result = tallyResult($meet, [
        'sport_id' => paragamesSport()->id,
        'age_division' => AgeDivision::Elementary,
    ]);
    publicTallyPlacement($result, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.paragames.totals.gold', 1)
            ->where('categories.paragames.hasResults', true)
            ->where('categories.overall.totals.gold', 0)
            ->where('categories.overall.hasResults', false)
            ->where('categories.elementary.totals.gold', 0)
            ->where('categories.secondary.totals.gold', 0));
});

test('Overall equals Elementary plus Secondary and excludes Paragames', function () {
    $meet = Meet::factory()->active()->published()->create();

    // Elementary: 5 gold
    foreach (range(1, 5) as $i) {
        $r = tallyResult($meet, ['age_division' => AgeDivision::Elementary]);
        publicTallyPlacement($r, School::factory()->create(), 1);
    }

    // Secondary: 7 gold
    foreach (range(1, 7) as $i) {
        $r = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
        publicTallyPlacement($r, School::factory()->create(), 1);
    }

    // Paragames: 3 gold — must not count toward Overall
    foreach (range(1, 3) as $i) {
        $r = tallyResult($meet, [
            'sport_id' => paragamesSport("Para Sport {$i}")->id,
            'age_division' => AgeDivision::Secondary,
        ]);
        publicTallyPlacement($r, School::factory()->create(), 1);
    }

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.elementary.totals.gold', 5)
            ->where('categories.secondary.totals.gold', 7)
            ->where('categories.overall.totals.gold', 12)
            ->where('categories.paragames.totals.gold', 3));
});

test('Kickboxing contributes to no tally category', function () {
    $meet = Meet::factory()->active()->published()->create();

    $kickboxing = Sport::factory()->create(['name' => 'Kickboxing']);
    $combined = Sport::factory()->create(['name' => 'Weightlifting / Kickboxing']);

    foreach ([$kickboxing, $combined] as $sport) {
        $r = tallyResult($meet, ['sport_id' => $sport->id, 'age_division' => AgeDivision::Secondary]);
        publicTallyPlacement($r, School::factory()->create(), 1);
    }

    // A clean Secondary medal, to prove the tally is otherwise working.
    $clean = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
    publicTallyPlacement($clean, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.overall.totals.gold', 1)
            ->where('categories.secondary.totals.gold', 1)
            ->where('categories.elementary.totals.gold', 0)
            ->where('categories.paragames.totals.gold', 0));
});

test('flexible and repeated medal rows still count correctly per category', function () {
    $meet = Meet::factory()->active()->published()->create();

    // One Secondary event awarding Gold, Silver, Bronze, Bronze — and the
    // same school taking two of the golds.
    $result = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
    $compostela = School::factory()->create(['name' => 'Compostela NHS']);

    publicTallyPlacement($result, $compostela, 1);
    publicTallyPlacement($result, $compostela, 1);
    publicTallyPlacement($result, School::factory()->create(), 2);
    publicTallyPlacement($result, School::factory()->create(), 3);
    publicTallyPlacement($result, School::factory()->create(), 3);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.secondary.totals.gold', 2)
            ->where('categories.secondary.totals.silver', 1)
            ->where('categories.secondary.totals.bronze', 2)
            ->where('categories.secondary.totals.total', 5)
            ->where('categories.secondary.schools.0.school', 'Compostela NHS')
            ->where('categories.secondary.schools.0.gold', 2)
            ->where('categories.overall.totals.total', 5));
});

test('reopening an accepted result reconciles every affected category', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = tallyResult($meet, ['age_division' => AgeDivision::Elementary]);
    publicTallyPlacement($result, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.elementary.totals.gold', 1)
            ->where('categories.overall.totals.gold', 1));

    $this->actingAs(\App\Models\User::factory()->admin()->create())
        ->post("/results/{$result->id}/reopen", ['reason' => 'Wrong athlete placed first.'])
        ->assertSessionHasNoErrors();

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.elementary.totals.gold', 0)
            ->where('categories.overall.totals.gold', 0)
            ->where('categories.elementary.hasResults', false));
});

test('a newly validated medal shows up on the next poll of the same page', function () {
    // The client polls with `router.reload({ only: ['categories', 'generatedAt'] })` —
    // no navigation, so this is the same endpoint returning fresh category data.
    $meet = Meet::factory()->active()->published()->create();
    $first = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
    publicTallyPlacement($first, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.secondary.totals.gold', 1));

    $second = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
    publicTallyPlacement($second, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.secondary.totals.gold', 2)
            ->where('categories.overall.totals.gold', 2));
});

test('the public tally counts validated results only, in medal order, sharing ties', function () {
    $meet = Meet::factory()->active()->published()->create();

    $validated = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
    $goldSchool = School::factory()->create(['name' => 'Gold School']);
    $tieSchool = School::factory()->create(['name' => 'Tie School']);

    publicTallyPlacement($validated, $goldSchool, 1, tie: true);
    publicTallyPlacement($validated, $tieSchool, 1, tie: true);
    publicTallyPlacement($validated, $tieSchool, 3);

    $encoded = EventResult::factory()->create(['meet_id' => $meet->id]);
    publicTallyPlacement($encoded, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('categories.overall.schools', 2)
            ->where('categories.overall.schools.0.school', 'Tie School')
            ->where('categories.overall.schools.0.gold', 1)
            ->where('categories.overall.schools.0.bronze', 1)
            ->where('categories.overall.schools.1.school', 'Gold School')
            ->has('categories.overall.districts', 2));
});

test('the public tally excludes other meets', function () {
    $meet = Meet::factory()->active()->published()->create();

    $foreign = EventResult::factory()->validated()->create();
    publicTallyPlacement($foreign, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.overall.hasResults', false)
            ->has('categories.overall.schools', 0));
});

test('the public tally splits a municipal delegation\'s medals across its own schools', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);

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
            ->has('categories.overall.schools', 2)
            ->where('categories.overall.schools.0.school', 'Nabunturan Central School')
            ->has('categories.overall.districts', 1)
            ->where('categories.overall.districts.0.district', 'Nabunturan')
            ->where('categories.overall.districts.0.total', 2));
});

test('school standings show the school district only when its municipality has more than one', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);

    $laak = District::factory()->create(['name' => 'Laak']);
    $laakNorth = SchoolDistrict::factory()->create(['district_id' => $laak->id, 'name' => 'Laak North']);
    SchoolDistrict::factory()->create(['district_id' => $laak->id, 'name' => 'Laak South']);
    $laakSchool = School::factory()->create([
        'district_id' => $laak->id,
        'school_district_id' => $laakNorth->id,
        'name' => 'Laak North School',
    ]);

    $mawab = District::factory()->create(['name' => 'Mawab']);
    $mawabSchool = School::factory()->create([
        'district_id' => $mawab->id,
        'school_district_id' => null,
        'name' => 'Mawab Central School',
    ]);

    publicTallyPlacement($result, $laakSchool, 1);
    publicTallyPlacement($result, $mawabSchool, 2);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.overall.schools.0.school', 'Laak North School')
            ->where('categories.overall.schools.0.district', 'Laak North')
            ->where('categories.overall.schools.1.district', 'Mawab'));
});

test('the public tally can be filtered by sport, and the filter narrows every category', function () {
    $meet = Meet::factory()->active()->published()->create();

    $resultA = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
    publicTallyPlacement($resultA, School::factory()->create(['name' => 'Sport A School']), 1);

    $resultB = tallyResult($meet, ['age_division' => AgeDivision::Secondary]);
    publicTallyPlacement($resultB, School::factory()->create(['name' => 'Sport B School']), 1);

    $sportA = $resultA->event->sport_id;

    $this->get("/meets/{$meet->id}/tally?sport_id={$sportA}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.sport_id', $sportA)
            ->has('categories.overall.schools', 1)
            ->where('categories.overall.schools.0.school', 'Sport A School')
            ->where('categories.secondary.totals.gold', 1));
});

test('the tally exposes a generated-at timestamp and the official flag', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('generatedAt')
            ->where('medalTallyOfficial', false));
});

test('top medalists rank individual athletes by gold, then silver, then bronze', function () {
    $meet = Meet::factory()->active()->published()->create();

    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $school = School::factory()->create(['name' => 'Champion School']);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $school->id]);
    $star = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'first_name' => 'Ana',
        'last_name' => 'Cruz',
    ]);

    $event = Event::factory()->create(['sport_id' => $basketball->id, 'age_division' => AgeDivision::Secondary]);
    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $star->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id, 'rank' => 1]);

    $this->get("/meets/{$meet->id}/tally")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.overall.topMedalists.0.athlete', 'ANA CRUZ')
            ->where('categories.overall.topMedalists.0.gold', 1)
            ->where('categories.secondary.topMedalists.0.athlete', 'ANA CRUZ'));
});
