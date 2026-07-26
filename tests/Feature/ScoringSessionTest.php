<?php

use App\Enums\MatchStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\ResultPlacement;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * Broadcasting runs on the "null" driver in tests (phpunit.xml), so every
 * test here already proves the feature works without Reverb running.
 */
function confirmedEntryForScoringSession(EventMatch $match): Entry
{
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    return Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $match->event_id,
    ]);
}

/**
 * A scheduled match whose sport resolves to the basketball scoreboard
 * (App\Enums\ScoreboardType — WP-07-04).
 */
function basketballMatch(): EventMatch
{
    $sport = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * A scheduled match whose sport resolves to the boxing scoreboard
 * (App\Enums\ScoreboardType — WP-07-05).
 */
function boxingMatch(): EventMatch
{
    $sport = Sport::factory()->create(['name' => 'Boxing']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * A scheduled match whose sport resolves to the softball/baseball
 * scoreboard (App\Enums\ScoreboardType — WP-07-06).
 */
function softballMatch(string $sportName = 'Softball'): EventMatch
{
    $sport = Sport::factory()->create(['name' => $sportName]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

test('guests are redirected from the scoring session endpoint', function () {
    $match = EventMatch::factory()->create();

    $this->get("/matches/{$match->id}/scoring-session")->assertRedirect('/login');
});

test('viewers are forbidden from viewing a scoring session', function () {
    $match = EventMatch::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get("/matches/{$match->id}/scoring-session")
        ->assertForbidden();
});

test('a delegation officer can view their own delegation\'s match but not another\'s', function () {
    $match = EventMatch::factory()->create();
    $entry = confirmedEntryForScoringSession($match);
    $match->entries()->attach($entry);

    $otherMatch = EventMatch::factory()->create();

    $officer = User::factory()->delegationOfficer()->create();
    $entry->delegation->officers()->attach($officer);

    $this->actingAs($officer)
        ->get("/matches/{$match->id}/scoring-session")
        ->assertOk()
        ->assertJson(['session' => null]);

    $this->actingAs($officer)
        ->get("/matches/{$otherMatch->id}/scoring-session")
        ->assertForbidden();
});

test('non-managers cannot start, score, or end a scoring session', function (User $user) {
    $match = EventMatch::factory()->create();

    $this->actingAs($user)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertForbidden();

    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/end", [])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a session can only be started for a scheduled match', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Completed]);

    $this->actingAs(User::factory()->admin()->create())
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertSessionHasErrors('match_id');

    expect(ScoringSession::query()->count())->toBe(0);
});

test('only one active session per match is allowed', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertSessionHasErrors('match_id');

    expect(ScoringSession::query()->where('match_id', $match->id)->count())->toBe(1);
});

test('a full session lifecycle works entirely through the polling read endpoint', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 2])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", [
            'type' => 'correction', 'side' => 'a', 'delta' => -1, 'reason' => 'Miscounted a point.',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/period", ['period_label' => 'Q2', 'status_note' => 'Timeout'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/pause", [])->assertSessionHasNoErrors();
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/resume", [])->assertSessionHasNoErrors();
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/end", [])->assertSessionHasNoErrors();

    $response = $this->actingAs($admin)->get("/matches/{$match->id}/scoring-session");

    $response->assertOk()->assertJson([
        'session' => [
            'status' => 'ended',
            'side_a_label' => 'Home',
            'side_b_label' => 'Away',
            'score_a' => 1,
            'score_b' => 0,
            'period_label' => 'Q2',
            'status_note' => 'Timeout',
        ],
    ]);

    expect(ScoreEvent::query()->where('scoring_session_id', $session->id)->count())->toBe(6)
        ->and(ScoreEvent::query()->where('scoring_session_id', $session->id)->where('type', 'correction')->first()->payload['reason'])
        ->toBe('Miscounted a point.')
        ->and(AuditLog::query()->whereIn('action', [
            'scoring.started', 'scoring.scored', 'scoring.corrected', 'scoring.period_changed',
            'scoring.paused', 'scoring.resumed', 'scoring.ended',
        ])->count())->toBe(7);
});

