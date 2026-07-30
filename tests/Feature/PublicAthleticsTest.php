<?php

use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\Sport;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia;

/**
 * An Event belonging to the real "Athletics" sport (seeded by
 * SportsCatalogSeeder in a real deployment; created fresh here since
 * tests don't run seeders).
 */
function athleticsEvent(): Event
{
    $sport = Sport::query()->firstOrCreate(['name' => 'Athletics']);

    return Event::factory()->create(['sport_id' => $sport->id]);
}

/**
 * A validated placement for the given event/school, via a confirmed
 * entry — same shape `MedalTallyTest`'s `placeSchool()` uses.
 */
function placeAthleticsResult(EventResult $result, School $school, int $rank, ?string $mark = null): ResultPlacement
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
        'mark' => $mark,
    ]);
}

test('guests can view the athletics page for a published meet; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/athletics")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/athletics')
            ->where('meet.name', $meet->name));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/athletics")->assertNotFound();
});

test('only Athletics-sport events appear, never other sports', function () {
    $meet = Meet::factory()->active()->published()->create();
    $venue = Venue::factory()->create();

    $athleticsEvent = athleticsEvent();
    $athleticsEvent->forceFill(['name' => 'Sample Race'])->save();
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $athleticsEvent->id,
        'venue_id' => $venue->id,
        'scheduled_date' => '2026-08-10',
    ]);

    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $basketballEvent = Event::factory()->create(['sport_id' => $basketball->id]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $basketballEvent->id,
        'scheduled_date' => '2026-08-10',
    ]);

    $this->get("/meets/{$meet->id}/athletics?date=2026-08-10")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('slots', 1)
            ->where('slots.0.event', fn (string $event): bool => str_contains($event, 'Sample Race')));
});

test('a scheduled event with no validated result shows upcoming status and no placements', function () {
    $meet = Meet::factory()->active()->published()->create();
    $event = athleticsEvent();

    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'scheduled_date' => '2026-08-10',
    ]);

    $this->get("/meets/{$meet->id}/athletics?date=2026-08-10")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('slots.0.status', 'upcoming')
            ->where('slots.0.top_placements', []));
});

test('a validated result shows completed status with real top placements and marks', function () {
    $meet = Meet::factory()->active()->published()->create();
    $event = athleticsEvent();

    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'scheduled_date' => '2026-08-10',
    ]);

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    $winner = School::factory()->create(['name' => 'Winner School']);
    placeAthleticsResult($result, $winner, 1, '11.58s');

    $this->get("/meets/{$meet->id}/athletics?date=2026-08-10")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('slots.0.status', 'completed')
            ->has('slots.0.top_placements', 1)
            ->where('slots.0.top_placements.0.rank', 1)
            ->where('slots.0.top_placements.0.school', 'Winner School')
            ->where('slots.0.top_placements.0.mark', '11.58s')
            ->has('slots.0.official_as_of'));
});

test('medal totals reflect only Athletics-sport medals, not other sports', function () {
    $meet = Meet::factory()->active()->published()->create();

    $athleticsResult = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => athleticsEvent()->id]);
    placeAthleticsResult($athleticsResult, School::factory()->create(), 1);

    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $basketballEvent = Event::factory()->create(['sport_id' => $basketball->id]);
    $basketballResult = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $basketballEvent->id]);
    placeAthleticsResult($basketballResult, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/athletics")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('medalTotals.gold', 1)
            ->where('medalTotals.total', 1));
});

test('an unvalidated (encoded) result never appears as a completed placement', function () {
    $meet = Meet::factory()->active()->published()->create();
    $event = athleticsEvent();

    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'scheduled_date' => '2026-08-10',
    ]);

    $encoded = EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id, 'status' => ResultStatus::Encoded]);
    placeAthleticsResult($encoded, School::factory()->create(), 1);

    $this->get("/meets/{$meet->id}/athletics?date=2026-08-10")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('slots.0.status', 'upcoming')
            ->where('slots.0.top_placements', []));
});
