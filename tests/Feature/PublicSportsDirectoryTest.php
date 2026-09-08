<?php

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\ResultPlacement;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\SportCategory;
use Inertia\Testing\AssertableInertia;

function directoryAwardMedal(Meet $meet, Event $event): void
{
    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    $placement = ResultPlacement::factory()->create(['event_result_id' => $result->id, 'rank' => 1]);
    $result->medalAwards()->create([
        'result_placement_id' => $placement->id,
        'delegation_id' => $placement->entry->delegation_id,
        'rank' => 1, 'medal_type' => 'gold',
        'physical_quantity' => 1, 'tally_quantity' => 1,
        'result_version' => $result->version ?? 1,
        'snapshotted_by' => $result->encoded_by, 'snapshotted_at' => now(),
    ]);
}

test('every catalog event links from its sport to its own standings and results page', function () {
    $this->withoutVite();
    $meet = Meet::factory()->active()->published()->create();
    $sport = Sport::query()->create(['name' => 'Basketball']);
    $events = Event::factory()->count(2)->create(['sport_id' => $sport->id]);
    $meet->events()->attach($events[0]);

    $this->get('/sports-directory')->assertOk()->assertInertia(fn ($page) => $page->has('sports.0.events', 2));
    $this->get('/basketball')->assertOk()->assertInertia(fn ($page) => $page->has('sport.events', 2));
    foreach ($events as $event) {
        $this->get(route('public.sport-event', ['event' => $event, 'meet_id' => $meet->id]))
            ->assertOk()->assertInertia(fn ($page) => $page->component('portal/sport-event')
            ->where('event.id', $event->id)->has('standings', 0)->has('results', 0));
    }
});

test('guests can browse the sports directory even with no active meet', function () {
    Sport::query()->create(['name' => 'Basketball', 'short_description' => 'Team sport.']);

    $this->get('/sports-directory')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/sports-directory')
            ->where('meet', null)
            ->has('sports', 1)
            ->where('sports.0.slug', 'basketball')
            ->where('sports.0.name', 'Basketball')
            ->where('sports.0.short_description', 'Team sport.')
            ->where('sports.0.is_paragames', false));
});

test('the directory correctly classifies regular sports and all four Paragames sports', function () {
    Meet::factory()->active()->published()->featured()->create();
    Sport::query()->create(['name' => 'Basketball']);
    Sport::query()->create(['name' => 'Para Athletics', 'classification' => 'paragames']);
    Sport::query()->create(['name' => 'Bocce', 'classification' => 'paragames']);
    Sport::query()->create(['name' => 'Goalball', 'classification' => 'paragames']);
    Sport::query()->create(['name' => 'Para Swimming', 'classification' => 'paragames']);

    $this->get('/sports-directory')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sports', 5)
            ->where('sports.0.is_paragames', false)
            ->where('sports.1.is_paragames', true)
            ->where('sports.2.is_paragames', true)
            ->where('sports.3.is_paragames', true)
            ->where('sports.4.is_paragames', true));
});

test('category count combines catalog-wide and this meet-scoped categories', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->create(['name' => 'Basketball']);
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);

    SportCategory::factory()->create(['sport_id' => $sport->id, 'meet_sport_id' => null]);
    SportCategory::factory()->create(['sport_id' => $sport->id, 'meet_sport_id' => $meetSport->id]);

    $this->get('/sports-directory')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sports.0.category_count', 2));
});

test('each card carries medal-awarding progress for the active meet', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->create(['name' => 'Basketball']);
    $events = Event::factory()->count(3)->create(['sport_id' => $sport->id]);
    $meet->events()->attach($events->pluck('id'));

    directoryAwardMedal($meet, $events[0]);
    directoryAwardMedal($meet, $events[1]);

    $this->get('/sports-directory')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sports.0.event_category_count', 3)
            ->where('sports.0.awarded_event_count', 2));
});

test('a sport whose every event is awarded reports parity', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->create(['name' => 'Basketball']);
    $events = Event::factory()->count(2)->create(['sport_id' => $sport->id]);
    $meet->events()->attach($events->pluck('id'));

    $events->each(fn (Event $event) => directoryAwardMedal($meet, $event));

    $this->get('/sports-directory')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sports.0.event_category_count', 2)
            ->where('sports.0.awarded_event_count', 2));
});

test('awarding progress is null when there is no active meet', function () {
    Sport::query()->create(['name' => 'Basketball']);

    $this->get('/sports-directory')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sports.0.event_category_count', null)
            ->where('sports.0.awarded_event_count', null));
});

test('a sport with a currently running scoring session is flagged live', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->get('/sports-directory')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sports.0.is_live', true));
});

test('the directory route does not collide with the authenticated admin sports catalog route', function () {
    // Regression test: `/sports` is already claimed by the authenticated
    // admin catalog route (`sports.index`) — registering the public
    // directory at the same URI made guests silently bounce to `/login`
    // instead of seeing the directory (Laravel's route collection
    // overwrites on identical method+URI). `/sports-directory` exists
    // specifically to avoid this.
    $this->get('/sports-directory')->assertOk();
    $this->get('/sports')->assertRedirect('/login');
});
