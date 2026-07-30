<?php

use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia;

test('guests can view a published meet page; unpublished meets 404', function () {
    $published = Meet::factory()->active()->published()->create();
    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$published->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/schedule')
            ->where('meet.name', $published->name));

    $this->get("/meets/{$hidden->id}")->assertNotFound();
});

test('the schedule shows the selected day grouped by venue in time order', function () {
    $meet = Meet::factory()->active()->published()->create();

    $alpha = Venue::factory()->create(['name' => 'Alpha Gym']);
    $beta = Venue::factory()->create(['name' => 'Beta Field']);

    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'venue_id' => $alpha->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '10:00:00',
        'ends_at' => '12:00:00',
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'venue_id' => $alpha->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00:00',
        'ends_at' => '09:00:00',
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'venue_id' => $beta->id,
        'scheduled_date' => '2026-08-10',
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'scheduled_date' => '2026-08-11',
    ]);
    EventSchedule::factory()->create(['scheduled_date' => '2026-08-10']);

    $this->get("/meets/{$meet->id}?date=2026-08-10")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('days', 2)
            ->where('selectedDay', '2026-08-10')
            ->has('venuesForDay', 2)
            ->where('venuesForDay.0.venue', 'Alpha Gym')
            ->has('venuesForDay.0.slots', 2)
            ->where('venuesForDay.0.slots.0.starts_at', '08:00')
            ->where('venuesForDay.1.venue', 'Beta Field'));
});

test('the day selector defaults to the first day when today has no slots', function () {
    $meet = Meet::factory()->active()->published()->create();

    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'scheduled_date' => '2026-08-11',
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'scheduled_date' => '2026-08-10',
    ]);

    $this->get("/meets/{$meet->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('selectedDay', '2026-08-10'));
});

test('the day selector defaults to today when today has slots', function () {
    $meet = Meet::factory()->active()->published()->create();

    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'scheduled_date' => '2026-08-10',
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'scheduled_date' => today()->toDateString(),
    ]);

    $this->get("/meets/{$meet->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('selectedDay', today()->toDateString()));
});

test('the venue guide lists names and addresses but never internal notes', function () {
    $meet = Meet::factory()->active()->published()->create();

    $venue = Venue::factory()->create([
        'name' => 'Capitol Oval',
        'address' => 'Capitol Compound',
        'notes' => 'Gate code 4321 — internal only',
    ]);

    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'venue_id' => $venue->id,
    ]);

    $this->get("/meets/{$meet->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('venueGuide', 1)
            ->where('venueGuide.0.name', 'Capitol Oval')
            ->where('venueGuide.0.address', 'Capitol Compound')
            ->missing('venueGuide.0.notes'));
});

test('a published meet without slots shows the empty schedule state', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('days', 0)
            ->where('selectedDay', null)
            ->has('venuesForDay', 0)
            ->has('venueGuide', 0));
});
