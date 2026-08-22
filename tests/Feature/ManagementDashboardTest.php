<?php

use App\Enums\MeetStatus;
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

test('admins and organizers can view the management dashboard, scoped to the one meet', function (User $user) {
    $meet = Meet::current();

    $this->actingAs($user)
        ->get('/management')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('management/index')
            ->has('meets', 1)
            ->where('meets.0.id', $meet->id));
})->with([
    'admin' => fn () => User::factory()->admin()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
]);

test('participation counts delegations by status and individuals/entries for the current meet', function () {
    $meet = Meet::current();

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

test('another meet\'s delegations and individuals never leak into the current meet\'s row', function () {
    $currentMeet = Meet::current();
    Delegation::factory()->approved()->create(['meet_id' => $currentMeet->id]);

    $otherMeet = Meet::factory()->create();
    $otherDelegation = Delegation::factory()->create(['meet_id' => $otherMeet->id]);
    Athlete::factory()->create(['delegation_id' => $otherDelegation->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('participation.rows', 1)
            ->where('participation.rows.0.meet_id', $currentMeet->id)
            ->where('participation.rows.0.delegations.total', 1)
            ->where('participation.rows.0.athletes', 0));
});

test('operations progress counts results, eligibility, protests, and incidents for the current meet', function () {
    $meet = Meet::current();
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
            ->where('operations.0.results.validated', 0)
            ->where('operations.0.eligibility.pending', 1)
            ->where('operations.0.eligibility.returned', 1)
            ->where('operations.0.protests.filed', 1)
            ->where('operations.0.protests.upheld', 1)
            ->where('operations.0.incidents.open', 1)
            ->where('operations.0.incidents.resolved', 1));
});

test('the current meet is flagged stalled when Active with an old encoded result', function () {
    $meet = Meet::current();
    $meet->forceFill(['status' => MeetStatus::Active])->save();

    EventResult::factory()->create([
        'meet_id' => $meet->id,
        'encoded_at' => now()->subHours(30),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('operations', 1)
            ->where('operations.0.is_stalled', true));
});

test('an Active meet with only a fresh encoded result is not flagged stalled', function () {
    $meet = Meet::current();
    $meet->forceFill(['status' => MeetStatus::Active])->save();

    EventResult::factory()->create([
        'meet_id' => $meet->id,
        'encoded_at' => now()->subHours(2),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('operations.0.is_stalled', false));
});

test('a Completed meet with an old encoded result is not flagged stalled', function () {
    $meet = Meet::current();
    $meet->forceFill(['status' => MeetStatus::Completed])->save();

    EventResult::factory()->create([
        'meet_id' => $meet->id,
        'encoded_at' => now()->subHours(30),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/management')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('operations.0.is_stalled', false));
});

test('performance history aggregates medal standings for the same school within the current meet', function () {
    $school = School::factory()->create();
    $meet = Meet::current();

    $resultA = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
    placeSchoolInResult($resultA, $school, 1);

    $resultB = EventResult::factory()->validated()->create(['meet_id' => $meet->id]);
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

    $result = EventResult::factory()->validated()->create(['meet_id' => Meet::current()->id]);
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

test('venue utilization counts slots, hours, and events for the current meet', function () {
    $venue = Venue::factory()->create(['name' => 'Sports Complex']);
    $meet = Meet::current();

    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
        'venue_id' => $venue->id,
        'starts_at' => '08:00:00',
        'ends_at' => '10:00:00',
    ]);
    EventSchedule::factory()->create([
        'meet_id' => $meet->id,
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
            ->where('venues.0.meets', 1)
            ->where('venues.0.events', 2));
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
    $this->actingAs($user)
        ->get('/reports/management')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reports/management')
            ->has('meets', 1)
            ->has('participation.rows', 1)
            ->has('operations', 1)
            ->has('performance.districts', 0)
            ->has('venues', 0));
})->with([
    'admin' => fn () => User::factory()->admin()->create(),
    'organizer' => fn () => User::factory()->organizer()->create(),
]);

test('the management dashboard CSV download is audited and carries every section', function () {
    $venue = Venue::factory()->create(['name' => 'Sports Complex']);
    EventSchedule::factory()->create(['meet_id' => Meet::current()->id, 'venue_id' => $venue->id]);

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
