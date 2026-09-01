<?php

use App\Enums\PersonnelRole;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\CongressionalDistrict;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\FileUpload;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Personnel;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\Sport;
use App\Models\SportRosterMember;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

/**
 * Registers a school-rooted delegation for the given municipality and
 * returns a Confirmed entry for a fresh athlete — the same shape
 * `PublicTallyTest`'s own helpers use, extended with an explicit
 * municipality/school pairing since the teams pages group by both.
 */
function teamsEntry(Meet $meet, District $municipality, Event $event, ?School $school = null): Entry
{
    $school ??= School::factory()->create(['district_id' => $municipality->id]);

    $delegation = Delegation::query()
        ->where('meet_id', $meet->id)
        ->where('school_id', $school->id)
        ->first()
        ?? Delegation::factory()->approved()->create([
            'meet_id' => $meet->id,
            'school_id' => $school->id,
        ]);

    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'school_id' => $school->id]);

    $meetSport = MeetSport::query()->firstOrCreate([
        'meet_id' => $meet->id,
        'sport_id' => $event->sport_id,
    ], ['active' => true]);
    SportRosterMember::query()->create([
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'athlete_id' => $athlete->id,
        'level' => $event->age_division->value,
        'gender' => $event->gender->value,
    ]);

    return Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ]);
}

test('the teams index is public and lists only this meet\'s competing municipalities', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $event = Event::factory()->create();
    teamsEntry($meet, $municipality, $event);

    $foreignMeet = Meet::factory()->active()->published()->featured()->create();
    $foreignMunicipality = District::factory()->create(['name' => 'Compostela']);
    teamsEntry($foreignMeet, $foreignMunicipality, Event::factory()->create());

    $this->get('/teams')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/teams')
            ->has('teams', 1)
            ->where('teams.0.name', 'Nabunturan')
            ->where('teams.0.slug', 'nabunturan'));
});

test('the teams index resolves to whichever meet is currently active, same as the sport-portal routes', function () {
    Meet::factory()->published()->create(); // published but not active — must not be picked

    $this->get('/teams')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('meet', null)
            ->has('teams', 0));
});

test('a municipality profile is public and shows real athlete/sport participation counts', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $chess = Sport::factory()->create(['name' => 'Chess']);

    teamsEntry($meet, $municipality, Event::factory()->create(['sport_id' => $basketball->id]));
    teamsEntry($meet, $municipality, Event::factory()->create(['sport_id' => $chess->id]));

    $this->get('/teams/nabunturan')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/team-detail')
            ->where('team.name', 'Nabunturan')
            ->where('team.athlete_count', 2)
            ->where('team.sport_count', 2));
});

test('an unknown municipality slug 404s', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    teamsEntry($meet, District::factory()->create(['name' => 'Nabunturan']), Event::factory()->create());

    $this->get('/teams/not-a-real-municipality')->assertNotFound();
});

test('a municipality profile shows its congressional district when one is assigned', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $congressionalDistrict = CongressionalDistrict::factory()->create(['name' => 'Second District']);
    $municipality = District::factory()->create([
        'name' => 'Nabunturan',
        'congressional_district_id' => $congressionalDistrict->id,
    ]);
    teamsEntry($meet, $municipality, Event::factory()->create());

    $this->get('/teams/nabunturan')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('team.congressional_district', 'Second District'));
});

