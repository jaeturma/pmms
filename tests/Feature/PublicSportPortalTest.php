<?php

use App\Enums\MeetSportAssignmentRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Person;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

/**
 * Attach two confirmed entries (each a different school) to a match —
 * the real "suggested labels" source `PortalController::
 * matchCompetitors()` falls back to before any scoring session exists.
 */
function attachTwoCompetitors(EventMatch $match): void
{
    foreach (['Home School', 'Away School'] as $name) {
        $school = School::factory()->create(['name' => $name]);
        $delegation = Delegation::factory()->approved()->create([
            'meet_id' => $match->meet_id,
            'school_id' => $school->id,
        ]);
        $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $school->id]);
        $entry = Entry::factory()->confirmed()->create([
            'athlete_id' => $athlete->id,
            'delegation_id' => $delegation->id,
            'event_id' => $match->event_id,
        ]);
        $match->entries()->attach($entry->id);
    }
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function basketballSport(): Sport
{
    return Sport::query()->firstOrCreate(['name' => 'Basketball']);
}

test('guests can view a sport portal for the active meet; unknown slugs 404', function () {
    auth()->logout();
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    Event::factory()->create(['sport_id' => $sport->id]);

    $this->get('/basketball')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/sport-portal')
            ->where('sport.slug', 'basketball')
            ->where('sport.name', 'Basketball')
            ->where('canonicalUrl', url('/basketball'))
            ->where('meet.name', $meet->name)
            ->where('liveNow', null)
            ->where('otherLiveCount', 0)
            ->has('todayGames', 0)
            ->has('completedGames', 0)
            ->has('upcomingGames', 0)
            ->has('venues', 0));

    $this->get('/not-a-real-sport')->assertNotFound();
});

test('with no active meet, the portal shows an empty state instead of erroring', function () {
    basketballSport();

    $this->get('/basketball')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/sport-portal')
            ->where('canonicalUrl', url('/basketball'))
            ->where('meet', null)
            ->where('liveNow', null));
});

test('a live match for this sport appears as liveNow with a real session payload', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'round_label' => 'Finals',
    ]);
    ScoringSession::factory()->create([
        'match_id' => $match->id,
        'side_a_label' => 'Home Team',
        'side_b_label' => 'Away Team',
        'score_a' => 42,
        'score_b' => 38,
    ]);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('liveNow.match_id', $match->id)
            ->where('liveNow.round_label', 'Finals')
            ->where('liveNow.session.side_a_label', 'Home Team')
            ->where('liveNow.session.score_a', 42)
            ->where('otherLiveCount', 0));
});

test('multiple simultaneous live matches count the extras', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $event = Event::factory()->create(['sport_id' => $sport->id]);

    $matchA = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    $matchB = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ScoringSession::factory()->create(['match_id' => $matchA->id]);
    ScoringSession::factory()->create(['match_id' => $matchB->id]);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('otherLiveCount', 1));
});

test('an ended session never appears as liveNow', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ScoringSession::factory()->ended()->create(['match_id' => $match->id]);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('liveNow', null)
            ->where('otherLiveCount', 0));
});

test("today's, completed, and upcoming games are classified correctly with real competitor names", function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $venue = Venue::factory()->create(['name' => 'Main Gym']);

    $todayEvent = Event::factory()->create(['sport_id' => $sport->id]);
    $todaySchedule = EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $todayEvent->id,
        'venue_id' => $venue->id,
        'scheduled_date' => today()->toDateString(),
        'starts_at' => '09:00:00',
    ]);
    $todayMatch = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $todayEvent->id,
        'event_schedule_id' => $todaySchedule->id,
    ]);
    attachTwoCompetitors($todayMatch);

    $completedEvent = Event::factory()->create(['sport_id' => $sport->id]);
    $completedMatch = EventMatch::factory()->completed()->create([
        'meet_id' => $meet->id,
        'event_id' => $completedEvent->id,
    ]);
    ScoringSession::factory()->ended()->create([
        'match_id' => $completedMatch->id,
        'side_a_label' => 'Winners',
        'side_b_label' => 'Runners-up',
        'score_a' => 55,
        'score_b' => 50,
    ]);

    $upcomingEvent = Event::factory()->create(['sport_id' => $sport->id]);
    $upcomingSchedule = EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $upcomingEvent->id,
        'venue_id' => $venue->id,
        'scheduled_date' => today()->addDays(3)->toDateString(),
    ]);
    $upcomingMatch = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $upcomingEvent->id,
        'event_schedule_id' => $upcomingSchedule->id,
    ]);
    attachTwoCompetitors($upcomingMatch);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('todayGames', 1)
            ->where('todayGames.0.id', $todayMatch->id)
            ->where('todayGames.0.side_a', 'Home School')
            ->where('todayGames.0.side_b', 'Away School')
            ->where('todayGames.0.venue', 'Main Gym')
            ->has('completedGames', 1)
            ->where('completedGames.0.side_a', 'Winners')
            ->where('completedGames.0.score_a', 55)
            ->has('upcomingGames', 1)
            ->where('upcomingGames.0.id', $upcomingMatch->id)
            ->has('venues', 1)
            ->where('venues.0.name', 'Main Gym'));
});