test('a correction requires a reason', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'correction', 'side' => 'a', 'delta' => -1])
        ->assertSessionHasErrors('reason');
});

test('score never goes below zero', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'score_a' => 1]);

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'correction', 'side' => 'a', 'delta' => -5, 'reason' => 'Fix'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->score_a)->toBe(0);
});

test('ending a scoring session never creates or touches an EventResult', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/scoring-sessions/{$session->id}/end", [])
        ->assertSessionHasNoErrors();

    expect(EventResult::query()->count())->toBe(0)
        ->and(ResultPlacement::query()->count())->toBe(0)
        ->and($match->fresh()->status)->toBe(MatchStatus::Scheduled);
});

test('a session cannot be mutated once it has ended', function () {
    $session = ScoringSession::factory()->ended()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasErrors('status');
});

test('guests are redirected from the scoreboard page', function () {
    $match = EventMatch::factory()->create();

    $this->get("/matches/{$match->id}/scoreboard")->assertRedirect('/login');
});

test('viewers are forbidden from the scoreboard page', function () {
    $match = EventMatch::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get("/matches/{$match->id}/scoreboard")
        ->assertForbidden();
});

test('a delegation officer can open the scoreboard for their own match but not another\'s', function () {
    $match = EventMatch::factory()->create();
    $entry = confirmedEntryForScoringSession($match);
    $match->entries()->attach($entry);

    $otherMatch = EventMatch::factory()->create();

    $officer = User::factory()->delegationOfficer()->create();
    $entry->delegation->officers()->attach($officer);

    $this->actingAs($officer)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('scoring/show')
            ->where('match.id', $match->id)
            ->where('canManage', false)
            ->where('session', null));

    $this->actingAs($officer)
        ->get("/matches/{$otherMatch->id}/scoreboard")
        ->assertForbidden();
});

test('the scoreboard page suggests side labels only when the match has exactly two entries', function () {
    $match = EventMatch::factory()->create();
    $entryA = confirmedEntryForScoringSession($match);
    $entryB = confirmedEntryForScoringSession($match);
    $match->entries()->attach([$entryA->id, $entryB->id]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('scoring/show')
            ->where('canManage', true)
            ->has('suggestedLabels', 2)
            ->where('suggestedLabels.0', $entryA->athlete->school->name)
            ->where('suggestedLabels.1', $entryB->athlete->school->name));

    $entryC = confirmedEntryForScoringSession($match);
    $match->entries()->attach($entryC->id);

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('suggestedLabels.0', null)
            ->where('suggestedLabels.1', null));
});

test('the scoreboard page reflects a score change made through the operator console', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'b', 'delta' => 3])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.score_a', 0)
            ->where('session.score_b', 3)
            ->where('session.status', 'in_progress'));
});

// WP-07-04: Basketball live scoreboard

test('starting a session for a basketball match initializes team fouls and the board type', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'basketball',
        'sport_state' => ['fouls_a' => 0, 'fouls_b' => 0],
    ]);
});

test('a non-basketball match uses the generic board type with no sport state', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'generic',
        'sport_state' => null,
    ]);
});

test('recording a team foul increments the correct side and reset zeroes both', function () {
    $match = basketballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['fouls_a' => 0, 'fouls_b' => 0],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'add', 'side' => 'a'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe(['fouls_a' => 1, 'fouls_b' => 0]);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'add', 'side' => 'a'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe(['fouls_a' => 2, 'fouls_b' => 0]);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'reset'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe(['fouls_a' => 0, 'fouls_b' => 0])
        ->and(AuditLog::query()->whereIn('action', ['scoring.foul_recorded', 'scoring.fouls_reset'])->count())->toBe(3);
});

test('the foul endpoint is rejected for a non-basketball scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'add', 'side' => 'a'])
        ->assertStatus(422);
});

