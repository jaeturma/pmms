<?php

use App\Enums\MatchStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\ScoringSessionStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\FileUpload;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\ResultPlacement;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Carbon;
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
 * @return array<string, mixed>
 */
function boxingInitialSportState(): array
{
    return [
        'rounds' => [],
        'round_duration_seconds' => 120, 'rest_duration_seconds' => 60, 'total_rounds' => 3,
        'clock_seconds' => 120, 'clock_updated_at' => null, 'clock_phase' => 'round',
        'bell_sounded_at' => null,
    ];
}

/**
 * A scheduled match whose sport resolves to the combat-rounds scoreboard
 * (taekwondo/wushu/pencak silat — shares boxing's exact round-clock
 * shape).
 */
function combatRoundsMatch(string $sportName = 'Taekwondo'): EventMatch
{
    $sport = Sport::factory()->create(['name' => $sportName]);
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

/**
 * A scheduled match whose sport resolves to the volleyball/sepak takraw
 * scoreboard.
 */
function rallySetsMatch(string $sportName = 'Volleyball'): EventMatch
{
    $sport = Sport::factory()->create(['name' => $sportName]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * @return array<string, mixed>
 */
function rallySetsInitialState(string $sportName = 'Volleyball'): array
{
    $isSepakTakraw = mb_strtolower($sportName) === 'sepak takraw';

    return [
        'sets' => [],
        'current_set_score_a' => 0, 'current_set_score_b' => 0,
        'sets_won_a' => 0, 'sets_won_b' => 0,
        'set_target_points' => $isSepakTakraw ? 21 : 25,
        'deciding_set_target_points' => $isSepakTakraw ? 21 : 15,
        'sets_to_win' => $isSepakTakraw ? 2 : 3,
        'possession' => null,
    ];
}

/**
 * A scheduled match whose sport resolves to the football/futsal
 * scoreboard.
 */
function footballMatch(string $sportName = 'Football'): EventMatch
{
    $sport = Sport::factory()->create(['name' => $sportName]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * @return array<string, mixed>
 */
function footballInitialState(string $sportName = 'Football'): array
{
    return [
        'yellow_cards_a' => 0, 'yellow_cards_b' => 0,
        'red_cards_a' => 0, 'red_cards_b' => 0,
        'minutes_per_half' => mb_strtolower($sportName) === 'futsal' ? 20 : 45,
    ];
}

/**
 * A scheduled match whose sport resolves to the table tennis/badminton
 * scoreboard.
 */
function racketGamesMatch(string $sportName = 'Table Tennis'): EventMatch
{
    $sport = Sport::factory()->create(['name' => $sportName]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * @return array<string, mixed>
 */
function racketGamesInitialState(string $sportName = 'Table Tennis'): array
{
    $isBadminton = mb_strtolower($sportName) === 'badminton';

    return [
        'games' => [],
        'current_game_score_a' => 0, 'current_game_score_b' => 0,
        'games_won_a' => 0, 'games_won_b' => 0,
        'game_target_points' => $isBadminton ? 21 : 11,
        'hard_cap_points' => $isBadminton ? 30 : 0,
        'games_to_win' => $isBadminton ? 2 : 3,
        'possession' => null,
    ];
}

/**
 * A scheduled match whose sport resolves to the wrestling scoreboard.
 */
function wrestlingMatch(): EventMatch
{
    $sport = Sport::factory()->create(['name' => 'Wrestling']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * @return array<string, mixed>
 */
function wrestlingInitialState(): array
{
    return [
        'period_duration_seconds' => 180, 'rest_duration_seconds' => 30, 'total_periods' => 2,
        'clock_seconds' => 180, 'clock_updated_at' => null, 'clock_phase' => 'period',
        'fall_side' => null, 'fall_declared_at' => null,
    ];
}

/**
 * A scheduled match whose sport resolves to the tennis scoreboard.
 */
function tennisMatch(): EventMatch
{
    $sport = Sport::factory()->create(['name' => 'Tennis']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * @return array<string, mixed>
 */
function tennisInitialState(): array
{
    return [
        'sets' => [],
        'sets_won_a' => 0, 'sets_won_b' => 0,
        'current_set_games_a' => 0, 'current_set_games_b' => 0,
        'current_game_points_a' => 0, 'current_game_points_b' => 0,
        'is_tiebreak' => false,
        'tiebreak_points_a' => 0, 'tiebreak_points_b' => 0,
        'sets_to_win' => 2,
        'possession' => null,
    ];
}

/**
 * A scheduled match whose sport resolves to the goal ball scoreboard.
 */
function goalBallMatch(): EventMatch
{
    $sport = Sport::factory()->create(['name' => 'Paragames - Goal Ball']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * @return array<string, mixed>
 */
function goalBallInitialState(): array
{
    return [
        'penalty_throws_a' => 0, 'penalty_throws_b' => 0,
        'minutes_per_half' => 6,
    ];
}

/**
 * A scheduled match whose sport resolves to the billiard scoreboard.
 */
function billiardMatch(): EventMatch
{
    $sport = Sport::factory()->create(['name' => 'Billiard']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * @return array<string, mixed>
 */
function billiardInitialState(): array
{
    return [
        'racks' => [],
        'racks_won_a' => 0, 'racks_won_b' => 0,
        'racks_to_win' => 5,
    ];
}

/**
 * A scheduled match whose sport resolves to the bocce scoreboard.
 */
function bocceMatch(): EventMatch
{
    $sport = Sport::factory()->create(['name' => 'Paragames - Boccee']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    return EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);
}

/**
 * @return array<string, mixed>
 */
function bocceInitialState(): array
{
    return [
        'ends' => [],
        'ends_played' => 0,
        'target_score' => 12,
    ];
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

test('a technical official can view a match in their assigned sport but not another sport', function () {
    $match = basketballMatch();
    $otherSportMatch = boxingMatch();

    $official = User::factory()->technicalOfficial()->create();
    $official->sports()->attach($match->event->sport_id);

    $this->actingAs($official)
        ->get("/matches/{$match->id}/scoring-session")
        ->assertOk()
        ->assertJson(['session' => null]);

    $this->actingAs($official)
        ->get("/matches/{$otherSportMatch->id}/scoring-session")
        ->assertForbidden();
});

test('a technical official can run a full basketball scoring session for their assigned sport', function () {
    $match = basketballMatch();
    $official = User::factory()->technicalOfficial()->create();
    $official->sports()->attach($match->event->sport_id);

    $this->actingAs($official)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', true));

    $this->actingAs($official)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertRedirect();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    $this->actingAs($official)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 2])
        ->assertRedirect();

    $this->actingAs($official)
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'add', 'side' => 'a'])
        ->assertRedirect();

    $this->actingAs($official)
        ->patch("/scoring-sessions/{$session->id}/end", [])
        ->assertRedirect();

    $session->refresh();

    expect($session->score_a)->toBe(2)
        ->and($session->sport_state['fouls_a'])->toBe(1)
        ->and($session->status)->toBe(ScoringSessionStatus::Ended);
});

test('starting a scheduled team scoreboard uses its assigned teams instead of operator-entered labels', function () {
    $match = basketballMatch();
    $entryA = confirmedEntryForScoringSession($match);
    $entryB = confirmedEntryForScoringSession($match);
    $match->entries()->sync([$entryA->id, $entryB->id]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Wrong A',
        'side_b_label' => 'Wrong B',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->side_a_label)->toBe($entryA->athlete->school->name)
        ->and($session->side_b_label)->toBe($entryB->athlete->school->name);
});

test('a technical official cannot run scoring for a match outside their assigned sport', function () {
    $basketball = basketballMatch();
    $boxing = boxingMatch();

    $official = User::factory()->technicalOfficial()->create();
    $official->sports()->attach($basketball->event->sport_id);

    $this->actingAs($official)
        ->post("/matches/{$boxing->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertForbidden();

    $this->actingAs($official)
        ->get("/matches/{$boxing->id}/scoreboard")
        ->assertForbidden();
});

test('a tournament manager can run a full basketball scoring session for their managed sport', function () {
    $match = basketballMatch();
    $manager = User::factory()->tournamentManager()->create();
    $match->event->sport->forceFill(['tournament_manager_id' => $manager->id])->save();

    $this->actingAs($manager)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', true));

    $this->actingAs($manager)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertRedirect();
});

test('a tournament manager cannot run scoring for a match outside their managed sport', function () {
    $basketball = basketballMatch();
    $boxing = boxingMatch();

    $manager = User::factory()->tournamentManager()->create();
    $basketball->event->sport->forceFill(['tournament_manager_id' => $manager->id])->save();

    $this->actingAs($manager)
        ->post("/matches/{$boxing->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertForbidden();

    $this->actingAs($manager)
        ->get("/matches/{$boxing->id}/scoreboard")
        ->assertForbidden();
});

test('an organizer assigned as tournament secretary can run a scoring session for their assigned meet+sport', function () {
    $match = basketballMatch();

    $organizer = User::factory()->organizer()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $match->meet_id, 'sport_id' => $match->event->sport_id]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $organizer->id,
        'role' => MeetSportAssignmentRole::TournamentSecretary,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($organizer)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canManage', true));

    $this->actingAs($organizer)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertRedirect();
});

test('an organizer assigned as tournament ict can run a scoring session for their assigned meet+sport', function () {
    $match = boxingMatch();

    $organizer = User::factory()->organizer()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $match->meet_id, 'sport_id' => $match->event->sport_id]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $organizer->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($organizer)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertRedirect();
});

test('an organizer\'s tournament secretary/ICT assignment does not carry over to a different meet or sport', function () {
    $basketball = basketballMatch();
    $boxing = boxingMatch();

    $organizer = User::factory()->organizer()->create();
    // Assigned as Tournament Secretary for boxing's meet+sport, not basketball's.
    $meetSport = MeetSport::factory()->create(['meet_id' => $boxing->meet_id, 'sport_id' => $boxing->event->sport_id]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $organizer->id,
        'role' => MeetSportAssignmentRole::TournamentSecretary,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($organizer)
        ->post("/matches/{$basketball->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertForbidden();
});

test('a pending or ended tournament secretary/ICT assignment does not grant scoreboard access', function (MeetSportAssignmentStatus $status) {
    $match = basketballMatch();

    $organizer = User::factory()->organizer()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $match->meet_id, 'sport_id' => $match->event->sport_id]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $organizer->id,
        'role' => MeetSportAssignmentRole::TournamentSecretary,
        'status' => $status,
    ]);

    $this->actingAs($organizer)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertForbidden();
})->with([
    'pending' => MeetSportAssignmentStatus::Pending,
    'declined' => MeetSportAssignmentStatus::Declined,
    'ended' => MeetSportAssignmentStatus::Ended,
]);

test('an organizer assigned as tournament manager (not secretary/ICT) cannot run the scoreboard', function () {
    $match = basketballMatch();

    $organizer = User::factory()->organizer()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $match->meet_id, 'sport_id' => $match->event->sport_id]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $organizer->id,
        'role' => MeetSportAssignmentRole::TournamentManager,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    $this->actingAs($organizer)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
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
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'technical official not assigned to this match\'s sport' => fn () => User::factory()->technicalOfficial()->create(),
]);

test('a session can only be started for a scheduled match', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Completed]);

    $this->actingAs(User::factory()->admin()->create())
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'A', 'side_b_label' => 'B'])
        ->assertSessionHasErrors('match_id');

    expect(ScoringSession::query()->count())->toBe(0);
});

test('only one active session per match is allowed', function () {
    $match = basketballMatch();
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

test('the running clock excludes paused time and freezes once ended', function () {
    Carbon::setTestNow('2026-01-01 10:00:00');

    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    Carbon::setTestNow('2026-01-01 10:01:00'); // +60s active
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/pause", [])->assertSessionHasNoErrors();

    Carbon::setTestNow('2026-01-01 10:01:30'); // +30s paused — must not count
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/resume", [])->assertSessionHasNoErrors();

    Carbon::setTestNow('2026-01-01 10:02:15'); // +45s active

    $payload = $session->refresh()->toLivePayload();

    expect($payload['elapsed_seconds'])->toBe(105)
        ->and($payload['clock_running'])->toBeTrue();

    Carbon::setTestNow('2026-01-01 10:02:20'); // +5s active, before ending
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/end", [])->assertSessionHasNoErrors();

    Carbon::setTestNow('2026-01-01 10:05:00'); // time passing after the game ended must not count

    $endedPayload = $session->refresh()->toLivePayload();

    expect($endedPayload['elapsed_seconds'])->toBe(110)
        ->and($endedPayload['clock_running'])->toBeFalse();

    Carbon::setTestNow();
});

test('a correction requires a reason', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'correction', 'side' => 'a', 'delta' => -1])
        ->assertSessionHasErrors('reason');
});

test('score never goes below zero', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'score_a' => 1]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'correction', 'side' => 'a', 'delta' => -5, 'reason' => 'Fix'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->score_a)->toBe(0);
});

test('ending an unslotted scoring session completes the match and submits an EventResult', function () {
    $match = basketballMatch();
    $match->forceFill(['event_schedule_id' => null])->save();
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/end", [])
        ->assertSessionHasNoErrors();

    expect(EventResult::query()->count())->toBe(1)
        ->and(ResultPlacement::query()->count())->toBe(0)
        ->and($match->fresh()->status)->toBe(MatchStatus::Completed)
        ->and($match->result?->result_source)->toBe('live_score')
        ->and($match->result?->scoring_session_id)->toBe($session->id);
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

test('the scoreboard page exposes participant photos only for a two-entry match, and only when accredited', function () {
    $match = EventMatch::factory()->create();
    $entryA = confirmedEntryForScoringSession($match);
    $entryB = confirmedEntryForScoringSession($match);
    $match->entries()->attach([$entryA->id, $entryB->id]);

    $photo = FileUpload::factory()->create();
    $entryA->athlete->forceFill(['photo_upload_id' => $photo->id])->save();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('participants', 2)
            ->where('participants.0.photo_url', route('athletes.photo', $entryA->athlete))
            ->where('participants.1.photo_url', null));

    $entryC = confirmedEntryForScoringSession($match);
    $match->entries()->attach($entryC->id);

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('participants.0', null)
            ->where('participants.1', null));
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
        'sport_state' => basketballInitialSportState(),
    ]);
});