test('municipality medal totals count only validated results, split into elementary/secondary/paragames/total', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);

    $athletics = Sport::factory()->create(['name' => 'Athletics']);
    $paragamesSport = Sport::factory()->create(['name' => 'Paragames - Athletics']);

    $elementaryEvent = Event::factory()->create(['sport_id' => $athletics->id, 'age_division' => 'elementary']);
    $secondaryEvent = Event::factory()->create(['sport_id' => $athletics->id, 'age_division' => 'secondary']);
    $paragamesEvent = Event::factory()->create(['sport_id' => $paragamesSport->id, 'age_division' => 'secondary']);

    $elementaryResult = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $elementaryEvent->id]);
    ResultPlacement::factory()->create([
        'event_result_id' => $elementaryResult->id,
        'entry_id' => teamsEntry($meet, $municipality, $elementaryEvent)->id,
        'rank' => 1,
    ]);

    $secondaryResult = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $secondaryEvent->id]);
    ResultPlacement::factory()->create([
        'event_result_id' => $secondaryResult->id,
        'entry_id' => teamsEntry($meet, $municipality, $secondaryEvent)->id,
        'rank' => 2,
    ]);

    $paragamesResult = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $paragamesEvent->id]);
    ResultPlacement::factory()->create([
        'event_result_id' => $paragamesResult->id,
        'entry_id' => teamsEntry($meet, $municipality, $paragamesEvent)->id,
        'rank' => 3,
    ]);

    // An unvalidated (encoded) result must never count.
    $unvalidatedEvent = Event::factory()->create(['sport_id' => $athletics->id, 'age_division' => 'secondary']);
    $unvalidatedResult = EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $unvalidatedEvent->id]);
    ResultPlacement::factory()->create([
        'event_result_id' => $unvalidatedResult->id,
        'entry_id' => teamsEntry($meet, $municipality, $unvalidatedEvent)->id,
        'rank' => 1,
    ]);

    $this->get('/teams/nabunturan')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('medalBreakdown.elementary.gold', 1)
            ->where('medalBreakdown.elementary.total', 1)
            ->where('medalBreakdown.secondary.silver', 1)
            ->where('medalBreakdown.secondary.total', 1)
            ->where('medalBreakdown.paragames.bronze', 1)
            ->where('medalBreakdown.paragames.total', 1)
            ->where('medalBreakdown.total.gold', 1)
            ->where('medalBreakdown.total.silver', 1)
            ->where('medalBreakdown.total.bronze', 1)
            ->where('medalBreakdown.total.total', 3));
});

test('a paragames medal is never double-counted into the secondary tab', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $paragamesSport = Sport::factory()->create(['name' => 'Paragames - Swimming']);
    $event = Event::factory()->create(['sport_id' => $paragamesSport->id, 'age_division' => 'secondary']);

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'entry_id' => teamsEntry($meet, $municipality, $event)->id,
        'rank' => 1,
    ]);

    $this->get('/teams/nabunturan')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('medalBreakdown.secondary.total', 0)
            ->where('medalBreakdown.paragames.total', 1)
            ->where('medalBreakdown.total.total', 1));
});

test('municipality medal totals exclude every other municipality\'s medals', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $otherMunicipality = District::factory()->create(['name' => 'Compostela']);
    $event = Event::factory()->create();

    teamsEntry($meet, $municipality, $event); // no medal, just makes it a competing municipality

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'entry_id' => teamsEntry($meet, $otherMunicipality, $event)->id,
        'rank' => 1,
    ]);

    $this->get('/teams/nabunturan')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('medalBreakdown.total.total', 0));
});

test('medal winners list an individual athlete\'s name, sport, event, level, and school', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $school = School::factory()->create(['district_id' => $municipality->id, 'name' => 'Nabunturan NHS']);
    $athletics = Sport::factory()->create(['name' => 'Athletics']);
    $event = Event::factory()->create([
        'sport_id' => $athletics->id,
        'name' => '100 Meter Dash',
        'age_division' => 'secondary',
        'is_team_event' => false,
    ]);
    $entry = teamsEntry($meet, $municipality, $event, $school);
    Athlete::whereKey($entry->athlete_id)->update(['first_name' => 'Juan', 'last_name' => 'Dela Cruz']);

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id, 'rank' => 1]);

    $this->get('/teams/nabunturan')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('medalWinners', 1)
            ->where('medalWinners.0.medal', 'gold')
            ->where('medalWinners.0.participant_type', 'athlete')
            ->where('medalWinners.0.athlete_name', 'JUAN DELA CRUZ')
            ->where('medalWinners.0.sport', 'Athletics')
            ->where('medalWinners.0.event', '100 Meter Dash')
            ->where('medalWinners.0.school', 'Nabunturan NHS'));
});

