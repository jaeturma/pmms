<?php

use App\Enums\MatchStatus;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\Sport;
use Inertia\Testing\AssertableInertia;

/**
 * A scheduled match belonging to the given (or a fresh) published meet.
 */
function publicScoreboardMatch(?Meet $meet = null): EventMatch
{
    $meet ??= Meet::factory()->active()->published()->create();

    return EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'status' => MatchStatus::Scheduled,
    ]);
}

test('guests can view the public scoreboard for a published meet; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();
    $match = publicScoreboardMatch($meet);

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/scoreboard')
            ->where('session', null));

    $hidden = Meet::factory()->active()->create();
    $hiddenMatch = publicScoreboardMatch($hidden);

    $this->get("/meets/{$hidden->id}/matches/{$hiddenMatch->id}/scoreboard")->assertNotFound();
});

test('a match that does not belong to the given meet 404s', function () {
    $meet = Meet::factory()->active()->published()->create();
    $otherMeet = Meet::factory()->active()->published()->create();
    $foreignMatch = publicScoreboardMatch($otherMeet);

    $this->get("/meets/{$meet->id}/matches/{$foreignMatch->id}/scoreboard")->assertNotFound();
});

test('the public scoreboard exposes the live session read-only, including sport-specific state', function () {
    $meet = Meet::factory()->active()->published()->create();

    $sport = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'status' => MatchStatus::Scheduled,
    ]);

    ScoringSession::factory()->create([
        'match_id' => $match->id,
        'side_a_label' => 'Home',
        'side_b_label' => 'Away',
        'score_a' => 12,
        'score_b' => 9,
        'sport_state' => ['fouls_a' => 2, 'fouls_b' => 1],
    ]);

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.side_a_label', 'Home')
            ->where('session.side_b_label', 'Away')
            ->where('session.score_a', 12)
            ->where('session.score_b', 9)
            ->where('session.board_type', 'basketball')
            ->where('session.sport_state', ['fouls_a' => 2, 'fouls_b' => 1])
            ->missing('canManage')
            ->missing('suggestedLabels'));
});

test('the public scoreboard poll endpoint returns the same read-only payload', function () {
    $meet = Meet::factory()->active()->published()->create();
    $match = publicScoreboardMatch($meet);

    ScoringSession::factory()->create(['match_id' => $match->id, 'score_a' => 5, 'score_b' => 3]);

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard/poll")
        ->assertOk()
        ->assertJson(['session' => ['score_a' => 5, 'score_b' => 3]]);

    $hidden = Meet::factory()->active()->create();
    $hiddenMatch = publicScoreboardMatch($hidden);

    $this->get("/meets/{$hidden->id}/matches/{$hiddenMatch->id}/scoreboard/poll")->assertNotFound();
});

test('the public meet page lists only matches with a currently active session', function () {
    $meet = Meet::factory()->active()->published()->create();

    $liveMatch = publicScoreboardMatch($meet);
    ScoringSession::factory()->create([
        'match_id' => $liveMatch->id,
        'side_a_label' => 'Red',
        'side_b_label' => 'Blue',
        'score_a' => 4,
        'score_b' => 2,
    ]);

    $endedMatch = publicScoreboardMatch($meet);
    ScoringSession::factory()->ended()->create(['match_id' => $endedMatch->id]);

    $noSessionMatch = publicScoreboardMatch($meet);

    $otherMeet = Meet::factory()->active()->published()->create();
    $otherMatch = publicScoreboardMatch($otherMeet);
    ScoringSession::factory()->create(['match_id' => $otherMatch->id]);

    $this->get("/meets/{$meet->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('liveMatches', 1)
            ->where('liveMatches.0.match_id', $liveMatch->id)
            ->where('liveMatches.0.side_a_label', 'Red')
            ->where('liveMatches.0.score_a', 4)
            ->where('liveMatches.0.score_b', 2));

    expect($endedMatch->id)->not->toBe($liveMatch->id)
        ->and($noSessionMatch->id)->not->toBe($liveMatch->id);
});
