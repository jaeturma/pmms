<?php

use App\Enums\MatchStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchRosterPlayer;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * Regression coverage for the Live Scoreboard Control blank-page fix and
 * the manual-setup / participant-override fallback (spec §15). The blank
 * page was `MatchRosterPlayer::groupBySide()` dereferencing a
 * soft-deleted athlete; the fallback lets a Tournament ICT always start
 * the board with hand-supplied participants when relational data is
 * incomplete.
 */
function lscScoreboardMatch(string $sport = 'Basketball', bool $team = true, bool $liveScoring = true): EventMatch
{
    $sportModel = Sport::factory()->create(['name' => $sport]);
    $event = Event::factory()->create(['sport_id' => $sportModel->id, 'is_team_event' => $team]);

    return EventMatch::factory()->create([
        'event_id' => $event->id,
        'status' => MatchStatus::Scheduled,
        'live_scoring_enabled' => $liveScoring,
    ]);
}

function lscIctFor(EventMatch $match): User
{
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $match->meet_id,
        'sport_id' => $match->event->sport_id,
        'active' => true,
    ]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'user_id' => $ict->id,
        'meet_sport_id' => $meetSport->id,
        'role' => 'tournament_ict',
        'status' => 'active',
    ]);

    return $ict;
}

function lscConfirmedEntry(EventMatch $match): Entry
{
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    return Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $match->event_id,
    ]);
}

/** §15.1 — open the scoreboard when a roster row points at a deleted athlete. */
test('opening the scoreboard does not blank when a roster athlete is soft-deleted', function () {
    $match = lscScoreboardMatch();
    $entryA = lscConfirmedEntry($match);
    $entryB = lscConfirmedEntry($match);
    $match->entries()->sync([$entryA->id, $entryB->id]);
    $player = MatchRosterPlayer::factory()->side('a')->create(['match_id' => $match->id, 'entry_id' => $entryA->id]);

    $ict = lscIctFor($match);
    $session = $match->scoringSessions()->create(['side_a_label' => 'A', 'side_b_label' => 'B']);
    $session->forceFill(['sport_state' => ['on_court_a' => [$player->id], 'on_court_b' => []]])->save();

    $athleteName = $entryA->athlete->fullName();
    $entryA->athlete->delete();

    $this->actingAs($ict)->get("/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('session.onCourt.a.0.name', $athleteName));
});

/** §15.2 — a missing athlete never yields a 500 or a blank React tree. */
test('the scoreboard poll stays 200 when a roster athlete is missing', function () {
    $match = lscScoreboardMatch();
    $entryA = lscConfirmedEntry($match);
    $entryB = lscConfirmedEntry($match);
    $match->entries()->sync([$entryA->id, $entryB->id]);
    $player = MatchRosterPlayer::factory()->side('a')->create(['match_id' => $match->id, 'entry_id' => $entryA->id]);

    $ict = lscIctFor($match);
    $session = $match->scoringSessions()->create(['side_a_label' => 'A', 'side_b_label' => 'B']);
    $session->forceFill(['sport_state' => ['on_court_a' => [$player->id], 'on_court_b' => []]])->save();
    $entryA->athlete->delete();

    $response = $this->actingAs($ict)->getJson("/matches/{$match->id}/scoring-session")->assertOk();
    expect($response->json('session.onCourt.a.0.name'))->not->toBeNull();
});

/** §15.3 — no Schedule attached must not block a manual start. */
test('a match with no schedule can still start the scoreboard manually', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    expect($match->event_schedule_id)->toBeNull();
    $ict = lscIctFor($match);

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Red',
        'side_b_label' => 'Blue',
        'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($match->scoringSessions()->count())->toBe(1);
});

/** §15.4 — no Confirmed Entry must not block a manual start. */
test('a match with no confirmed entries can still start the scoreboard manually', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->get("/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('suggestedLabels', [null, null]));

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Team Alpha',
        'side_b_label' => 'Team Bravo',
        'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $session = $match->scoringSessions()->firstOrFail();
    expect($session->side_a_label)->toBe('Team Alpha')
        ->and($session->operationalRemarks())->not->toBeEmpty();
});

/** §15.5 — a team scoreboard starts without a completed team roster. */
test('a team scoreboard starts with no team entries attached', function () {
    $match = lscScoreboardMatch('Basketball', team: true);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Municipality of North',
        'side_b_label' => 'Municipality of South',
        'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($match->scoringSessions()->firstOrFail()->side_a_label)->toBe('Municipality of North');
});

