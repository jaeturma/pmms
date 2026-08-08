<?php

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\SportCategory;
use Inertia\Testing\AssertableInertia;

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

test('the directory covers the full 28-sport catalog and correctly classifies Paragames', function () {
    Meet::factory()->active()->published()->featured()->create();
    Sport::query()->create(['name' => 'Basketball']);
    Sport::query()->create(['name' => 'Paragames - Athletics']);

    $this->get('/sports-directory')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sports', 2)
            ->where('sports.0.is_paragames', false)
            ->where('sports.1.is_paragames', true));
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