test('pausing freezes countdown clocks at their authoritative remaining time and resume reanchors them', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();
    $started = now()->startOfSecond();
    $state = basketballInitialSportState();
    $state['game_clock_seconds'] = 600;
    $state['game_clock_updated_at'] = $started->toIso8601String();
    $state['shot_clock_seconds'] = 24;
    $state['shot_clock_updated_at'] = $started->toIso8601String();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'status' => ScoringSessionStatus::InProgress,
        'sport_state' => $state,
    ]);

    $this->travel(7)->seconds();
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/pause")->assertSessionHasNoErrors();
    $paused = $session->fresh()->sport_state;

    expect($paused['game_clock_seconds'])->toBe(593)
        ->and($paused['shot_clock_seconds'])->toBe(17)
        ->and($paused['game_clock_updated_at'])->toBeNull()
        ->and($paused['shot_clock_updated_at'])->toBeNull();

    $this->travel(3)->seconds();
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/resume")->assertSessionHasNoErrors();
    $resumed = $session->fresh()->sport_state;

    expect($resumed['game_clock_seconds'])->toBe(593)
        ->and($resumed['shot_clock_seconds'])->toBe(17)
        ->and($resumed['game_clock_updated_at'])->not->toBeNull()
        ->and($resumed['shot_clock_updated_at'])->toBe($resumed['game_clock_updated_at']);
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
    'organizer' => fn () => User::factory()->organizer()->create(),
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
            ->where('session.sport_state', basketballInitialSportState()));
});

// WP-08-10: real play-by-play reconstructed from the score_events log