test('non-managers cannot record a team foul', function (User $user) {
    $match = basketballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['fouls_a' => 0, 'fouls_b' => 0],
    ]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'add', 'side' => 'a'])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a team foul cannot be recorded once the session has ended', function () {
    $match = basketballMatch();
    $session = ScoringSession::factory()->ended()->create([
        'match_id' => $match->id,
        'sport_state' => ['fouls_a' => 0, 'fouls_b' => 0],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'add', 'side' => 'a'])
        ->assertSessionHasErrors('status');
});

test('the scoreboard page exposes board type and sport state for a basketball match', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.board_type', 'basketball')
            ->where('session.sport_state', ['fouls_a' => 0, 'fouls_b' => 0]));
});

// WP-07-05: Boxing live scoreboard

test('starting a session for a boxing match initializes an empty round history and the board type', function () {
    $match = boxingMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Red', 'side_b_label' => 'Blue'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'boxing',
        'sport_state' => ['rounds' => []],
    ]);
});

test('recording round scores appends to the round history and sums into the running total', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['rounds' => []],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertSessionHasNoErrors();

    $session->refresh();

    expect($session->sport_state)->toBe(['rounds' => [['round' => 1, 'score_a' => 10, 'score_b' => 9]]])
        ->and($session->score_a)->toBe(10)
        ->and($session->score_b)->toBe(9);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 9, 'score_b' => 10])
        ->assertSessionHasNoErrors();

    $session->refresh();

    expect($session->sport_state)->toBe(['rounds' => [
        ['round' => 1, 'score_a' => 10, 'score_b' => 9],
        ['round' => 2, 'score_a' => 9, 'score_b' => 10],
    ]])
        ->and($session->score_a)->toBe(19)
        ->and($session->score_b)->toBe(19)
        ->and(AuditLog::query()->where('action', 'scoring.round_scored')->count())->toBe(2);
});

test('a round score must be between 0 and 10', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => ['rounds' => []]]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 11, 'score_b' => 9])
        ->assertSessionHasErrors('score_a');
});

test('the round endpoint is rejected for a non-boxing scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertStatus(422);
});

test('non-managers cannot record a round score', function (User $user) {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => ['rounds' => []]]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a round score cannot be recorded once the session has ended', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => ['rounds' => []]]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertSessionHasErrors('status');
});

test('the scoreboard page exposes board type and round history for a boxing match', function () {
    $match = boxingMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Red', 'side_b_label' => 'Blue'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.board_type', 'boxing')
            ->where('session.sport_state', ['rounds' => [['round' => 1, 'score_a' => 10, 'score_b' => 9]]]));
});

// WP-07-06: Softball/Baseball live scoreboard

test('starting a session for a softball or baseball match initializes the count/inning state and the board type', function (string $sportName) {
    $match = softballMatch($sportName);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'softball_baseball',
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => []],
    ]);
})->with(['Softball', 'Baseball']);

test('recording a run appends to the current inning and sums into the running total', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => []],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/inning-run", ['side' => 'a', 'runs' => 2])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/inning-run", ['side' => 'b', 'runs' => 1])
        ->assertSessionHasNoErrors();

    $session->refresh();

    expect($session->sport_state['innings'])->toBe([['inning' => 1, 'runs_a' => 2, 'runs_b' => 1]])
        ->and($session->score_a)->toBe(2)
        ->and($session->score_b)->toBe(1)
        ->and(AuditLog::query()->where('action', 'scoring.run_scored')->count())->toBe(2);
});

test('a run recorded in a later inning starts its own row in the breakdown', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => []],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/inning-run", ['side' => 'a', 'runs' => 1])
        ->assertSessionHasNoErrors();

    $session->forceFill(['sport_state' => [...$session->fresh()->sport_state, 'inning' => 2]])->save();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/inning-run", ['side' => 'b', 'runs' => 3])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state['innings'])->toBe([
        ['inning' => 1, 'runs_a' => 1, 'runs_b' => 0],
        ['inning' => 2, 'runs_a' => 0, 'runs_b' => 3],
    ]);
});