test('games from a different sport never appear on this sport\'s portal', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    basketballSport();
    $volleyball = Sport::factory()->create(['name' => 'Volleyball']);
    $event = Event::factory()->create(['sport_id' => $volleyball->id]);
    EventMatch::factory()->completed()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('completedGames', 0));
});

test('games from a different (non-active) meet never appear', function () {
    Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $foreignMeet = Meet::factory()->published()->create(['is_active' => false]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    EventMatch::factory()->completed()->create(['meet_id' => $foreignMeet->id, 'event_id' => $event->id]);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('completedGames', 0));
});

test('the sport portal poll endpoint returns the same liveNow shape', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ScoringSession::factory()->create(['match_id' => $match->id, 'score_a' => 10, 'score_b' => 8]);

    $response = $this->get('/basketball/poll')->assertOk();
    $response->assertJsonPath('liveNow.match_id', $match->id);
    $response->assertJsonPath('liveNow.session.score_a', 10);
    $response->assertJsonPath('otherLiveCount', 0);
});

test('game rows carry no internal or restricted athlete fields', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $venue = Venue::factory()->create();
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $schedule = EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'venue_id' => $venue->id,
        'scheduled_date' => today()->toDateString(),
    ]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'event_schedule_id' => $schedule->id,
    ]);
    attachTwoCompetitors($match);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('todayGames.0', fn (AssertableInertia $row) => $row
                ->hasAll(['id', 'round_label', 'status', 'status_label', 'category', 'venue', 'scheduled_date', 'starts_at', 'side_a', 'side_b', 'score_a', 'score_b', 'mark'])
                ->missing('birthdate')
                ->missing('lrn')
                ->missing('grade_level')));
});

test("today's games are capped at 10, ordered by start time", function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();

    foreach (range(1, 12) as $i) {
        $event = Event::factory()->create(['sport_id' => $sport->id]);
        $schedule = EventSchedule::factory()->create([
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'scheduled_date' => today()->toDateString(),
            'starts_at' => sprintf('%02d:00:00', $i),
        ]);
        EventMatch::factory()->create([
            'meet_id' => $meet->id,
            'event_id' => $event->id,
            'event_schedule_id' => $schedule->id,
            'round_label' => "Game {$i}",
        ]);
    }

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('todayGames', 10)
            ->where('todayGames.0.round_label', 'Game 1')
            ->where('todayGames.9.round_label', 'Game 10'));
});

test('an active-but-unpublished meet is treated as no active meet at all', function () {
    Meet::factory()->active()->featured()->create();
    basketballSport();

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('meet', null)
            ->where('liveNow', null));
});

test("a Basketball live session's board type and sport-specific fouls state flow through unchanged", function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['fouls_a' => 3, 'fouls_b' => 5],
    ]);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('liveNow.session.board_type', 'basketball')
            ->where('liveNow.session.sport_state.fouls_a', 3)
            ->where('liveNow.session.sport_state.fouls_b', 5));
});