test('the live payload includes a play-by-play feed reconstructed from score events, newest first', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Mabini', 'side_b_label' => 'Montevista'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 2])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'add', 'side' => 'b'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'b', 'delta' => 3])
        ->assertSessionHasNoErrors();

    $plays = $session->refresh()->toLivePayload()['playByPlay'];

    expect($plays)->toHaveCount(3)
        // Newest first: the last thing recorded was Montevista's 3-point play.
        ->and($plays[0]['description'])->toBe('+3 — Montevista')
        ->and($plays[0]['score_a'])->toBe(2)
        ->and($plays[0]['score_b'])->toBe(3)
        ->and($plays[1]['description'])->toBe('Foul — Montevista')
        ->and($plays[1]['score_a'])->toBe(2)
        ->and($plays[1]['score_b'])->toBe(0)
        ->and($plays[2]['description'])->toBe('+2 — Mabini')
        ->and($plays[2]['score_a'])->toBe(2)
        ->and($plays[2]['score_b'])->toBe(0);
});

test('a scorer can remove recorded points and added fouls from play by play', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away']);

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 3]);
    $point = $session->events()->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/foul", ['action' => 'add', 'side' => 'b']);
    $foul = $session->events()->latest('id')->firstOrFail();

    expect($session->refresh()->playByPlay()[0]['removable'])->toBeTrue();

    $this->actingAs($admin)
        ->delete("/scoring-sessions/{$session->id}/events/{$point->id}", ['reason' => 'Wrong player selected'])
        ->assertSessionHasNoErrors();
    $this->actingAs($admin)
        ->delete("/scoring-sessions/{$session->id}/events/{$foul->id}", ['reason' => 'Official reversed the call'])
        ->assertSessionHasNoErrors();

    $fresh = $session->refresh();

    expect($fresh->score_a)->toBe(0)
        ->and($fresh->sport_state['fouls_b'])->toBe(0)
        ->and($fresh->events()->count())->toBe(0)
        ->and(AuditLog::query()->where('action', 'scoring.event_removed')->count())->toBe(2)
        ->and(AuditLog::query()->where('action', 'scoring.event_removed')->latest('id')->value('context')['reason'])
        ->toBe('Official reversed the call');
});

test('removing a play requires a reason', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away']);
    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();
    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 2]);
    $point = $session->events()->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->delete("/scoring-sessions/{$session->id}/events/{$point->id}")
        ->assertSessionHasErrors('reason');

    expect($point->fresh())->not->toBeNull()
        ->and($session->refresh()->score_a)->toBe(2);
});

test('corrections and foul resets cannot be removed from play by play', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away']);

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/score", [
        'type' => 'correction', 'side' => 'a', 'delta' => 1, 'reason' => 'Official correction',
    ]);
    $correction = $session->events()->latest('id')->firstOrFail();

    $this->actingAs($admin)
        ->delete("/scoring-sessions/{$session->id}/events/{$correction->id}", ['reason' => 'Attempted removal'])
        ->assertSessionHasErrors('event');

    expect($correction->fresh())->not->toBeNull()
        ->and($session->refresh()->score_a)->toBe(1)
        ->and($session->playByPlay()[0]['removable'])->toBeFalse();
});

test('the scoreboard page exposes real match metadata: sport, category, round, venue, and date', function () {
    $match = basketballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('match.sport', 'Basketball')
            ->has('match.category')
            ->where('match.round_label', $match->round_label));
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
        'sport_state' => [
            'rounds' => [],
            'round_duration_seconds' => 120, 'rest_duration_seconds' => 60, 'total_rounds' => 3,
            'clock_seconds' => 120, 'clock_updated_at' => null, 'clock_phase' => 'round',
            'bell_sounded_at' => null,
        ],
    ]);
});

test('the live payload resolves each corner\'s athlete and sports photo for a two-entry individual bout', function () {
    $match = boxingMatch();
    $entryA = confirmedEntryForScoringSession($match);
    $entryB = confirmedEntryForScoringSession($match);
    $match->entries()->attach([$entryA->id, $entryB->id]);

    $sportsPhoto = FileUpload::factory()->create();
    $entryA->athlete->forceFill(['sports_photo_upload_id' => $sportsPhoto->id])->save();

    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $payload = $session->toLivePayload();

    expect($payload['side_a_athlete'])->toBe([
        'name' => $entryA->athlete->fullName(),
        'sports_photo_url' => route('athletes.sports-photo', $entryA->athlete).'?v='.$sportsPhoto->id,
    ])->and($payload['side_b_athlete'])->toBe([
        'name' => $entryB->athlete->fullName(),
        'sports_photo_url' => null,
    ]);
});

test('the live payload leaves side athletes null when the bout has other than two entries', function () {
    $match = boxingMatch();
    $entryA = confirmedEntryForScoringSession($match);
    $match->entries()->attach($entryA->id);

    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    expect($session->toLivePayload())->toMatchArray([
        'side_a_athlete' => null,
        'side_b_athlete' => null,
    ]);
});

test('the live payload leaves side athletes null for a team event even with two entries', function () {
    $match = basketballMatch();
    $match->event()->update(['is_team_event' => true]);
    $entryA = confirmedEntryForScoringSession($match);
    $entryB = confirmedEntryForScoringSession($match);
    $match->entries()->attach([$entryA->id, $entryB->id]);

    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    expect($session->toLivePayload())->toMatchArray([
        'side_a_athlete' => null,
        'side_b_athlete' => null,
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

test('the play-by-play feed reconstructs running scores across rounds for boxing (WP-08-12 fix)', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'side_a_label' => 'Red',
        'side_b_label' => 'Blue',
        'sport_state' => ['rounds' => []],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 9, 'score_b' => 10])
        ->assertSessionHasNoErrors();

    $plays = $session->refresh()->toLivePayload()['playByPlay'];

    expect($plays)->toHaveCount(2)
        ->and($plays[0]['description'])->toBe('Round 2: Red 9 – 10 Blue')
        ->and($plays[0]['score_a'])->toBe(19)
        ->and($plays[0]['score_b'])->toBe(19)
        ->and($plays[1]['description'])->toBe('Round 1: Red 10 – 9 Blue')
        ->and($plays[1]['score_a'])->toBe(10)
        ->and($plays[1]['score_b'])->toBe(9);
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
    'organizer' => fn () => User::factory()->organizer()->create(),
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
            ->where('session.sport_state', [
                'rounds' => [['round' => 1, 'score_a' => 10, 'score_b' => 9]],
                'round_duration_seconds' => 120, 'rest_duration_seconds' => 60, 'total_rounds' => 3,
                'clock_seconds' => 120, 'clock_updated_at' => null, 'clock_phase' => 'round',
                'bell_sounded_at' => null,
            ]));
});

test('a manager can start a fresh round or rest phase on the round clock', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => boxingInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round-clock", ['phase' => 'rest'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)
        ->toMatchArray(['clock_phase' => 'rest', 'clock_seconds' => 60]);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round-clock", ['phase' => 'round'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)
        ->toMatchArray(['clock_phase' => 'round', 'clock_seconds' => 120]);
});

test('a manager can manually adjust the round clock without changing phase', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => boxingInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round-clock", ['seconds' => 45])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)
        ->toMatchArray(['clock_phase' => 'round', 'clock_seconds' => 45]);
});

test('the round-clock and bell endpoints are rejected for a non-boxing scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round-clock", ['seconds' => 30])
        ->assertStatus(422);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/bell", [])
        ->assertStatus(422);
});

test('a manager can sound the bell and it is recorded in play-by-play', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => boxingInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/bell", []);

    expect($session->fresh()->sport_state['bell_sounded_at'])->not->toBeNull()
        ->and($session->fresh()->playByPlay()[0]['description'])->toBe('Bell sounded');
});

test('a manager can update boxing game settings', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => boxingInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'round_duration_seconds' => 90,
            'rest_duration_seconds' => 30,
            'total_rounds' => 5,
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'round_duration_seconds' => 90,
        'rest_duration_seconds' => 30,
        'total_rounds' => 5,
    ]);
});

