<?php

use App\Enums\MatchStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\MatchRosterPlayer;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;

/**
 * A confirmed entry for a fresh delegation/athlete/school — same helper
 * shape as ScoringSessionTest's own `confirmedEntryForScoringSession()`.
 */
function rosterConfirmedEntry(EventMatch $match): Entry
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
 * A second confirmed entry for the SAME school as a given representative
 * entry — the roster pool a team's other players come from.
 */
function rosterTeammateEntry(EventMatch $match, Entry $representative): Entry
{
    $athlete = Athlete::factory()->create(['delegation_id' => $representative->delegation_id]);

    return Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $representative->delegation_id,
        'event_id' => $match->event_id,
    ]);
}

/**
 * A scheduled basketball match with two representative entries already
 * attached (so side A/B school derivation works), ready for roster setup.
 *
 * @return array{0: EventMatch, 1: Entry, 2: Entry}
 */
function basketballMatchWithSides(): array
{
    $sport = Sport::factory()->create(['name' => 'Basketball']);
    $event = \App\Models\Event::factory()->create(['sport_id' => $sport->id, 'is_team_event' => true]);
    $match = EventMatch::factory()->create(['event_id' => $event->id, 'status' => MatchStatus::Scheduled]);

    $entryA = rosterConfirmedEntry($match);
    $entryB = rosterConfirmedEntry($match);
    $match->entries()->attach([$entryA->id, $entryB->id]);

    return [$match, $entryA, $entryB];
}

test('a manager can add a confirmed, correctly-schooled entry to the roster', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/roster", [
            'entry_id' => $teammate->id,
            'side' => 'a',
            'jersey_number' => '23',
            'is_starter' => true,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('match_roster_players', [
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
        'side' => 'a',
        'jersey_number' => '23',
        'is_starter' => true,
    ]);
});

test('adding an entry from the wrong side\'s school is rejected', function () {
    [$match, , $entryB] = basketballMatchWithSides();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/roster", [
            'entry_id' => $entryB->id,
            'side' => 'a',
        ])
        ->assertSessionHasErrors('entry_id');

    $this->assertDatabaseMissing('match_roster_players', ['entry_id' => $entryB->id]);
});

test('adding a non-confirmed entry is rejected', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $submitted = Entry::factory()->create([
        'event_id' => $match->event_id,
        'delegation_id' => $entryA->delegation_id,
        'athlete_id' => Athlete::factory()->create(['delegation_id' => $entryA->delegation_id])->id,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/matches/{$match->id}/roster", ['entry_id' => $submitted->id, 'side' => 'a'])
        ->assertSessionHasErrors('entry_id');
});

test('an entry cannot be rostered twice', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post("/matches/{$match->id}/roster", ['entry_id' => $teammate->id, 'side' => 'a']);
    $this->actingAs($admin)
        ->post("/matches/{$match->id}/roster", ['entry_id' => $teammate->id, 'side' => 'a'])
        ->assertSessionHasErrors('entry_id');

    expect(MatchRosterPlayer::query()->where('entry_id', $teammate->id)->count())->toBe(1);
});

test('non-managers cannot add a roster player', function (User $user) {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);

    $this->actingAs($user)
        ->post("/matches/{$match->id}/roster", ['entry_id' => $teammate->id, 'side' => 'a'])
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('a manager can update a roster player\'s jersey number and starter flag', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $rosterPlayer = MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/match-roster/{$rosterPlayer->id}", ['jersey_number' => '7', 'is_starter' => true])
        ->assertSessionHasNoErrors();

    expect($rosterPlayer->fresh())
        ->jersey_number->toBe('7')
        ->is_starter->toBeTrue();
});

test('a manager can remove a roster player with no live stats', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $rosterPlayer = MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete("/match-roster/{$rosterPlayer->id}");

    $this->assertDatabaseMissing('match_roster_players', ['id' => $rosterPlayer->id]);
});

test('a roster player currently on court cannot be removed', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $rosterPlayer = MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
    ]);
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...basketballInitialSportState(), 'on_court_a' => [$rosterPlayer->id]],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete("/match-roster/{$rosterPlayer->id}");

    $this->assertDatabaseHas('match_roster_players', ['id' => $rosterPlayer->id]);
});

// Basketball live-state endpoints (settings, possession, clocks, horn, lineup)

test('a manager can update basketball game settings', function () {
    [$match] = basketballMatchWithSides();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'minutes_per_period' => 12,
            'shot_clock_duration' => 30,
            'team_color_a' => '#111111',
            'team_color_b' => '#222222',
            'quarters' => 2,
        ])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state)
        ->toMatchArray([
            'minutes_per_period' => 12,
            'shot_clock_duration' => 30,
            'team_color_a' => '#111111',
            'team_color_b' => '#222222',
            'quarters' => 2,
        ]);
});

test('settings rejects a quarters value other than 2 or 4', function () {
    [$match] = basketballMatchWithSides();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'minutes_per_period' => 10,
            'shot_clock_duration' => 24,
            'team_color_a' => '#111111',
            'team_color_b' => '#222222',
            'quarters' => 3,
        ])
        ->assertSessionHasErrors('quarters');
});

test('settings is rejected for a non-basketball session', function () {
    $session = ScoringSession::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/scoring-sessions/{$session->id}/settings", [
            'minutes_per_period' => 10,
            'shot_clock_duration' => 24,
            'team_color_a' => '#111111',
            'team_color_b' => '#222222',
            'quarters' => 4,
        ])
        ->assertStatus(422);
});

