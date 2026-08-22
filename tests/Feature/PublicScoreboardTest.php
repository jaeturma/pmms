<?php

use App\Enums\MatchStatus;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
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

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('only logged-in users can view a published scoreboard and unpublished meets stay hidden', function () {
    $meet = Meet::factory()->active()->published()->create();
    $match = publicScoreboardMatch($meet);

    auth()->logout();
    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/scoreboard')
            ->where('session', null));

    $hidden = Meet::factory()->active()->create();
    $hiddenMatch = publicScoreboardMatch($hidden);

    $this->get("/meets/{$hidden->id}/matches/{$hiddenMatch->id}/scoreboard")->assertNotFound();
});

test('the public scoreboard exposes real match metadata: sport, category, round, venue, and date', function () {
    $meet = Meet::factory()->active()->published()->create();
    $sport = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'status' => MatchStatus::Scheduled,
    ]);

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('match.sport', 'Basketball')
            ->has('match.category')
            ->where('match.round_label', $match->round_label));
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

test('the public scoreboard never exposes participant photos, even for a boxing match', function () {
    // Athlete photos are never public (docs/public-portal.md's privacy
    // baseline) — unlike the internal operator console
    // (ScoringSessionTest's "participant photos" test), this page must
    // never receive a `participants` prop at all.
    $meet = Meet::factory()->active()->published()->create();
    $sport = Sport::factory()->create(['name' => 'Boxing']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'status' => MatchStatus::Scheduled,
    ]);

    ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('participants'));
});

test('a completed match that never used live scoring falls back to its event\'s validated official result', function () {
    $meet = Meet::factory()->active()->published()->create();
    $event = Event::factory()->create();
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'status' => MatchStatus::Completed,
    ]);

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    $placement = ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'rank' => 1,
        'mark' => '2:14.3',
    ]);

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session', null)
            ->where('match.status', 'completed')
            ->where('officialResult.placements.0.rank', 1)
            ->where('officialResult.placements.0.athlete', $placement->entry->athlete->fullName())
            ->where('officialResult.placements.0.mark', '2:14.3'));
});

test('a completed match shows no official result when its event has no validated result yet', function () {
    $meet = Meet::factory()->active()->published()->create();
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'status' => MatchStatus::Completed,
    ]);

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session', null)
            ->where('officialResult', null));
});

test('a match with a scoring session never falls back to the event\'s official result, even once ended', function () {
    $meet = Meet::factory()->active()->published()->create();
    $event = Event::factory()->create();
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'status' => MatchStatus::Completed,
    ]);

    ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'score_a' => 54, 'score_b' => 49]);

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id]);

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.score_a', 54)
            ->where('officialResult', null));
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

test('the public scoreboard poll returns a frozen clock after the scorer pauses', function () {
    $meet = Meet::factory()->active()->published()->create();
    $match = publicScoreboardMatch($meet);

    ScoringSession::factory()->paused()->create([
        'match_id' => $match->id,
        'sport_state' => [
            'game_clock_seconds' => 593,
            'game_clock_updated_at' => null,
        ],
    ]);

    $response = $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard/poll")
        ->assertOk()
        ->assertJsonPath('session.clock_running', false)
        ->assertJsonPath('session.sport_state.game_clock_seconds', 593)
        ->assertJsonPath('session.sport_state.game_clock_updated_at', null);

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
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