test('a round score cannot be recorded once every scheduled round is already judged', function () {
    $match = boxingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...boxingInitialSportState(), 'total_rounds' => 1],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertSessionHasErrors('score_a');

    expect($session->fresh()->sport_state['rounds'])->toHaveCount(1);
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
        'sport_state' => [
            'inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => [],
            'innings_scheduled' => 7,
            'team_color_a' => '#dc2626', 'team_color_b' => '#2563eb',
        ],
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
    'organizer' => fn () => User::factory()->organizer()->create(),
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

test('a manager can update softball/baseball game settings', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => [], 'innings_scheduled' => 7, 'team_color_a' => '#dc2626', 'team_color_b' => '#2563eb'],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'team_color_a' => '#111111',
            'team_color_b' => '#222222',
            'innings_scheduled' => 6,
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'team_color_a' => '#111111',
        'team_color_b' => '#222222',
        'innings_scheduled' => 6,
    ]);
});

// WP-08-12: real play-by-play descriptions for softball/baseball's own event types

test('the play-by-play feed describes inning runs and count updates from real payload data', function () {
    $match = softballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/inning-run", ['side' => 'a', 'runs' => 2])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'ball'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/count", ['action' => 'strike'])
        ->assertSessionHasNoErrors();

    $plays = $session->refresh()->toLivePayload()['playByPlay'];

    expect($plays)->toHaveCount(3)
        // Newest first.
        ->and($plays[0]['description'])->toBe('Strike (1-1)')
        ->and($plays[0]['score_a'])->toBe(2)
        ->and($plays[1]['description'])->toBe('Ball (1-0)')
        ->and($plays[2]['description'])->toBe('+2 runs — Home (Inning 1)')
        ->and($plays[2]['score_a'])->toBe(2)
        ->and($plays[2]['score_b'])->toBe(0);
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
        'sport_state' => basketballInitialSportState(),
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

// Live volleyball/sepak takraw scoreboard control

test('starting a session for a volleyball match initializes rally-set defaults and the board type', function () {
    $match = rallySetsMatch('Volleyball');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'volleyball_sepak_takraw',
        'sport_state' => rallySetsInitialState('Volleyball'),
    ]);
});

test('starting a session for a sepak takraw match uses its own rally-set defaults', function () {
    $match = rallySetsMatch('Sepak Takraw');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->sport_state)->toMatchArray([
        'set_target_points' => 21,
        'deciding_set_target_points' => 21,
        'sets_to_win' => 2,
    ]);
});

test('rally points accumulate the live set score without completing the set early', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => rallySetsInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    foreach (range(1, 20) as $i) {
        $this->actingAs($admin)
            ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
            ->assertSessionHasNoErrors();
    }

    $fresh = $session->fresh();

    expect($fresh->sport_state['current_set_score_a'])->toBe(20)
        ->and($fresh->sport_state['sets'])->toBeEmpty()
        ->and($fresh->sport_state['sets_won_a'])->toBe(0)
        ->and($fresh->score_a)->toBe(0)
        ->and(AuditLog::query()->where('action', 'scoring.rally_point_scored')->count())->toBe(20);
});

test('reaching the set target with a 2-point lead finalizes the set and increments sets won', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...rallySetsInitialState(), 'current_set_score_a' => 24, 'current_set_score_b' => 20],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['sets'])->toBe([['set' => 1, 'score_a' => 25, 'score_b' => 20]])
        ->and($fresh->sport_state['sets_won_a'])->toBe(1)
        ->and($fresh->sport_state['sets_won_b'])->toBe(0)
        ->and($fresh->sport_state['current_set_score_a'])->toBe(0)
        ->and($fresh->sport_state['current_set_score_b'])->toBe(0)
        ->and($fresh->score_a)->toBe(1)
        ->and($fresh->score_b)->toBe(0)
        ->and(AuditLog::query()->where('action', 'scoring.set_completed')->count())->toBe(1);
});

test('reaching the target without a 2-point lead does not finalize the set', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...rallySetsInitialState(), 'current_set_score_a' => 24, 'current_set_score_b' => 24],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['sets'])->toBeEmpty()
        ->and($fresh->sport_state['current_set_score_a'])->toBe(25)
        ->and($fresh->sport_state['current_set_score_b'])->toBe(24);
});

test('the deciding set uses deciding_set_target_points instead of set_target_points', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [
            ...rallySetsInitialState(),
            'sets_won_a' => 2, 'sets_won_b' => 2,
            'current_set_score_a' => 14, 'current_set_score_b' => 10,
        ],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['sets'])->toBe([['set' => 1, 'score_a' => 15, 'score_b' => 10]])
        ->and($fresh->sport_state['sets_won_a'])->toBe(3);
});

test('a rally-point correction adjusts the live set score without triggering set completion', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...rallySetsInitialState(), 'current_set_score_a' => 24, 'current_set_score_b' => 20],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/rally-point", [
            'type' => 'correction', 'side' => 'a', 'delta' => 1, 'reason' => 'Referee overturned the call',
        ])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['current_set_score_a'])->toBe(25)
        ->and($fresh->sport_state['sets'])->toBeEmpty()
        ->and($fresh->sport_state['sets_won_a'])->toBe(0)
        ->and(AuditLog::query()->where('action', 'scoring.rally_point_corrected')->count())->toBe(1);
});

test('a rally-point correction requires a reason', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => rallySetsInitialState(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'correction', 'side' => 'a', 'delta' => -1])
        ->assertSessionHasErrors('reason');
});

test('the rally-point endpoint is rejected for a non-volleyball-sepak-takraw scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertStatus(422);
});

test('non-managers cannot record a rally point', function (User $user) {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => rallySetsInitialState()]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a rally point cannot be recorded once the session has ended', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => rallySetsInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasErrors('status');
});

test('a manager can update volleyball/sepak takraw match settings', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => rallySetsInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'set_target_points' => 21,
            'deciding_set_target_points' => 15,
            'sets_to_win' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'set_target_points' => 21,
        'deciding_set_target_points' => 15,
        'sets_to_win' => 2,
    ]);
});

test('a manager can set and clear the serve indicator for a volleyball match, reusing the possession endpoint', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => rallySetsInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/possession", ['side' => 'a']);
    expect($session->fresh()->sport_state['possession'])->toBe('a');

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/possession", ['side' => null]);
    expect($session->fresh()->sport_state['possession'])->toBeNull();
});

test('the possession endpoint is still rejected for a softball session (only basketball and rally-sets boards)', function () {
    $match = softballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['inning' => 1, 'half' => 'top', 'outs' => 0, 'balls' => 0, 'strikes' => 0, 'innings' => []],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/possession", ['side' => 'a'])
        ->assertStatus(422);
});

test('the play-by-play feed reconstructs sets-won running scores and describes rally points/set completions', function () {
    $match = rallySetsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'side_a_label' => 'Home',
        'side_b_label' => 'Away',
        'sport_state' => [...rallySetsInitialState(), 'current_set_score_a' => 24, 'current_set_score_b' => 20],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/rally-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    $plays = $session->refresh()->toLivePayload()['playByPlay'];

    expect($plays)->toHaveCount(2)
        // Newest first: the SetComplete event logged after the point.
        ->and($plays[0]['description'])->toBe('Set 1: Home 25 – 20 Away (leads 1-0)')
        ->and($plays[0]['score_a'])->toBe(1)
        ->and($plays[0]['score_b'])->toBe(0)
        ->and($plays[1]['description'])->toBe('+1 — Home (Set 1: 25-20)')
        ->and($plays[1]['score_a'])->toBe(0)
        ->and($plays[1]['score_b'])->toBe(0);
});

test('a volleyball match session can be forced to the generic board at start', function () {
    $match = rallySetsMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Home', 'side_b_label' => 'Away', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

// Live football/futsal scoreboard control

test('starting a session for a football match initializes card tallies and the board type', function () {
    $match = footballMatch('Football');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'football_futsal',
        'sport_state' => footballInitialState('Football'),
    ]);
});