/** §15.6 — the ICT names both sides by hand for a team match. */
test('the ICT can name both competing sides manually for a team match', function () {
    $match = lscScoreboardMatch('Basketball', team: true);
    Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->get("/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('meetDelegationOptions'));

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Pantukan',
        'side_b_label' => 'Nabunturan',
        'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($match->scoringSessions()->firstOrFail()->side_b_label)->toBe('Nabunturan');
});

/** §15.7 — an individual board accepts an optional linked athlete. */
test('an individual scoreboard override accepts an optional athlete link', function () {
    $match = lscScoreboardMatch('Boxing', team: false);
    $ict = lscIctFor($match);
    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Red Corner', 'side_b_label' => 'Blue Corner', 'manual_setup' => true,
    ])->assertRedirect();
    $session = $match->scoringSessions()->firstOrFail();

    $delegation = Delegation::factory()->approved()->create(['meet_id' => $match->meet_id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $this->actingAs($ict)->patch("/scoring-sessions/{$session->id}/participants", [
        'side_a_label' => $athlete->fullName(),
        'side_b_label' => 'Blue Corner',
        'side_a_athlete_id' => $athlete->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->side_a_label)->toBe($athlete->fullName())
        ->and($session->participantProvenance()['side_a']['athlete_id'])->toBe($athlete->id);
});

/** §15.8 — an individual board accepts a manual display name with no athlete. */
test('an individual scoreboard accepts a manual display name when no athlete is available', function () {
    $match = lscScoreboardMatch('Boxing', team: false);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'J. Dela Cruz (Pantukan)',
        'side_b_label' => 'R. Santos (Nabunturan)',
        'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($match->scoringSessions()->firstOrFail()->side_a_label)->toBe('J. Dela Cruz (Pantukan)');
});

/** §15.9 — the ICT overrides stale generated participants at start. */
test('the ICT can override the generated participants when starting the board', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    $entryA = lscConfirmedEntry($match);
    $entryB = lscConfirmedEntry($match);
    $match->entries()->sync([$entryA->id, $entryB->id]);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Corrected Home',
        'side_b_label' => 'Corrected Away',
        'override_participants' => true,
        'override_reason' => 'Schools swapped in the generated match',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $session = $match->scoringSessions()->firstOrFail();
    expect($session->side_a_label)->toBe('Corrected Home')
        ->and($session->side_a_label)->not->toBe($entryA->athlete->school->name)
        ->and($session->participantProvenance()['mode'])->toBe('override')
        ->and($session->participantProvenance()['previous']['a'])->toBe($entryA->athlete->school->name);
});

/** §15.10 — the override is written to the audit trail. */
test('a participant override is recorded in the audit log', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    $ict = lscIctFor($match);
    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'A', 'side_b_label' => 'B', 'manual_setup' => true,
    ])->assertRedirect();
    $session = $match->scoringSessions()->firstOrFail();

    $this->actingAs($ict)->patch("/scoring-sessions/{$session->id}/participants", [
        'side_a_label' => 'Overridden A',
        'side_b_label' => 'Overridden B',
        'reason' => 'Wrong teams',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $log = AuditLog::query()->where('action', 'scoring.participants_overridden')->latest('id')->first();
    expect($log)->not->toBeNull()
        ->and($log->context['previous'])->toBe(['a' => 'A', 'b' => 'B'])
        ->and($log->context['reason'])->toBe('Wrong teams');
    expect(ScoreEvent::query()->where('scoring_session_id', $session->id)->where('type', 'note')->exists())->toBeTrue();
});

/** §15.11 — a manual start creates exactly one scoring session. */
test('a manual start creates exactly one scoring session', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'A', 'side_b_label' => 'B', 'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(ScoringSession::query()->where('match_id', $match->id)->count())->toBe(1);
});

/** §15.12 — a repeated Start does not create a duplicate active session. */
test('a repeated start does not create a duplicate active session', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'A', 'side_b_label' => 'B', 'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'A', 'side_b_label' => 'B', 'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasErrors('match_id');

    expect(ScoringSession::query()->where('match_id', $match->id)->count())->toBe(1);
});

/** §15.13 — a generic fallback board starts for a non-standard sport. */
test('a generic fallback scoreboard can start for a custom sport', function () {
    $match = lscScoreboardMatch('Dragon Boat', team: true, liveScoring: true);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Crew A',
        'side_b_label' => 'Crew B',
        'board_type' => 'generic',
        'manual_setup' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($match->scoringSessions()->firstOrFail()->boardType()->value)->toBe('generic');
});