test('every sport portal resolves its own real sport, isolated from every other sport', function (string $slug, string $sportName) {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->firstOrCreate(['name' => $sportName]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->completed()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ScoringSession::factory()->ended()->create(['match_id' => $match->id]);

    // A match belonging to a different sport must never leak into this
    // sport's own game lists — same real-data isolation WP-12-02 first
    // proved for Basketball, now checked for all 11 other real routes.
    $otherSportName = $sportName === 'Chess' ? 'Basketball' : 'Chess';
    $otherSport = Sport::query()->firstOrCreate(['name' => $otherSportName]);
    $otherEvent = Event::factory()->create(['sport_id' => $otherSport->id]);
    EventMatch::factory()->completed()->create(['meet_id' => $meet->id, 'event_id' => $otherEvent->id]);

    $this->get("/{$slug}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/sport-portal')
            ->where('sport.slug', $slug)
            ->where('sport.name', $sportName)
            ->where('canonicalUrl', url("/{$slug}"))
            ->has('completedGames', 1));
})->with([
    ['volleyball', 'Volleyball'],
    ['baseball', 'Baseball'],
    ['softball', 'Softball'],
    ['football', 'Football'],
    ['sepak-takraw', 'Sepak Takraw'],
    ['badminton', 'Badminton'],
    ['table-tennis', 'Table Tennis'],
    ['chess', 'Chess'],
    ['boxing', 'Boxing'],
    // Athletics/Swimming excluded here (WP-12-05): they no longer read
    // from `EventMatch` at all — their own real data model (`EventSchedule`/
    // `EventResult`) is verified by the dedicated test below instead.
]);

test('the live board type follows the real sport — dedicated boards where they exist, generic otherwise', function (string $sportName, string $expectedBoardType) {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->firstOrCreate(['name' => $sportName]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ScoringSession::factory()->create(['match_id' => $match->id]);

    $slug = Str::slug($sportName);

    $this->get("/{$slug}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('liveNow.session.board_type', $expectedBoardType));
})->with([
    ['Boxing', 'boxing'],
    ['Baseball', 'softball_baseball'],
    ['Softball', 'softball_baseball'],
    ['Volleyball', 'volleyball_sepak_takraw'],
    ['Sepak Takraw', 'volleyball_sepak_takraw'],
    ['Football', 'football_futsal'],
    ['Table Tennis', 'racket_games'],
    ['Badminton', 'racket_games'],
    ['Chess', 'generic'],
]);

test('Athletics/Swimming events use real schedule/result data, never a fabricated match', function (string $slug, string $sportName) {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->firstOrCreate(['name' => $sportName]);

    $upcomingEvent = Event::factory()->create(['sport_id' => $sport->id]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $upcomingEvent->id,
        'scheduled_date' => today()->addDays(2)->toDateString(),
    ]);

    $completedEvent = Event::factory()->create(['sport_id' => $sport->id]);
    $result = EventResult::factory()->validated()->create([
        'meet_id' => $meet->id,
        'event_id' => $completedEvent->id,
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $completedEvent->id,
        'scheduled_date' => today()->subDay()->toDateString(),
    ]);

    $school = School::factory()->create(['name' => 'Nabunturan Central School']);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id, 'school_id' => $school->id]);
    $athlete = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
    ]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $completedEvent->id,
    ]);
    ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'entry_id' => $entry->id,
        'rank' => 1,
        'mark' => '10.45s',
    ]);

    $this->get("/{$slug}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/sport-portal')
            ->where('liveNow', null)
            ->where('otherLiveCount', 0)
            ->has('upcomingGames', 1)
            ->where('upcomingGames.0.side_a', null)
            ->where('upcomingGames.0.side_b', null)
            ->has('completedGames', 1)
            ->where('completedGames.0.side_a', '1st: JUAN DELA CRUZ (Nabunturan Central School)')
            ->where('completedGames.0.side_b', null)
            ->where('completedGames.0.mark', '10.45s')
            ->where('completedGames.0.score_a', null));
})->with([
    ['athletics', 'Athletics'],
    ['swimming', 'Swimming'],
]);

test("a Boxing live session's rounds state flows through unchanged", function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->firstOrCreate(['name' => 'Boxing']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ScoringSession::factory()->create([
        'match_id' => $match->id,
        'sport_state' => ['rounds' => [
            ['round' => 1, 'score_a' => 10, 'score_b' => 9],
        ]],
    ]);

    $this->get('/boxing')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('liveNow.session.board_type', 'boxing')
            ->where('liveNow.session.sport_state.rounds.0.round', 1)
            ->where('liveNow.session.sport_state.rounds.0.score_a', 10));
});

test('a Chess match with no live session never fabricates a score', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::query()->firstOrCreate(['name' => 'Chess']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->completed()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    attachTwoCompetitors($match);

    $this->get('/chess')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sport.slug', 'chess')
            ->has('completedGames', 1)
            ->where('completedGames.0.side_a', 'Home School')
            ->where('completedGames.0.score_a', null)
            ->where('completedGames.0.score_b', null)
            ->where('completedGames.0.mark', null));
});