test('starting a session for a futsal match uses a 20-minute half by default', function () {
    $match = footballMatch('Futsal');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->sport_state)->toMatchArray(['minutes_per_half' => 20]);
});

test('a goal is recorded through the generic score endpoint, unchanged for this board', function () {
    $match = footballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => footballInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->score_a)->toBe(1);
});

test('a manager can issue a yellow or red card and reset the tallies', function () {
    $match = footballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => footballInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/card", ['action' => 'add', 'side' => 'a', 'type' => 'yellow'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/card", ['action' => 'add', 'side' => 'b', 'type' => 'red'])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'yellow_cards_a' => 1, 'yellow_cards_b' => 0,
        'red_cards_a' => 0, 'red_cards_b' => 1,
    ])
        ->and($fresh->playByPlay()[0]['description'])->toBe('Red card — Side B')
        ->and($fresh->playByPlay()[1]['description'])->toBe('Yellow card — Side A')
        ->and(AuditLog::query()->where('action', 'scoring.card_issued')->count())->toBe(2);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/card", ['action' => 'reset'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'yellow_cards_a' => 0, 'yellow_cards_b' => 0,
        'red_cards_a' => 0, 'red_cards_b' => 0,
    ]);
});

test('a card requires a side and type when adding', function () {
    $match = footballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => footballInitialState(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/card", ['action' => 'add'])
        ->assertSessionHasErrors(['side', 'type']);
});

test('the card endpoint is rejected for a non-football-futsal scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/card", ['action' => 'add', 'side' => 'a', 'type' => 'yellow'])
        ->assertStatus(422);
});

test('non-managers cannot issue a card', function (User $user) {
    $match = footballMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => footballInitialState()]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/card", ['action' => 'add', 'side' => 'a', 'type' => 'yellow'])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a card cannot be recorded once the session has ended', function () {
    $match = footballMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => footballInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/card", ['action' => 'add', 'side' => 'a', 'type' => 'yellow'])
        ->assertSessionHasErrors('status');
});

test('a manager can update the minutes-per-half setting', function () {
    $match = footballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => footballInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", ['minutes_per_half' => 40])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray(['minutes_per_half' => 40]);
});

test('the half stepper reuses the generic period endpoint', function () {
    $match = footballMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => footballInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/period", ['period_label' => '2nd Half'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->period_label)->toBe('2nd Half');
});

test('the scoreboard page exposes board type and card state for a football match', function () {
    $match = footballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.board_type', 'football_futsal')
            ->where('session.sport_state.yellow_cards_a', 0));
});

test('a football match session can be forced to the generic board at start', function () {
    $match = footballMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Home', 'side_b_label' => 'Away', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

// Live table tennis/badminton scoreboard control

test('starting a session for a table tennis match initializes rally-game defaults and the board type', function () {
    $match = racketGamesMatch('Table Tennis');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'racket_games',
        'sport_state' => racketGamesInitialState('Table Tennis'),
    ]);
});

test('starting a session for a badminton match uses its own hard-capped defaults', function () {
    $match = racketGamesMatch('Badminton');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->sport_state)->toMatchArray([
        'game_target_points' => 21,
        'hard_cap_points' => 30,
        'games_to_win' => 2,
    ]);
});

test('game points accumulate the live game score without completing the game early', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => racketGamesInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    foreach (range(1, 8) as $i) {
        $this->actingAs($admin)
            ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
            ->assertSessionHasNoErrors();
    }

    $fresh = $session->fresh();

    expect($fresh->sport_state['current_game_score_a'])->toBe(8)
        ->and($fresh->sport_state['games'])->toBeEmpty()
        ->and($fresh->sport_state['games_won_a'])->toBe(0)
        ->and($fresh->score_a)->toBe(0)
        ->and(AuditLog::query()->where('action', 'scoring.game_point_scored')->count())->toBe(8);
});

test('reaching the game target with a 2-point lead finalizes the game and increments games won', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...racketGamesInitialState(), 'current_game_score_a' => 10, 'current_game_score_b' => 8],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['games'])->toBe([['game' => 1, 'score_a' => 11, 'score_b' => 8]])
        ->and($fresh->sport_state['games_won_a'])->toBe(1)
        ->and($fresh->sport_state['current_game_score_a'])->toBe(0)
        ->and($fresh->sport_state['current_game_score_b'])->toBe(0)
        ->and($fresh->score_a)->toBe(1)
        ->and($fresh->score_b)->toBe(0)
        ->and(AuditLog::query()->where('action', 'scoring.game_completed')->count())->toBe(1);
});

test('reaching the target without a 2-point lead does not finalize the game (no cap)', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...racketGamesInitialState(), 'current_game_score_a' => 10, 'current_game_score_b' => 10],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['games'])->toBeEmpty()
        ->and($fresh->sport_state['current_game_score_a'])->toBe(11)
        ->and($fresh->sport_state['current_game_score_b'])->toBe(10);
});

test('badmintons hard cap wins the game outright even without a 2-point lead', function () {
    $match = racketGamesMatch('Badminton');
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...racketGamesInitialState('Badminton'), 'current_game_score_a' => 29, 'current_game_score_b' => 29],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['games'])->toBe([['game' => 1, 'score_a' => 30, 'score_b' => 29]])
        ->and($fresh->sport_state['games_won_a'])->toBe(1);
});

test('a game-point correction adjusts the live game score without triggering game completion', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...racketGamesInitialState(), 'current_game_score_a' => 10, 'current_game_score_b' => 8],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/game-point", [
            'type' => 'correction', 'side' => 'a', 'delta' => 1, 'reason' => 'Referee overturned the call',
        ])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['current_game_score_a'])->toBe(11)
        ->and($fresh->sport_state['games'])->toBeEmpty()
        ->and($fresh->sport_state['games_won_a'])->toBe(0)
        ->and(AuditLog::query()->where('action', 'scoring.game_point_corrected')->count())->toBe(1);
});

test('a game-point correction requires a reason', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => racketGamesInitialState(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'correction', 'side' => 'a', 'delta' => -1])
        ->assertSessionHasErrors('reason');
});

test('the game-point endpoint is rejected for a non-racket-games scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertStatus(422);
});

test('non-managers cannot record a game point', function (User $user) {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => racketGamesInitialState()]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a game point cannot be recorded once the session has ended', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => racketGamesInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasErrors('status');
});

test('a manager can update table tennis/badminton match settings', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => racketGamesInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'game_target_points' => 21,
            'hard_cap_points' => 0,
            'games_to_win' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'game_target_points' => 21,
        'hard_cap_points' => 0,
        'games_to_win' => 2,
    ]);
});

test('a manager can set and clear the serve indicator for a racket-games match, reusing the possession endpoint', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => racketGamesInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/possession", ['side' => 'a']);
    expect($session->fresh()->sport_state['possession'])->toBe('a');

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/possession", ['side' => null]);
    expect($session->fresh()->sport_state['possession'])->toBeNull();
});

test('the play-by-play feed reconstructs games-won running scores and describes game points/completions', function () {
    $match = racketGamesMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'side_a_label' => 'Home',
        'side_b_label' => 'Away',
        'sport_state' => [...racketGamesInitialState(), 'current_game_score_a' => 10, 'current_game_score_b' => 8],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/game-point", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    $plays = $session->refresh()->toLivePayload()['playByPlay'];

    expect($plays)->toHaveCount(2)
        // Newest first: the GameComplete event logged after the point.
        ->and($plays[0]['description'])->toBe('Game 1: Home 11 – 8 Away (leads 1-0)')
        ->and($plays[0]['score_a'])->toBe(1)
        ->and($plays[0]['score_b'])->toBe(0)
        ->and($plays[1]['description'])->toBe('+1 — Home (Game 1: 11-8)')
        ->and($plays[1]['score_a'])->toBe(0)
        ->and($plays[1]['score_b'])->toBe(0);
});

