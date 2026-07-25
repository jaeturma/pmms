<?php

use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Incident;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\Protest;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\User;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia;

/**
 * Place a school in a result at the given rank via a confirmed entry —
 * mirrors the helper in MedalTallyTest.php (test files don't share
 * locally-defined functions, so it's duplicated here).
 */
function placeSchoolInResult(EventResult $result, School $school, int $rank): ResultPlacement
{
    $delegation = Delegation::query()
        ->where('meet_id', $result->meet_id)
        ->where('school_id', $school->id)
        ->first()
        ?? Delegation::factory()->approved()->create([
            'meet_id' => $result->meet_id,
            'school_id' => $school->id,
        ]);

    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $result->event_id,
    ]);

    return ResultPlacement::factory()->create([
        'event_result_id' => $result->id,
        'entry_id' => $entry->id,
        'rank' => $rank,
    ]);
}

test('guests are redirected from the management dashboard', function () {
    $this->get('/management')->assertRedirect('/login');
});

test('delegation officers and viewers are forbidden from the management dashboard', function (User $user) {
    $this->actingAs($user)
        ->get('/management')
        ->assertForbidden();
})->with([
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'viewer' => fn () => User::factory()->create(),
]);

test('admins and organizers can view the management dashboard', function (User $user) {
    Meet::factory()->create(['school_year' => '2025-2026']);

    $this->actingAs($user)
        ->get('/management')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('management/index')
            ->has('meets', 1)
            ->has('schoolYearOptions', 1)
            ->where('filters.school_year', null));
})->with([
    'admin' => fn () => User::factory()->admin()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
]);

test('the school year filter narrows meets in scope', function () {
    Meet::factory()->create(['school_year' => '2024-2025']);
    Meet::factory()->create(['school_year' => '2025-2026']);
    Meet::factory()->create(['school_year' => '2025-2026']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management?school_year=2025-2026')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('meets', 2)
            ->where('filters.school_year', '2025-2026')
            ->where('meets.0.school_year', '2025-2026')
            ->where('meets.1.school_year', '2025-2026'));
});

test('the management dashboard lists no meets when none exist', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('meets', 0)
            ->has('schoolYearOptions', 0));
});

test('participation counts delegations by status and individuals/entries per meet', function () {
    $meet = Meet::factory()->create();

    Delegation::factory()->create(['meet_id' => $meet->id]);
    Delegation::factory()->submitted()->create(['meet_id' => $meet->id]);
    $approved = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);

    $athlete = Athlete::factory()->create(['delegation_id' => $approved->id]);
    Personnel::factory()->create(['delegation_id' => $approved->id]);
    Entry::factory()->create(['athlete_id' => $athlete->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('participation.rows', 1)
            ->where('participation.rows.0.delegations.draft', 1)
            ->where('participation.rows.0.delegations.submitted', 1)
            ->where('participation.rows.0.delegations.approved', 1)
            ->where('participation.rows.0.delegations.total', 3)
            ->where('participation.rows.0.athletes', 1)
            ->where('participation.rows.0.personnel', 1)
            ->where('participation.rows.0.entries', 1)
            ->where('participation.totals.delegations', 3)
            ->where('participation.totals.athletes', 1)
            ->where('participation.totals.personnel', 1)
            ->where('participation.totals.entries', 1));
});

test('a different meet\'s delegations and individuals never leak into another meet\'s row', function () {
    $newerMeet = Meet::factory()->create(['starts_at' => now()->addMonths(2)->toDateString()]);
    $olderMeet = Meet::factory()->create(['starts_at' => now()->addMonth()->toDateString()]);

    Delegation::factory()->approved()->create(['meet_id' => $newerMeet->id]);
    $olderDelegation = Delegation::factory()->create(['meet_id' => $olderMeet->id]);
    Athlete::factory()->create(['delegation_id' => $olderDelegation->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('participation.rows', 2)
            ->where('participation.rows.0.meet_id', $newerMeet->id)
            ->where('participation.rows.0.delegations.total', 1)
            ->where('participation.rows.0.athletes', 0)
            ->where('participation.rows.1.meet_id', $olderMeet->id)
            ->where('participation.rows.1.delegations.total', 1)
            ->where('participation.rows.1.athletes', 1));
});

test('operations progress counts results, eligibility, protests, and incidents per meet', function () {
    $meet = Meet::factory()->create();
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    EventResult::factory()->create(['meet_id' => $meet->id]);
    $validatedResult = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);

    EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $meet->id]);
    $secondAthlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    EligibilityReview::factory()->returned()->create([
        'athlete_id' => $secondAthlete->id,
        'meet_id' => $meet->id,
    ]);

    Protest::factory()->create(['delegation_id' => $delegation->id, 'event_result_id' => $validatedResult->id]);
    Protest::factory()->upheld()->create(['delegation_id' => $delegation->id, 'event_result_id' => $validatedResult->id]);

    Incident::factory()->create(['meet_id' => $meet->id]);
    Incident::factory()->resolved()->create(['meet_id' => $meet->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('operations', 1)
            ->where('operations.0.results.encoded', 1)
            ->where('operations.0.results.validated', 1)
            ->where('operations.0.eligibility.pending', 1)
            ->where('operations.0.eligibility.returned', 1)
            ->where('operations.0.protests.filed', 1)
            ->where('operations.0.protests.upheld', 1)
            ->where('operations.0.incidents.open', 1)
            ->where('operations.0.incidents.resolved', 1));
});