test('the mini portal profile carries real description/photo/paragames fields from the Sport row', function () {
    Meet::factory()->active()->published()->featured()->create();
    Sport::query()->create([
        'name' => 'Para Athletics',
        'classification' => 'paragames',
        'short_description' => 'A short blurb.',
        'description' => 'A full description.',
    ]);

    $this->get('/paragames-athletics')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sport.slug', 'paragames-athletics')
            ->where('sport.is_paragames', true)
            ->where('sport.short_description', 'A short blurb.')
            ->where('sport.description', 'A full description.')
            ->where('sport.photo_url', null));
});

test('mini portal categories combine catalog-wide and this meet-scoped rows, sorted by name', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);

    SportCategory::factory()->create(['sport_id' => $sport->id, 'meet_sport_id' => null, 'display_name' => 'Zonal Catalog Category']);
    SportCategory::factory()->create(['sport_id' => $sport->id, 'meet_sport_id' => $meetSport->id, 'display_name' => 'Alpha Meet Category']);
    // An inactive category is real data but never shown publicly.
    SportCategory::factory()->create(['sport_id' => $sport->id, 'meet_sport_id' => null, 'active' => false, 'display_name' => 'Hidden Category']);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sport.categories', 2)
            ->where('sport.categories.0.display_name', 'Alpha Meet Category')
            ->where('sport.categories.1.display_name', 'Zonal Catalog Category'));
});

test('tournament management shows real MeetSportAssignment rows, excluding Technical Official, ordered Manager-first', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $category = SportCategory::factory()->create(['sport_id' => $sport->id, 'meet_sport_id' => $meetSport->id]);

    $secretary = User::factory()->create(['name' => 'Secretary Person']);
    $manager = User::factory()->create(['name' => 'Manager Person']);
    $categoryManager = User::factory()->create(['name' => 'Category Manager Person']);
    $technicalOfficial = User::factory()->create(['name' => 'TO Person']);

    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $secretary->id,
        'role' => MeetSportAssignmentRole::TournamentSecretary,
    ]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $manager->id,
        'role' => MeetSportAssignmentRole::TournamentManager,
        'is_lead' => true,
    ]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'sport_category_id' => $category->id,
        'user_id' => $categoryManager->id,
        'role' => MeetSportAssignmentRole::CategoryTournamentManager,
    ]);
    // TechnicalOfficial-role assignments are excluded here — public
    // Technical Officials come from `sport_user` instead.
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $technicalOfficial->id,
        'role' => MeetSportAssignmentRole::TechnicalOfficial,
    ]);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sport.tournament_management', 3)
            ->where('sport.tournament_management.0.name', 'Manager Person')
            ->where('sport.tournament_management.0.role_label', 'Tournament Manager')
            ->where('sport.tournament_management.0.is_lead', true)
            ->where('sport.tournament_management.1.role_label', 'Category Tournament Manager')
            ->where('sport.tournament_management.1.category', $category->display_name)
            ->where('sport.tournament_management.2.role_label', 'Tournament Secretary'));
});

test('technical officials come from sport_user with a real duty label, defaulting to null when unset', function () {
    Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();

    $withDuty = User::factory()->create(['name' => 'Referee Person']);
    $withoutDuty = User::factory()->create(['name' => 'Plain Official']);

    $sport->technicalOfficials()->attach($withDuty->id, ['duty' => 'Referee']);
    $sport->technicalOfficials()->attach($withoutDuty->id);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sport.technical_officials', 2)
            ->where('sport.technical_officials.0.name', 'Plain Official')
            ->where('sport.technical_officials.0.duty', null)
            ->where('sport.technical_officials.1.name', 'Referee Person')
            ->where('sport.technical_officials.1.duty', 'Referee'));
});

test('technical officials include meet assignments backed by imported people without user accounts', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = basketballSport();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    $person = Person::query()->create([
        'source_key' => 'public-to-person',
        'full_name' => 'Imported Official',
        'normalized_name' => 'imported official',
    ]);

    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'person_id' => $person->id,
        'user_id' => null,
        'role' => MeetSportAssignmentRole::TechnicalOfficial,
        'original_designation' => 'ICT / Technical Official',
    ]);

    $this->get('/basketball')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sport.technical_officials', 1)
            ->where('sport.technical_officials.0.name', 'Imported Official')
            ->where('sport.technical_officials.0.duty', 'ICT / Technical Official'));
});

test('sport routes now cover the full 28-sport catalog, not just the original 12', function () {
    Meet::factory()->active()->published()->featured()->create();
    Sport::query()->create(['name' => 'Archery']);

    $this->get('/archery')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sport.slug', 'archery')
            ->where('sport.name', 'Archery'));
});