test('a table tennis match session can be forced to the generic board at start', function () {
    $match = racketGamesMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Home', 'side_b_label' => 'Away', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

// Live combat-rounds scoreboard control (taekwondo/wushu/pencak silat —
// reuses boxing's own round()/roundClock()/bell()/settings() endpoints)

test('starting a session for taekwondo, wushu, or pencak silat resolves the combat-rounds board with boxing-shaped defaults', function (string $sportName) {
    $match = combatRoundsMatch($sportName);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Red', 'side_b_label' => 'Blue'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'combat_rounds',
        'sport_state' => boxingInitialSportState(),
    ]);
})->with(['Taekwondo', 'Wushu', 'Pencak Silat', 'Arnis']);

test('round(), roundClock(), and bell() all work for a combat-rounds session, previously boxing-only', function () {
    $match = combatRoundsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => boxingInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round", ['score_a' => 10, 'score_b' => 9])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/round-clock", ['phase' => 'rest'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/bell", [])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['rounds'])->toBe([['round' => 1, 'score_a' => 10, 'score_b' => 9]])
        ->and($fresh->sport_state['clock_phase'])->toBe('rest')
        ->and($fresh->sport_state['bell_sounded_at'])->not->toBeNull()
        ->and($fresh->score_a)->toBe(10)
        ->and($fresh->score_b)->toBe(9);
});

test('a manager can update combat-rounds settings, reusing boxing\'s validation', function () {
    $match = combatRoundsMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => boxingInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'round_duration_seconds' => 90,
            'rest_duration_seconds' => 30,
            'total_rounds' => 5,
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'round_duration_seconds' => 90,
        'rest_duration_seconds' => 30,
        'total_rounds' => 5,
    ]);
});

test('boxing itself still resolves to its own board type, unaffected by sharing its endpoints with combat-rounds', function () {
    $match = boxingMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Red', 'side_b_label' => 'Blue'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'boxing']);
});

test('a combat-rounds match session can be forced to the generic board at start', function () {
    $match = combatRoundsMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Red', 'side_b_label' => 'Blue', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

// Live wrestling scoreboard control

test('starting a session for a wrestling match initializes period-clock defaults and the board type', function () {
    $match = wrestlingMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Red', 'side_b_label' => 'Blue'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'wrestling',
        'sport_state' => wrestlingInitialState(),
    ]);
});

test('a wrestling point accumulates the plain running score, real move recorded', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => wrestlingInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/wrestling-point", ['side' => 'a', 'move' => 'takedown', 'points' => 2])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/wrestling-point", ['side' => 'a', 'move' => 'near_fall', 'points' => 3])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/wrestling-point", ['side' => 'b', 'move' => 'escape', 'points' => 1])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->score_a)->toBe(5)
        ->and($fresh->score_b)->toBe(1)
        ->and(AuditLog::query()->where('action', 'scoring.wrestling_point_scored')->count())->toBe(3);
});

test('a wrestling score correction reuses the generic score endpoint', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => wrestlingInitialState(),
        'score_a' => 4,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", [
            'type' => 'correction', 'side' => 'a', 'delta' => -2, 'reason' => 'Wrong move tapped',
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->score_a)->toBe(2);
});

test('the wrestling-point endpoint rejects an invalid move', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => wrestlingInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/wrestling-point", ['side' => 'a', 'move' => 'suplex', 'points' => 2])
        ->assertSessionHasErrors('move');
});

test('the wrestling-point endpoint is rejected for a non-wrestling scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/wrestling-point", ['side' => 'a', 'move' => 'takedown', 'points' => 2])
        ->assertStatus(422);
});

test('a manager can start a fresh period or rest phase on the period clock', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => wrestlingInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/period-clock", ['phase' => 'rest'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)
        ->toMatchArray(['clock_phase' => 'rest', 'clock_seconds' => 30]);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/period-clock", ['phase' => 'period'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)
        ->toMatchArray(['clock_phase' => 'period', 'clock_seconds' => 180]);
});

test('a manager can manually adjust the period clock without changing phase', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => wrestlingInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/period-clock", ['seconds' => 90])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)
        ->toMatchArray(['clock_phase' => 'period', 'clock_seconds' => 90]);
});

test('a manager can declare and clear a fall', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => wrestlingInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/fall", ['action' => 'declare', 'side' => 'a'])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['fall_side'])->toBe('a')
        ->and($fresh->sport_state['fall_declared_at'])->not->toBeNull()
        ->and($fresh->playByPlay()[0]['description'])->toBe('Fall — Side A');

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/fall", ['action' => 'clear'])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['fall_side'])->toBeNull()
        ->and($fresh->sport_state['fall_declared_at'])->toBeNull()
        ->and($fresh->playByPlay()[0]['description'])->toBe('Fall cleared');
});

test('declaring a fall does not end the session or change the score', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => wrestlingInitialState(),
        'score_a' => 3,
        'score_b' => 5,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/fall", ['action' => 'declare', 'side' => 'a'])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->status->value)->toBe('in_progress')
        ->and($fresh->score_a)->toBe(3)
        ->and($fresh->score_b)->toBe(5);
});

test('a manager can sound the horn for a wrestling match, reusing the horn endpoint', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => wrestlingInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/horn", []);

    expect($session->fresh()->sport_state['horn_sounded_at'] ?? null)->not->toBeNull();
});

test('a manager can update wrestling settings', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => wrestlingInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'period_duration_seconds' => 120,
            'rest_duration_seconds' => 20,
            'total_periods' => 3,
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'period_duration_seconds' => 120,
        'rest_duration_seconds' => 20,
        'total_periods' => 3,
    ]);
});

test('non-managers cannot record a wrestling point, adjust the period clock, or declare a fall', function (User $user) {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => wrestlingInitialState()]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/wrestling-point", ['side' => 'a', 'move' => 'takedown', 'points' => 2])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/period-clock", ['seconds' => 90])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/fall", ['action' => 'declare', 'side' => 'a'])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a wrestling point cannot be recorded once the session has ended', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => wrestlingInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/wrestling-point", ['side' => 'a', 'move' => 'takedown', 'points' => 2])
        ->assertSessionHasErrors('status');
});

test('the play-by-play feed reconstructs the running score for wrestling points and describes falls', function () {
    $match = wrestlingMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'side_a_label' => 'Red',
        'side_b_label' => 'Blue',
        'sport_state' => wrestlingInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/wrestling-point", ['side' => 'a', 'move' => 'takedown', 'points' => 2])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/fall", ['action' => 'declare', 'side' => 'a'])
        ->assertSessionHasNoErrors();

    $plays = $session->refresh()->toLivePayload()['playByPlay'];

    expect($plays)->toHaveCount(2)
        ->and($plays[0]['description'])->toBe('Fall — Red')
        ->and($plays[0]['score_a'])->toBe(2)
        ->and($plays[0]['score_b'])->toBe(0)
        ->and($plays[1]['description'])->toBe('+2 Takedown — Red')
        ->and($plays[1]['score_a'])->toBe(2)
        ->and($plays[1]['score_b'])->toBe(0);
});

test('a wrestling match session can be forced to the generic board at start', function () {
    $match = wrestlingMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Red', 'side_b_label' => 'Blue', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

// Live tennis scoreboard control (real Love/15/30/40/deuce/advantage
// scoring, 6-game sets with a tiebreak at 6-6, best-of-N sets)

test('starting a session for a tennis match initializes the real scoring-state defaults and the board type', function () {
    $match = tennisMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Red', 'side_b_label' => 'Blue'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'tennis',
        'sport_state' => tennisInitialState(),
    ]);
});

test('winning 4 points to love wins the game with no deuce needed', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...tennisInitialState(), 'current_game_points_a' => 3],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a'])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'current_set_games_a' => 1, 'current_set_games_b' => 0,
        'current_game_points_a' => 0, 'current_game_points_b' => 0,
    ]);
});

