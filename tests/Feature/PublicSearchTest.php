<?php

use App\Models\Announcement;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\Sport;
use Inertia\Testing\AssertableInertia;

test('guests can view the search page for a published meet; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/search")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/search')
            ->where('query', '')
            ->has('schools', 0)
            ->has('sports', 0)
            ->has('announcements', 0)
            ->has('placements', 0));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/search")->assertNotFound();
});

test('an empty query returns no matches without running any search', function () {
    $meet = Meet::factory()->active()->published()->create();

    School::factory()->create(['name' => 'Nabunturan Central School']);

    $this->get("/meets/{$meet->id}/search?q=")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('query', '')
            ->has('schools', 0));
});

test('a no-match query returns empty groups, not an error', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/search?q=zzz-nonexistent")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('query', 'zzz-nonexistent')
            ->has('schools', 0)
            ->has('sports', 0)
            ->has('announcements', 0)
            ->has('placements', 0));
});

test('search finds a school only when it actually participates in this meet', function () {
    $meet = Meet::factory()->active()->published()->create();

    $participating = School::factory()->create(['name' => 'Nabunturan Central School']);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $participating->id]);
    Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $participating->id]);

    $notParticipating = School::factory()->create(['name' => 'Nabunturan East School']);

    $this->get("/meets/{$meet->id}/search?q=Nabunturan")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools', 1)
            ->where('schools.0.name', $participating->name));

    expect($notParticipating->exists)->toBeTrue();
});

test('search finds sports contested in this meet, matched by name', function () {
    $meet = Meet::factory()->active()->published()->create();

    $athletics = Sport::factory()->create(['name' => 'Athletics']);
    $athleticsEvent = Event::factory()->create(['sport_id' => $athletics->id]);
    $meet->events()->attach($athleticsEvent->id);

    Sport::factory()->create(['name' => 'Basketball']);

    $this->get("/meets/{$meet->id}/search?q=athlet")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sports', 1)
            ->where('sports.0.name', 'Athletics'));
});

test('search finds published announcements for this meet only, matched by title', function () {
    $meet = Meet::factory()->active()->published()->create();

    Announcement::factory()->published()->create(['meet_id' => $meet->id, 'title' => 'Opening ceremony schedule']);
    Announcement::factory()->create(['meet_id' => $meet->id, 'title' => 'Opening ceremony draft']);

    $foreign = Meet::factory()->active()->published()->create();
    Announcement::factory()->published()->create(['meet_id' => $foreign->id, 'title' => 'Opening ceremony elsewhere']);

    $this->get("/meets/{$meet->id}/search?q=opening")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements', 1)
            ->where('announcements.0.title', 'Opening ceremony schedule'));
});

test('search finds validated result placements by athlete name or school name', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);

    $school = School::factory()->create(['name' => 'Nabunturan Central School']);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $school->id]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $result->event_id,
    ]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id, 'rank' => 1]);

    $this->get("/meets/{$meet->id}/search?q=dela cruz")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('placements', 1)
            ->where('placements.0.athlete', 'JUAN DELA CRUZ')
            ->where('placements.0.school', 'Nabunturan Central School'));

    $this->get("/meets/{$meet->id}/search?q=nabunturan")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('placements', 1));
});

test('search excludes encoded (unvalidated) results', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = EventResult::factory()->create(['meet_id' => $meet->id]);

    $school = School::factory()->create(['name' => 'Compostela National High School']);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $school->id]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'first_name' => 'Maria',
        'last_name' => 'Santos',
    ]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $result->event_id,
    ]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id, 'rank' => 1]);

    $this->get("/meets/{$meet->id}/search?q=santos")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('placements', 0));
});

test('search excludes another meet\'s data entirely', function () {
    $meet = Meet::factory()->active()->published()->create();
    $foreign = Meet::factory()->active()->published()->create();

    $foreignResult = EventResult::factory()->validated()->create(['meet_id' => $foreign->id]);
    $school = School::factory()->create(['name' => 'Foreign School']);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $foreign->id, 'school_id' => $school->id]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'first_name' => 'Pedro',
        'last_name' => 'Reyes',
    ]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $foreignResult->event_id,
    ]);
    ResultPlacement::factory()->create(['event_result_id' => $foreignResult->id, 'entry_id' => $entry->id, 'rank' => 1]);

    $this->get("/meets/{$meet->id}/search?q=reyes")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('placements', 0)
            ->has('schools', 0));
});

test('placement rows carry only the public-safe fields, no internal or restricted data', function () {
    $meet = Meet::factory()->active()->published()->create();
    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);

    $district = District::factory()->create(['name' => 'Nabunturan']);
    $school = School::factory()->create(['name' => 'Nabunturan Central School', 'district_id' => $district->id]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $school->id]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $result->event_id,
    ]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id, 'rank' => 1]);

    $this->get("/meets/{$meet->id}/search?q=dela cruz")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('placements.0', fn (AssertableInertia $row) => $row
                ->hasAll(['event', 'sport_id', 'rank', 'athlete', 'school', 'delegation', 'mark', 'is_tie'])
                ->missing('birthdate')
                ->missing('lrn')
                ->missing('grade_level')));
});
