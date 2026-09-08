<?php

use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\Sport;
use Inertia\Testing\AssertableInertia;

test('guests see live sport pages and updates without signing in', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id, 'status' => 'scheduled', 'live_scoring_enabled' => true]);
    ScoringSession::factory()->create(['match_id' => $match->id, 'score_a' => 12, 'score_b' => 8]);

    $this->get('/live/basketball')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->component('portal/live-sport')->where('liveNow.session.score_a', 12));
    $this->get('/basketball/poll')->assertOk()->assertJsonPath('liveNow.session.score_a', 12);
    $this->get('/basketball')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
        ->where('liveNow.session.score_a', 12));
    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard/poll")
        ->assertOk()->assertJsonPath('session.score_a', 12);

    $this->get('/scoreboards')->assertRedirect('/login');
    $this->get("/matches/{$match->id}/scoreboard")->assertRedirect('/login');
    $this->post("/matches/{$match->id}/scoreboard/reset")->assertRedirect('/login');
});