test('a game at deuce requires a real 2-point lead to win, not just reaching 4', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...tennisInitialState(), 'current_game_points_a' => 3, 'current_game_points_b' => 3],
    ]);
    $admin = User::factory()->admin()->create();

    // Advantage A (4-3) — not yet a win.
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a']);
    expect($session->fresh()->sport_state)->toMatchArray([
        'current_game_points_a' => 4, 'current_game_points_b' => 3, 'current_set_games_a' => 0,
    ]);

    // Back to deuce (4-4).
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'b']);
    expect($session->fresh()->sport_state)->toMatchArray(['current_game_points_a' => 4, 'current_game_points_b' => 4]);

    // Advantage A again (5-4).
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a']);
    expect($session->fresh()->sport_state)->toMatchArray(['current_set_games_a' => 0]);

    // A wins the game (6-4, a real 2-point lead).
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a']);
    expect($session->fresh()->sport_state)->toMatchArray([
        'current_set_games_a' => 1, 'current_game_points_a' => 0, 'current_game_points_b' => 0,
    ]);
});

test('winning the 12th game to tie 6-6 triggers a tiebreak instead of ending the set', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [
            ...tennisInitialState(),
            'current_set_games_a' => 5, 'current_set_games_b' => 6,
            'current_game_points_a' => 3, 'current_game_points_b' => 0,
        ],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'current_set_games_a' => 6, 'current_set_games_b' => 6,
        'is_tiebreak' => true, 'tiebreak_points_a' => 0, 'tiebreak_points_b' => 0,
    ]);
});

test('winning the tiebreak completes the set 7-6 and increments sets won', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [
            ...tennisInitialState(),
            'current_set_games_a' => 6, 'current_set_games_b' => 6,
            'is_tiebreak' => true, 'tiebreak_points_a' => 6, 'tiebreak_points_b' => 5,
        ],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a'])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'sets' => [['set' => 1, 'score_a' => 7, 'score_b' => 6]],
        'sets_won_a' => 1, 'sets_won_b' => 0,
        'current_set_games_a' => 0, 'current_set_games_b' => 0,
        'is_tiebreak' => false, 'tiebreak_points_a' => 0, 'tiebreak_points_b' => 0,
    ])
        ->and($fresh->score_a)->toBe(1)
        ->and(AuditLog::query()->where('action', 'scoring.set_completed')->count())->toBe(1);
});

test('winning 6 games with a 2-game lead completes the set outright, no tiebreak', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [
            ...tennisInitialState(),
            'current_set_games_a' => 5, 'current_set_games_b' => 4,
            'current_game_points_a' => 3, 'current_game_points_b' => 0,
        ],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'sets' => [['set' => 1, 'score_a' => 6, 'score_b' => 4]],
        'sets_won_a' => 1,
        'is_tiebreak' => false,
    ]);
});

test('7-5 also completes the set, a real 2-game lead past 6 games', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [
            ...tennisInitialState(),
            'current_set_games_a' => 6, 'current_set_games_b' => 5,
            'current_game_points_a' => 3, 'current_game_points_b' => 0,
        ],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'sets' => [['set' => 1, 'score_a' => 7, 'score_b' => 5]],
        'sets_won_a' => 1,
    ]);
});

test('undo reverses the most recent point', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => tennisInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a']);
    expect($session->fresh()->sport_state['current_game_points_a'])->toBe(1);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-undo", [])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray(['current_game_points_a' => 0, 'current_game_points_b' => 0]);
});

test('undo reverses a point that had just completed a set, restoring sets won and the score column', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [
            ...tennisInitialState(),
            'current_set_games_a' => 5, 'current_set_games_b' => 4,
            'current_game_points_a' => 3, 'current_game_points_b' => 0,
        ],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a']);
    expect($session->fresh()->sport_state['sets_won_a'])->toBe(1)
        ->and($session->fresh()->score_a)->toBe(1);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-undo", [])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'sets' => [], 'sets_won_a' => 0, 'sets_won_b' => 0,
        'current_set_games_a' => 5, 'current_set_games_b' => 4,
        'current_game_points_a' => 3, 'current_game_points_b' => 0,
    ])
        ->and($fresh->score_a)->toBe(0)
        ->and($fresh->toLivePayload()['playByPlay'][0]['score_a'])->toBe(0);
});

test('undo is a harmless no-op when there is nothing to undo', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => tennisInitialState(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/tennis-undo", [])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toBe(tennisInitialState());
});

test('the tennis-point and tennis-undo endpoints are rejected for a non-tennis scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a'])
        ->assertStatus(422);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/tennis-undo", [])
        ->assertStatus(422);
});

test('non-managers cannot record or undo a tennis point', function (User $user) {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => tennisInitialState()]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/tennis-undo", [])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a tennis point cannot be recorded once the session has ended', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => tennisInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a'])
        ->assertSessionHasErrors('status');
});

test('a manager can update the sets-to-win setting, only 2 or 3 accepted', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => tennisInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", ['sets_to_win' => 3])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray(['sets_to_win' => 3]);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", ['sets_to_win' => 4])
        ->assertSessionHasErrors('sets_to_win');
});

test('a manager can set and clear the serve indicator for a tennis match, reusing the possession endpoint', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => tennisInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/possession", ['side' => 'a']);
    expect($session->fresh()->sport_state['possession'])->toBe('a');
});

test('the play-by-play feed describes deuce, advantage, and an undo in real tennis terms', function () {
    $match = tennisMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'side_a_label' => 'Red',
        'side_b_label' => 'Blue',
        'sport_state' => [...tennisInitialState(), 'current_game_points_a' => 3, 'current_game_points_b' => 3],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/tennis-point", ['side' => 'a']);
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/tennis-undo", []);

    $plays = $session->refresh()->toLivePayload()['playByPlay'];

    expect($plays)->toHaveCount(2)
        ->and($plays[0]['description'])->toBe('Last point undone')
        ->and($plays[1]['description'])->toBe('+1 — Red (Games 0-0, Ad Red)');
});

test('a tennis match session can be forced to the generic board at start', function () {
    $match = tennisMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Red', 'side_b_label' => 'Blue', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

// Live goal ball scoreboard control

test('starting a session for a goal ball match initializes penalty-throw tallies and the board type', function () {
    $match = goalBallMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'goal_ball',
        'sport_state' => goalBallInitialState(),
    ]);
});

test('a goal ball goal is recorded through the generic score endpoint, unchanged for this board', function () {
    $match = goalBallMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => goalBallInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 1])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->score_a)->toBe(1);
});

test('a manager can issue a penalty throw and reset the tallies', function () {
    $match = goalBallMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => goalBallInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/penalty-throw", ['action' => 'add', 'side' => 'a'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/penalty-throw", ['action' => 'add', 'side' => 'b'])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'penalty_throws_a' => 1, 'penalty_throws_b' => 1,
    ])
        ->and($fresh->playByPlay()[0]['description'])->toBe('Penalty throw — Side B')
        ->and($fresh->playByPlay()[1]['description'])->toBe('Penalty throw — Side A')
        ->and(AuditLog::query()->where('action', 'scoring.penalty_throw_issued')->count())->toBe(2);

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/penalty-throw", ['action' => 'reset'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray([
        'penalty_throws_a' => 0, 'penalty_throws_b' => 0,
    ]);
});

test('a penalty throw requires a side when adding', function () {
    $match = goalBallMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => goalBallInitialState(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/penalty-throw", ['action' => 'add'])
        ->assertSessionHasErrors(['side']);
});

test('the penalty-throw endpoint is rejected for a non-goal-ball scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/penalty-throw", ['action' => 'add', 'side' => 'a'])
        ->assertStatus(422);
});

test('non-managers cannot issue a penalty throw', function (User $user) {
    $match = goalBallMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => goalBallInitialState()]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/penalty-throw", ['action' => 'add', 'side' => 'a'])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a penalty throw cannot be recorded once the session has ended', function () {
    $match = goalBallMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => goalBallInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/penalty-throw", ['action' => 'add', 'side' => 'a'])
        ->assertSessionHasErrors('status');
});