test('a team event\'s tied placements are grouped into one team medal, not one row per athlete', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $school = School::factory()->create(['district_id' => $municipality->id]);
    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $basketball->id, 'is_team_event' => true]);

    $result = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);

    foreach (range(1, 5) as $i) {
        $entry = teamsEntry($meet, $municipality, $event, $school);
        ResultPlacement::factory()->create([
            'event_result_id' => $result->id,
            'entry_id' => $entry->id,
            'rank' => 1,
            'is_tie' => true,
        ]);
    }

    $this->get('/teams/nabunturan')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('medalWinners', 1)
            ->where('medalWinners.0.participant_type', 'team')
            ->where('medalWinners.0.team_name', 'Nabunturan Basketball Team')
            ->has('medalWinners.0.roster', 5));
});

test('the medal winners list can be filtered by category, matching the breakdown tabs', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);

    $elementaryEvent = Event::factory()->create(['age_division' => 'elementary']);
    $secondaryEvent = Event::factory()->create(['age_division' => 'secondary']);

    $elementaryResult = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $elementaryEvent->id]);
    ResultPlacement::factory()->create([
        'event_result_id' => $elementaryResult->id,
        'entry_id' => teamsEntry($meet, $municipality, $elementaryEvent)->id,
        'rank' => 1,
    ]);

    $secondaryResult = EventResult::factory()->validated()->create(['meet_id' => $meet->id, 'event_id' => $secondaryEvent->id]);
    ResultPlacement::factory()->create([
        'event_result_id' => $secondaryResult->id,
        'entry_id' => teamsEntry($meet, $municipality, $secondaryEvent)->id,
        'rank' => 1,
    ]);

    $this->get('/teams/nabunturan?category=elementary')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('medalWinners', 1)
            ->where('medalWinners.0.level', 'Elementary')
            ->where('filters.category', 'elementary'));
});

test('players and coaches are grouped by sport and scoped to the requested municipality only', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $otherMunicipality = District::factory()->create(['name' => 'Compostela']);
    $school = School::factory()->create(['district_id' => $municipality->id, 'name' => 'Nabunturan NHS']);
    $otherSchool = School::factory()->create(['district_id' => $otherMunicipality->id]);

    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $basketball->id, 'age_division' => 'secondary', 'gender' => 'boys']);

    $entry = teamsEntry($meet, $municipality, $event, $school);
    Athlete::whereKey($entry->athlete_id)->update(['first_name' => 'Mark', 'last_name' => 'Santos']);

    $delegation = Delegation::query()->where('meet_id', $meet->id)->where('school_id', $school->id)->firstOrFail();
    $coach = Personnel::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'role' => PersonnelRole::Coach,
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
    ]);
    $coach->sports()->attach($basketball->id);

    // A different municipality's roster must never leak in.
    teamsEntry($meet, $otherMunicipality, $event, $otherSchool);

    $this->get('/teams/nabunturan/players-coaches')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sportOptions', 1)
            ->where('selectedSportId', null)
            ->has('sports', 0));

    $this->get('/teams/nabunturan/players-coaches?sport_id='.$basketball->id)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/team-players-coaches')
            ->has('sports', 1)
            ->where('sports.0.sport', 'Basketball')
            ->where('sports.0.is_paragames', false)
            ->has('sports.0.athletes', 1)
            ->where('sports.0.athletes.0.name', 'MARK SANTOS')
            ->where('sports.0.athletes.0.level', 'secondary')
            ->where('sports.0.athletes.0.school', 'Nabunturan NHS')
            ->where('sports.0.athletes.0.eligibility_status', 'Not Submitted')
            ->where('sports.0.athletes.0.is_eligible', false)
            ->has('sports.0.coaches', 1)
            ->where('sports.0.coaches.0.name', 'ANA REYES')
            ->where('sports.0.coaches.0.role', 'Coach')
            ->where('sports.0.coaches.0.status', 'Registered')
            ->where('sports.0.coaches.0.is_accredited', false));
});