test('a manager can set and clear the possession arrow', function () {
    [$match] = basketballMatchWithSides();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/possession", ['side' => 'a']);
    expect($session->fresh()->sport_state['possession'])->toBe('a');

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/possession", ['side' => null]);
    expect($session->fresh()->sport_state['possession'])->toBeNull();
});

test('a manager can set the game clock', function () {
    [$match] = basketballMatchWithSides();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/game-clock", ['seconds' => 450])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->sport_state['game_clock_seconds'])->toBe(450);
});

test('the shot clock resets to the configured duration when no seconds are given', function () {
    [$match] = basketballMatchWithSides();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...basketballInitialSportState(), 'shot_clock_seconds' => 3, 'shot_clock_duration' => 24],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/shot-clock", []);

    expect($session->fresh()->sport_state['shot_clock_seconds'])->toBe(24);
});

test('a manager can sound the horn and it is recorded in play-by-play', function () {
    [$match] = basketballMatchWithSides();
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/horn", []);

    expect($session->fresh()->sport_state['horn_sounded_at'])->not->toBeNull()
        ->and($session->fresh()->playByPlay()[0]['description'])->toBe('Horn sounded');
});

test('a manager can send a roster player to court and bench them again, capped at 5', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $session = ScoringSession::factory()->paused()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $players = collect(range(1, 6))->map(function () use ($match, $entryA) {
        $teammate = rosterTeammateEntry($match, $entryA);

        return MatchRosterPlayer::factory()->side('a')->create([
            'match_id' => $match->id,
            'entry_id' => $teammate->id,
        ]);
    });

    foreach ($players->take(5) as $player) {
        $this->actingAs($admin)
            ->patch("/scoring-sessions/{$session->id}/lineup", [
                'side' => 'a',
                'roster_player_id' => $player->id,
                'on_court' => true,
            ])
            ->assertSessionHasNoErrors();
    }

    expect($session->fresh()->sport_state['on_court_a'])->toHaveCount(5);

    // A 6th player can't join without someone benching first.
    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/lineup", [
            'side' => 'a',
            'roster_player_id' => $players->last()->id,
            'on_court' => true,
        ])
        ->assertSessionHasErrors('roster_player_id');

    // Bench the first player, freeing a slot.
    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/lineup", [
        'side' => 'a',
        'roster_player_id' => $players->first()->id,
        'on_court' => false,
    ]);

    expect($session->fresh()->sport_state['on_court_a'])->toHaveCount(4);
});

test('a substitution is rejected unless the session is paused', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $rosterPlayer = MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
    ]);
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/lineup", [
            'side' => 'a',
            'roster_player_id' => $rosterPlayer->id,
            'on_court' => true,
        ])
        ->assertSessionHasErrors('status');

    expect($session->fresh()->sport_state['on_court_a'])->toBeEmpty();
});

test('recording a point attributed to a roster player updates player_points and names the player in play-by-play', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $rosterPlayer = MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
    ]);
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", [
            'type' => 'point',
            'side' => 'a',
            'delta' => 2,
            'roster_player_id' => $rosterPlayer->id,
        ])
        ->assertSessionHasNoErrors();

    $fresh = $session->fresh();

    expect($fresh->sport_state['player_points'][(string) $rosterPlayer->id])->toBe(2)
        ->and($fresh->playByPlay()[0]['description'])->toContain($teammate->athlete->fullName());
});

test('recording a foul attributed to a roster player updates player_fouls', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $rosterPlayer = MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
    ]);
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => basketballInitialSportState(),
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/scoring-sessions/{$session->id}/foul", [
        'action' => 'add',
        'side' => 'a',
        'roster_player_id' => $rosterPlayer->id,
    ]);

    expect($session->fresh()->sport_state['player_fouls'][(string) $rosterPlayer->id])->toBe(1);
});

test('the on-demand roster endpoint exposes the full roster and eligible athletes pool, fetched only when requested', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $rosterPlayer = MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson("/matches/{$match->id}/roster")
        ->assertOk()
        ->assertJsonPath('roster.a.0.id', $rosterPlayer->id)
        // entryA is the school's own representative entry — it was never
        // rostered by this test, so it's still a legitimate eligible pick
        // alongside $teammate's own school B counterpart.
        ->assertJsonCount(1, 'eligibleAthletes.a')
        ->assertJsonCount(1, 'eligibleAthletes.b');
});

test('non-managers cannot fetch the on-demand roster endpoint', function (User $user) {
    [$match] = basketballMatchWithSides();

    $this->actingAs($user)
        ->getJson("/matches/{$match->id}/roster")
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('the board Inertia page and live payload only ever expose on-court players, not the full roster', function () {
    [$match, $entryA] = basketballMatchWithSides();
    $teammate = rosterTeammateEntry($match, $entryA);
    $onCourtPlayer = MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $teammate->id,
    ]);
    $benchTeammate = rosterTeammateEntry($match, $entryA);
    MatchRosterPlayer::factory()->side('a')->create([
        'match_id' => $match->id,
        'entry_id' => $benchTeammate->id,
    ]);
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => [...basketballInitialSportState(), 'on_court_a' => [$onCourtPlayer->id]],
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get("/matches/{$match->id}/scoreboard")
        ->assertInertia(fn ($page) => $page
            ->missing('roster')
            ->missing('eligibleAthletes')
            ->has('session.onCourt.a', 1)
            ->where('session.onCourt.a.0.id', $onCourtPlayer->id)
            ->has('session.onCourt.b', 0));

    $this->actingAs($admin)
        ->getJson("/matches/{$match->id}/scoring-session")
        ->assertOk()
        ->assertJsonCount(1, 'session.onCourt.a')
        ->assertJsonMissingPath('session.roster');
});