test('a manager can update the minutes-per-half setting for goal ball', function () {
    $match = goalBallMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => goalBallInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", ['minutes_per_half' => 8])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray(['minutes_per_half' => 8]);
});

test('the half stepper reuses the generic period endpoint for goal ball', function () {
    $match = goalBallMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => goalBallInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/period", ['period_label' => '2nd Half'])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->period_label)->toBe('2nd Half');
});

test('the scoreboard page exposes board type and penalty-throw state for a goal ball match', function () {
    $match = goalBallMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.board_type', 'goal_ball')
            ->where('session.sport_state.penalty_throws_a', 0));
});

test('a goal ball match session can be forced to the generic board at start', function () {
    $match = goalBallMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Home', 'side_b_label' => 'Away', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

// Live billiard scoreboard control

test('starting a session for a billiard match initializes rack tracking and the board type', function () {
    $match = billiardMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'billiard',
        'sport_state' => billiardInitialState(),
    ]);
});

test('a manager can award racks to either side, appending to history and tracking the running total', function () {
    $match = billiardMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => billiardInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/billiard-rack", ['side' => 'a'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/billiard-rack", ['side' => 'a'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/billiard-rack", ['side' => 'b'])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'racks' => [
            ['rack' => 1, 'winner' => 'a'],
            ['rack' => 2, 'winner' => 'a'],
            ['rack' => 3, 'winner' => 'b'],
        ],
        'racks_won_a' => 2, 'racks_won_b' => 1,
    ])
        ->and($fresh->score_a)->toBe(2)
        ->and($fresh->score_b)->toBe(1)
        ->and($fresh->playByPlay()[0]['description'])->toBe('Rack 3: Side B wins (leads 2-1)')
        ->and(AuditLog::query()->where('action', 'scoring.rack_completed')->count())->toBe(3);
});

test('a manager can undo the most recently awarded rack', function () {
    $match = billiardMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [
            'racks' => [
                ['rack' => 1, 'winner' => 'a'],
                ['rack' => 2, 'winner' => 'b'],
            ],
            'racks_won_a' => 1, 'racks_won_b' => 1,
            'racks_to_win' => 5,
        ],
        'score_a' => 1,
        'score_b' => 1,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/billiard-undo-rack")
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'racks' => [['rack' => 1, 'winner' => 'a']],
        'racks_won_a' => 1, 'racks_won_b' => 0,
    ])
        ->and($fresh->score_a)->toBe(1)
        ->and($fresh->score_b)->toBe(0)
        ->and($fresh->playByPlay()[0]['description'])->toBe('Last rack undone');
});

test('undoing a rack is a harmless no-op when no racks have been played', function () {
    $match = billiardMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => billiardInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/billiard-undo-rack")
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray(billiardInitialState());
});

test('the billiard-rack endpoint requires a valid side', function () {
    $match = billiardMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => billiardInitialState(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/billiard-rack", [])
        ->assertSessionHasErrors(['side']);
});

test('the billiard-rack and billiard-undo-rack endpoints are rejected for a non-billiard scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/billiard-rack", ['side' => 'a'])
        ->assertStatus(422);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/billiard-undo-rack")
        ->assertStatus(422);
});

test('non-managers cannot award or undo a billiard rack', function (User $user) {
    $match = billiardMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => billiardInitialState()]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/billiard-rack", ['side' => 'a'])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/billiard-undo-rack")
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a billiard rack cannot be recorded once the session has ended', function () {
    $match = billiardMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => billiardInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/billiard-rack", ['side' => 'a'])
        ->assertSessionHasErrors('status');
});

test('a manager can update the racks-to-win setting', function () {
    $match = billiardMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => billiardInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", ['racks_to_win' => 7])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray(['racks_to_win' => 7]);
});

test('the scoreboard page exposes board type and rack state for a billiard match', function () {
    $match = billiardMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.board_type', 'billiard')
            ->where('session.sport_state.racks_to_win', 5));
});

test('a billiard match session can be forced to the generic board at start', function () {
    $match = billiardMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Home', 'side_b_label' => 'Away', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});

// Live bocce scoreboard control

test('starting a session for a bocce match initializes end tracking and the board type', function () {
    $match = bocceMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray([
        'board_type' => 'bocce',
        'sport_state' => bocceInitialState(),
    ]);
});

test('a manager can award end points to either side, appending to history and tracking the running score', function () {
    $match = bocceMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => bocceInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/bocce-end", ['side' => 'a', 'points' => 3])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/bocce-end", ['side' => 'b', 'points' => 1])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'ends' => [
            ['end' => 1, 'winner' => 'a', 'points' => 3],
            ['end' => 2, 'winner' => 'b', 'points' => 1],
        ],
        'ends_played' => 2,
    ])
        ->and($fresh->score_a)->toBe(3)
        ->and($fresh->score_b)->toBe(1)
        ->and($fresh->playByPlay()[0]['description'])->toBe('End 2: Side B +1 (score 3-1)')
        ->and(AuditLog::query()->where('action', 'scoring.end_completed')->count())->toBe(2);
});

test('a manager can undo the most recently completed end', function () {
    $match = bocceMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [
            'ends' => [
                ['end' => 1, 'winner' => 'a', 'points' => 3],
                ['end' => 2, 'winner' => 'b', 'points' => 2],
            ],
            'ends_played' => 2,
            'target_score' => 12,
        ],
        'score_a' => 3,
        'score_b' => 2,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/bocce-undo-end")
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state)->toMatchArray([
        'ends' => [['end' => 1, 'winner' => 'a', 'points' => 3]],
        'ends_played' => 1,
    ])
        ->and($fresh->score_a)->toBe(3)
        ->and($fresh->score_b)->toBe(0)
        ->and($fresh->playByPlay()[0]['description'])->toBe('Last end undone');
});

test('undoing an end is a harmless no-op when no ends have been played', function () {
    $match = bocceMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => bocceInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/bocce-undo-end")
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray(bocceInitialState());
});

test('the bocce-end endpoint requires a valid side and a positive points value', function () {
    $match = bocceMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => bocceInitialState(),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/bocce-end", ['side' => 'a', 'points' => 0])
        ->assertSessionHasErrors(['points']);
});

test('the bocce-end and bocce-undo-end endpoints are rejected for a non-bocce scoring session', function () {
    $match = EventMatch::factory()->create(['status' => MatchStatus::Scheduled]);
    $session = ScoringSession::factory()->create(['match_id' => $match->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/bocce-end", ['side' => 'a', 'points' => 1])
        ->assertStatus(422);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/bocce-undo-end")
        ->assertStatus(422);
});

test('non-managers cannot award or undo a bocce end', function (User $user) {
    $match = bocceMatch();
    $session = ScoringSession::factory()->create(['match_id' => $match->id, 'sport_state' => bocceInitialState()]);

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/bocce-end", ['side' => 'a', 'points' => 1])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch("/scoring-sessions/{$session->id}/bocce-undo-end")
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a bocce end cannot be recorded once the session has ended', function () {
    $match = bocceMatch();
    $session = ScoringSession::factory()->ended()->create(['match_id' => $match->id, 'sport_state' => bocceInitialState()]);

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/bocce-end", ['side' => 'a', 'points' => 1])
        ->assertSessionHasErrors('status');
});

test('a manager can update the target-score setting', function () {
    $match = bocceMatch();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => bocceInitialState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", ['target_score' => 21])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)->toMatchArray(['target_score' => 21]);
});

test('the scoreboard page exposes board type and end state for a bocce match', function () {
    $match = bocceMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Home', 'side_b_label' => 'Away'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.board_type', 'bocce')
            ->where('session.sport_state.target_score', 12));
});

test('a bocce match session can be forced to the generic board at start', function () {
    $match = bocceMatch();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/scoring-sessions", [
            'side_a_label' => 'Home', 'side_b_label' => 'Away', 'board_type' => 'generic',
        ])
        ->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $match->id)->firstOrFail();

    expect($session->toLivePayload())->toMatchArray(['board_type' => 'generic', 'sport_state' => null]);
});