/** §15.14 — the ordinary two-entry workflow still derives its own labels. */
test('the standard match participant workflow still overrides operator-typed labels', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    $entryA = lscConfirmedEntry($match);
    $entryB = lscConfirmedEntry($match);
    $match->entries()->sync([$entryA->id, $entryB->id]);
    $ict = lscIctFor($match);

    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Ignored A',
        'side_b_label' => 'Ignored B',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $session = $match->scoringSessions()->firstOrFail();
    expect($session->side_a_label)->toBe($entryA->athlete->school->name)
        ->and($session->participantProvenance())->toBeNull();
});

/** §15.15 — an operator outside the sport cannot override participants. */
test('an operator outside the assigned sport cannot override participants', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    $operator = lscIctFor($match);
    $this->actingAs($operator)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'A', 'side_b_label' => 'B', 'manual_setup' => true,
    ])->assertRedirect();
    $session = $match->scoringSessions()->firstOrFail();

    $otherMatch = lscScoreboardMatch('Boxing', team: false);
    $outsider = lscIctFor($otherMatch); // ICT for a different meet+sport

    $this->actingAs($outsider)->patch("/scoring-sessions/{$session->id}/participants", [
        'side_a_label' => 'Hijacked A',
        'side_b_label' => 'Hijacked B',
    ])->assertForbidden();

    expect($session->fresh()->side_a_label)->toBe('A');
});

/** §15.16 — the public scoreboard shows the manual participant labels. */
test('the public scoreboard displays manually supplied participant labels', function () {
    $meet = Meet::factory()->active()->published()->create();
    $sport = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $sport->id, 'is_team_event' => true]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id, 'event_id' => $event->id, 'status' => MatchStatus::Scheduled,
    ]);
    $ict = lscIctFor($match);
    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'Manual Home', 'side_b_label' => 'Manual Away', 'manual_setup' => true,
    ])->assertRedirect();

    $this->actingAs(User::factory()->create())
        ->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('session.side_a_label', 'Manual Home')
            ->where('session.side_b_label', 'Manual Away'));
});

/** §12/§15.16 — operational remarks never reach the public payload. */
test('the public scoreboard payload carries no operational remarks or provenance', function () {
    $meet = Meet::factory()->active()->published()->create();
    $sport = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $sport->id, 'is_team_event' => false]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id, 'event_id' => $event->id, 'status' => MatchStatus::Scheduled,
    ]);
    $ict = lscIctFor($match);
    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'A', 'side_b_label' => 'B', 'manual_setup' => true,
    ])->assertRedirect();

    $this->actingAs(User::factory()->create())
        ->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('session.operational_remarks')
            ->missing('session.participant_provenance'));
});

/** §15.17 — ordinary scoring mutations still work after a manual start. */
test('scoring mutations still work after a manual start', function () {
    $match = lscScoreboardMatch('Basketball', team: false);
    $ict = lscIctFor($match);
    $this->actingAs($ict)->post("/matches/{$match->id}/scoring-sessions", [
        'side_a_label' => 'A', 'side_b_label' => 'B', 'manual_setup' => true,
    ])->assertRedirect();
    $session = $match->scoringSessions()->firstOrFail();

    $this->actingAs($ict)->patch("/scoring-sessions/{$session->id}/score", [
        'type' => 'point', 'side' => 'a', 'delta' => 2,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($session->fresh()->score_a)->toBe(2);
});

/** §15.1 — the substitution roster modal survives a soft-deleted athlete. */
test('the roster modal endpoint stays 200 with a soft-deleted roster athlete', function () {
    $match = lscScoreboardMatch('Basketball', team: true);
    $entryA = lscConfirmedEntry($match);
    $entryB = lscConfirmedEntry($match);
    $match->entries()->sync([$entryA->id, $entryB->id]);
    MatchRosterPlayer::factory()->side('a')->create(['match_id' => $match->id, 'entry_id' => $entryA->id]);
    $ict = lscIctFor($match);
    $athleteName = $entryA->athlete->fullName();
    $entryA->athlete->delete();

    $this->actingAs($ict)->getJson("/matches/{$match->id}/roster")
        ->assertOk()
        ->assertJsonPath('roster.a.0.name', $athleteName);
});