test('three outs flips the half inning and resets the count', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 2, 'strikes' => 1, 'innings' => []],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/count", ['action' => 'out'])->assertSessionHasNoErrors();
    expect($session->fresh()->sport_state)->toMatchArray(['outs' => 1, 'half' => 'top', 'inning' => 1]);

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/count", ['action' => 'out'])->assertSessionHasNoErrors();
    expect($session->fresh()->sport_state)->toMatchArray(['outs' => 2, 'half' => 'top', 'inning' => 1]);

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/count", ['action' => 'out'])->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe([
        'inning' => 1, 'half' => 'bottom', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => [],
    ]);
});

test('the third out of the bottom half advances the inning number', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'bottom', 'outs' => 2, 'balls' => 0, 'strikes' => 0, 'innings' => []],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'out'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe([
        'inning' => 2, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => [],
    ]);
});

test('a third strike is itself an out', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 1, 'strikes' => 2, 'innings' => []],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'strike'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe([
        'inning' => 1, 'half' => 'top', 'outs' => 1, 'balls' => 0, 'strikes' => 0, 'innings' => [],
    ]);
});

test('a fourth ball resets the count without recording an out', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 1, 'balls' => 3, 'strikes' => 2, 'innings' => []],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'ball'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe([
        'inning' => 1, 'half' => 'top', 'outs' => 1, 'balls' => 0, 'strikes' => 0, 'innings' => [],
    ]);
});

test('reset_count only zeroes balls and strikes', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 2, 'half' => 'bottom', 'outs' => 1, 'balls' => 2, 'strikes' => 1, 'innings' => []],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'reset_count'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe([
        'inning' => 2, 'half' => 'bottom', 'outs' => 1, 'balls' => 0, 'strikes' => 0, 'innings' => [],
    ]);
});

test('the count and inning-run endpoints are rejected for a non-softball-baseball scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'out'])
        ->assertStatus(422);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/inning-run", ['side' => 'a', 'runs' => 1])
        ->assertStatus(422);
});

test('non-managers cannot advance the count or record a run', function (User $user) {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => []],
    ]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'out'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/inning-run", ['side' => 'a', 'runs' => 1])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('the count and inning-run endpoints cannot be used once the session has ended', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->ended()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => []],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'out'])
        ->assertSessionHasErrors('status');

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/inning-run", ['side' => 'a', 'runs' => 1])
        ->assertSessionHasErrors('status');
});

test('the scoreboard page exposes board type and inning state for a softball match', function () {
    $match = softballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.board_type', 'softball_baseball')
            ->where('session.sport_state.inning', 1)
            ->where('session.sport_state.half', 'top'));
});

// WP-07-07: Generic match scoreboard (manual board-type override)

test('a basketball match session can be forced to the generic board at start', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Home', 'side_b_label' => 'Away', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

test('a boxing match session can be forced to the generic board at start', function () {
    $match = boxingMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Red', 'side_b_label' => 'Blue', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

test('a softball match session can be forced to the generic board at start', function () {
    $match = softballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Home', 'side_b_label' => 'Away', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

test('without an override a basketball match session still gets the basketball board', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'basketball',
        'sport_state' => ['fouls_a' => 0, 'fouls_b' => 0],
    ]);
});

test('the board type override only accepts generic, not another sport board', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);

    $this->actingAs(User::factory()->admin()->create())
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'A', 'side_b_label' => 'B', 'board_type' => 'basketball',
        ])
        ->assertSessionHasErrors('board_type');
});

test('the scoreboard page exposes the auto-derived suggested board type before a session starts', function () {
    $match = basketballMatch();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('suggestedBoardType', 'basketball'));

    $genericMatch = EventMatch::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/matches/{$genericMatch->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('suggestedBoardType', 'generic'));
});