test('a meet is flagged stalled only when Active with an old encoded result', function () {
    $activeStalled = Meet::factory()->active()->create(['starts_at' => now()->addMonths(3)->toDateString()]);
    EventResult::factory()->create([
        'meet_id' => $activeStalled->id,
        'encoded_at' => now()->subHours(30),
    ]);

    $activeFresh = Meet::factory()->active()->create(['starts_at' => now()->addMonths(2)->toDateString()]);
    EventResult::factory()->create([
        'meet_id' => $activeFresh->id,
        'encoded_at' => now()->subHours(2),
    ]);

    $completedStalled = Meet::factory()->completed()->create(['starts_at' => now()->addMonth()->toDateString()]);
    EventResult::factory()->create([
        'meet_id' => $completedStalled->id,
        'encoded_at' => now()->subHours(30),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('operations', 3)
            ->where('operations.0.meet_id', $activeStalled->id)
            ->where('operations.0.is_stalled', true)
            ->where('operations.1.meet_id', $activeFresh->id)
            ->where('operations.1.is_stalled', false)
            ->where('operations.2.meet_id', $completedStalled->id)
            ->where('operations.2.is_stalled', false));
});

test('performance history aggregates medal standings for the same school across meets', function () {
    $school = School::factory()->create();

    $resultA = EventResult::factory()->validated()->create();
    placeSchoolInResult($resultA, $school, 1);

    $resultB = EventResult::factory()->validated()->create();
    placeSchoolInResult($resultB, $school, 1);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('performance.districts', 1)
            ->where('performance.districts.0.district', $school->district->name)
            ->where('performance.districts.0.gold', 2)
            ->where('performance.districts.0.total', 2)
            ->has('performance.schools', 1)
            ->where('performance.schools.0.school', $school->name)
            ->where('performance.schools.0.gold', 2)
            ->where('performance.schools.0.total', 2));
});

test('performance history district standings are ordered gold, silver, bronze, then name', function () {
    $goldSchool = School::factory()->create(['name' => 'Zeta School']);
    $silverSchool = School::factory()->create([
        'name' => 'Alpha School',
        'district_id' => $goldSchool->district_id,
    ]);

    $result = EventResult::factory()->validated()->create();
    placeSchoolInResult($result, $goldSchool, 1);
    placeSchoolInResult($result, $silverSchool, 2);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('performance.schools', 2)
            ->where('performance.schools.0.position', 1)
            ->where('performance.schools.0.school', 'Zeta School')
            ->where('performance.schools.0.gold', 1)
            ->where('performance.schools.1.position', 2)
            ->where('performance.schools.1.school', 'Alpha School')
            ->where('performance.schools.1.silver', 1));
});

test('venue utilization counts slots, hours, meets, and events across meets in scope', function () {
    $venue = Venue::factory()->create(['name' => 'Sports Complex']);
    $meetA = Meet::factory()->create();
    $meetB = Meet::factory()->create();

    EventSchedule::factory()->create([
        'meet_id' => $meetA->id,
        'venue_id' => $venue->id,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meetB->id,
        'venue_id' => $venue->id,
        'starts_at' => '13:00:00',
        'ends_at' => '15:30:00',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('venues', 1)
            ->where('venues.0.venue', 'Sports Complex')
            ->where('venues.0.slots', 2)
            ->where('venues.0.hours', 4.5)
            ->where('venues.0.meets', 2)
            ->where('venues.0.events', 2));
});

test('venue utilization is narrowed to meets in scope by the school year filter', function () {
    $venue = Venue::factory()->create();
    $meetIn = Meet::factory()->create(['school_year' => '2025-2026']);
    $meetOut = Meet::factory()->create(['school_year' => '2024-2025']);

    EventSchedule::factory()->create(['meet_id' => $meetIn->id, 'venue_id' => $venue->id]);
    EventSchedule::factory()->create(['meet_id' => $meetOut->id, 'venue_id' => $venue->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management?school_year=2025-2026')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('venues', 1)
            ->where('venues.0.slots', 1)
            ->where('venues.0.meets', 1));
});

test('venue utilization lists no venues when no slots are scheduled', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('venues', 0));
});

test('delegation officers and viewers are forbidden from the management report and its download', function (User $user) {
    $this->actingAs($user)->get('/reports/management')->assertForbidden();
    $this->actingAs($user)->get('/reports/management/download')->assertForbidden();
})->with([
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'viewer' => fn () => User::factory()->create(),
]);

test('admins and organizers can view the management report with the same widgets as the dashboard', function (User $user) {
    Meet::factory()->create(['school_year' => '2025-2026']);

    $this->actingAs($user)
        ->get('/reports/management')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reports/management')
            ->has('meets', 1)
            ->has('participation.rows', 1)
            ->has('operations', 1)
            ->has('performance.districts', 0)
            ->has('venues', 0)
            ->where('schoolYear', null));
})->with([
    'admin' => fn () => User::factory()->admin()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
]);

test('the management report school year filter matches the dashboard filter', function () {
    Meet::factory()->create(['school_year' => '2024-2025']);
    Meet::factory()->create(['school_year' => '2025-2026']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/reports/management?school_year=2025-2026')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('meets', 1)
            ->where('schoolYear', '2025-2026'));
});

test('the management dashboard CSV download is audited and carries every section', function () {
    $venue = Venue::factory()->create(['name' => 'Sports Complex']);
    Meet::factory()->create();
    EventSchedule::factory()->create(['venue_id' => $venue->id]);

    $response = $this->actingAs(User::factory()->organizer()->create())
        ->get('/reports/management/download');

    $response->assertOk();

    expect($response->streamedContent())
        ->toContain('Participation - Delegations by status')
        ->toContain('Participation - Individuals & Entries')
        ->toContain('Operations Progress & Risk')
        ->toContain('Performance History')
        ->toContain('Venue Utilization')
        ->toContain('Sports Complex')
        ->and(AuditLog::query()->where('action', 'report.management_exported')->exists())->toBeTrue();
});