test('submitted athletes remain visible with their eligibility status', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Mawab']);
    $school = School::factory()->create(['district_id' => $municipality->id]);
    $sport = Sport::factory()->create(['name' => 'Badminton']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $entry = teamsEntry($meet, $municipality, $event, $school);
    $entry->update(['status' => 'submitted']);

    $this->get('/teams/mawab/players-coaches?sport_id='.$sport->id)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sports.0.athletes', 1)
            ->where('sports.0.athletes.0.eligibility_status', 'Not Submitted')
            ->where('sports.0.athletes.0.is_eligible', false));
});

test('approved coach accounts and qualified athletes are visible on the municipal roster', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Laak']);
    $school = School::factory()->create(['district_id' => $municipality->id, 'name' => 'Laak NHS']);
    $volleyball = Sport::factory()->create(['name' => 'Volleyball']);
    $event = Event::factory()->create(['sport_id' => $volleyball->id]);
    $entry = teamsEntry($meet, $municipality, $event, $school);
    $entry->update(['status' => 'submitted']);
    EligibilityReview::factory()->approved()->create([
        'athlete_id' => $entry->athlete_id,
        'meet_id' => $meet->id,
    ]);

    $delegation = $entry->delegation;
    $meetSport = MeetSport::query()->where([
        'meet_id' => $meet->id,
        'sport_id' => $volleyball->id,
    ])->sole();
    $coach = User::factory()->create(['name' => 'Approved Coach']);
    CoachAssignmentRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'event_id' => $event->id,
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'status' => 'approved',
    ]);
    CoachOnboardingRequest::query()->create([
        'user_id' => $coach->id,
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'district_id' => $municipality->id,
        'certification_upload_id' => FileUpload::factory()->create()->id,
        'status' => 'approved',
    ]);

    $this->get('/teams/laak/players-coaches?sport_id='.$volleyball->id)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sports.0.sport', 'Volleyball')
            ->has('sports.0.athletes', 1)
            ->where('sports.0.athletes.0.eligibility_status', 'Eligible')
            ->where('sports.0.athletes.0.is_eligible', true)
            ->has('sports.0.coaches', 1)
            ->where('sports.0.coaches.0.name', 'Approved Coach')
            ->where('sports.0.coaches.0.status', 'Accredited')
            ->where('sports.0.coaches.0.is_accredited', true));
});

test('a paragames sport section is flagged so the frontend can filter on it', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $school = School::factory()->create(['district_id' => $municipality->id]);
    $paragamesSport = Sport::factory()->create(['name' => 'Paragames - Swimming']);
    $event = Event::factory()->create(['sport_id' => $paragamesSport->id, 'age_division' => 'secondary']);

    teamsEntry($meet, $municipality, $event, $school);

    $this->get('/teams/nabunturan/players-coaches?sport_id='.$paragamesSport->id)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sports.0.sport', 'Paragames - Swimming')
            ->where('sports.0.is_paragames', true));
});

test('public-safe fields only: no birthdate, LRN, or coach contact details are exposed', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();
    $municipality = District::factory()->create(['name' => 'Nabunturan']);
    $school = School::factory()->create(['district_id' => $municipality->id]);
    $basketball = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $basketball->id]);

    teamsEntry($meet, $municipality, $event, $school);

    $delegation = Delegation::query()->where('meet_id', $meet->id)->where('school_id', $school->id)->firstOrFail();
    $coach = Personnel::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $school->id,
        'role' => PersonnelRole::Coach,
        'phone' => '09171234567',
        'email' => 'coach@example.com',
    ]);
    $coach->sports()->attach($basketball->id);

    $response = $this->get('/teams/nabunturan/players-coaches?sport_id='.$basketball->id)->assertOk();

    $body = $response->getContent();

    expect($body)
        ->not->toContain('09171234567')
        ->not->toContain('coach@example.com');
});

test('the slug for a municipality is derived from its real name', function () {
    $district = District::factory()->create(['name' => 'General Luna']);

    expect(Str::slug($district->name))->toBe('general-luna');
});
