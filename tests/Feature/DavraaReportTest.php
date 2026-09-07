<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\CoachAssignmentRequest;
use App\Models\DavraaReportGroup;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\School;
use App\Models\Sport;
use App\Models\SportRosterMember;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(fn () => $this->withoutVite());

/**
 * A meet with two sports (Athletics + Basketball), events, an approved
 * delegation with rostered athletes, a sport-scoped Tournament ICT, an
 * admin, and a couple of coach accounts.
 */
function davraaContext(): array
{
    $meet = Meet::factory()->active()->create();

    $athletics = Sport::factory()->create(['name' => 'Athletics', 'is_team_sport' => false]);
    $basketball = Sport::factory()->create(['name' => 'Basketball', 'is_team_sport' => true]);

    $athleticsMeetSport = MeetSport::firstOrCreate(['meet_id' => $meet->id, 'sport_id' => $athletics->id], ['active' => true]);
    $basketballMeetSport = MeetSport::firstOrCreate(['meet_id' => $meet->id, 'sport_id' => $basketball->id], ['active' => true]);

    $events = collect([
        ['Athletics', $athletics->id, '100m', 'girls', 'secondary', false],
        ['Athletics', $athletics->id, '200m', 'girls', 'secondary', false],
        ['Athletics', $athletics->id, 'Long Jump', 'girls', 'secondary', false],
        ['Basketball', $basketball->id, 'Basketball 5x5', 'boys', 'secondary', true],
        ['Basketball', $basketball->id, 'Basketball 5x5', 'girls', 'secondary', true],
    ])->map(function (array $row) use ($meet): Event {
        $event = Event::factory()->create([
            'sport_id' => $row[1], 'name' => $row[2], 'gender' => $row[3],
            'age_division' => $row[4], 'is_team_event' => $row[5],
        ]);
        $meet->events()->attach($event);

        return $event;
    });

    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);

    $athletes = collect(range(1, 4))->map(function () use ($delegation, $athleticsMeetSport): Athlete {
        $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id, 'grade_level' => 9]);
        SportRosterMember::create([
            'meet_sport_id' => $athleticsMeetSport->id, 'delegation_id' => $delegation->id,
            'athlete_id' => $athlete->id, 'level' => 'secondary', 'gender' => 'girls',
        ]);

        return $athlete;
    });

    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    foreach ([$athleticsMeetSport, $basketballMeetSport] as $meetSport) {
        MeetSportAssignment::factory()->create([
            'meet_sport_id' => $meetSport->id, 'user_id' => $ict->id,
            'role' => MeetSportAssignmentRole::TournamentICT, 'status' => MeetSportAssignmentStatus::Active,
        ]);
    }

    $admin = User::factory()->admin()->create();

    $coachA = User::factory()->create(['role' => UserRole::Coach, 'name' => 'Santos, Maria P']);
    $coachB = User::factory()->create(['role' => UserRole::Coach, 'name' => 'Reyes, Jose T']);
    foreach ([$coachA, $coachB] as $coach) {
        CoachAssignmentRequest::create([
            'user_id' => $coach->id, 'meet_sport_id' => $athleticsMeetSport->id,
            'delegation_id' => $delegation->id, 'scope_type' => 'sport', 'status' => 'approved',
        ]);
    }

    return compact(
        'meet', 'athletics', 'basketball', 'athleticsMeetSport', 'basketballMeetSport',
        'events', 'delegation', 'athletes', 'ict', 'admin', 'coachA', 'coachB',
    );
}

function davraaGroupPayload(array $c, array $overrides = []): array
{
    return array_replace([
        'name' => 'Athletics – Secondary Girls',
        'sport_id' => $c['athletics']->id,
        'division' => 'girls',
        'level' => 'secondary',
        'notes' => null,
        'status' => 'draft',
        'event_ids' => [$c['events'][0]->id],
        'members' => [
            ['designation' => 'athlete', 'athlete_id' => $c['athletes'][0]->id, 'sort_order' => 0],
            ['designation' => 'coach', 'coach_user_id' => $c['coachA']->id, 'sort_order' => 1],
        ],
    ], $overrides);
}

test('a sport-scoped Tournament ICT can create a DAVRAA Report Group', function () {
    $c = davraaContext();

    $this->actingAs($c['ict'])
        ->post(route('davraa-reports.store'), davraaGroupPayload($c))
        ->assertRedirect()->assertSessionHasNoErrors();

    $group = DavraaReportGroup::sole();
    expect($group->name)->toBe('Athletics – Secondary Girls')
        ->and($group->sport_id)->toBe($c['athletics']->id)
        ->and($group->members()->count())->toBe(2)
        ->and($group->events()->count())->toBe(1)
        ->and(AuditLog::where('action', 'davraa_report.created')->exists())->toBeTrue();
});

test('the same Sport supports many independent report groups, each with its own events and coach', function () {
    $c = davraaContext();

    $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c, [
        'name' => 'Athletics – Sprints (Coach Santos)',
        'event_ids' => [$c['events'][0]->id, $c['events'][1]->id],
        'members' => [
            ['designation' => 'coach', 'coach_user_id' => $c['coachA']->id, 'sort_order' => 0],
            ['designation' => 'athlete', 'athlete_id' => $c['athletes'][0]->id, 'sort_order' => 1],
            ['designation' => 'athlete', 'athlete_id' => $c['athletes'][1]->id, 'sort_order' => 2],
        ],
    ]))->assertSessionHasNoErrors();

    $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c, [
        'name' => 'Athletics – Jumps (Coach Reyes)',
        'event_ids' => [$c['events'][2]->id],
        'members' => [
            ['designation' => 'coach', 'coach_user_id' => $c['coachB']->id, 'sort_order' => 0],
            ['designation' => 'athlete', 'athlete_id' => $c['athletes'][2]->id, 'sort_order' => 1],
        ],
    ]))->assertSessionHasNoErrors();

    $groups = DavraaReportGroup::where('sport_id', $c['athletics']->id)->get();
    expect($groups)->toHaveCount(2);
    // One coach covers multiple events in a single group…
    expect($groups->firstWhere('name', 'Athletics – Sprints (Coach Santos)')->events()->count())->toBe(2);
    // …and different coaches run separate groups for the same sport.
    expect($groups->pluck('id'))->each->not->toBeNull();
    expect(
        $groups->flatMap(fn ($g) => $g->members()->where('designation', 'coach')->pluck('coach_user_id'))->unique()->sort()->values()->all()
    )->toBe([$c['coachA']->id, $c['coachB']->id]);
});

test('Basketball can carry separate 5x5 / 3x3 style groups by division and level', function () {
    $c = davraaContext();
    $variants = [
        ['Basketball 5x5 Secondary Boys', 'boys', 'secondary'],
        ['Basketball 5x5 Secondary Girls', 'girls', 'secondary'],
        ['Basketball 5x5 Elementary Boys', 'boys', 'elementary'],
        ['Basketball 3x3 Secondary Boys', 'boys', 'secondary'],
    ];

    foreach ($variants as [$name, $division, $level]) {
        $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c, [
            'name' => $name,
            'sport_id' => $c['basketball']->id,
            'division' => $division,
            'level' => $level,
            'event_ids' => [],
            'members' => [
                ['designation' => 'athlete', 'athlete_id' => $c['athletes'][0]->id, 'sort_order' => 0],
            ],
        ]))->assertSessionHasNoErrors();
    }

    expect(DavraaReportGroup::where('sport_id', $c['basketball']->id)->count())->toBe(4);
});

test('athlete options are filtered to the selected Sport roster and coaches to the meet', function () {
    $c = davraaContext();
    // An athlete rostered under Basketball, not Athletics.
    $other = Athlete::factory()->create(['delegation_id' => $c['delegation']->id]);
    SportRosterMember::create([
        'meet_sport_id' => $c['basketballMeetSport']->id, 'delegation_id' => $c['delegation']->id,
        'athlete_id' => $other->id, 'level' => 'secondary', 'gender' => 'boys',
    ]);

    $response = $this->actingAs($c['ict'])
        ->getJson(route('davraa-reports.options', ['sport_id' => $c['athletics']->id]))
        ->assertOk();

    $athleteIds = collect($response->json('athletes'))->pluck('id');
    expect($athleteIds)->toContain($c['athletes'][0]->id)
        ->and($athleteIds)->not->toContain($other->id);
    expect(collect($response->json('coaches'))->pluck('id'))->toContain($c['coachA']->id);
});

test('ICT may include a rostered qualifier even with no Event Entry recorded', function () {
    $c = davraaContext();
    // $c['athletes'] have roster rows but no Entry rows at all.
    $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c, [
        'members' => collect($c['athletes'])->map(fn ($a, $i) => [
            'designation' => 'athlete', 'athlete_id' => $a->id, 'sort_order' => $i,
        ])->all(),
    ]))->assertSessionHasNoErrors();

    expect(DavraaReportGroup::sole()->members()->where('designation', 'athlete')->count())->toBe(4);
});

test('selected membership and manual ordering persist after save and reopen', function () {
    $c = davraaContext();
    $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c, [
        'members' => [
            ['designation' => 'coach', 'coach_user_id' => $c['coachA']->id, 'sort_order' => 0],
            ['designation' => 'athlete', 'athlete_id' => $c['athletes'][2]->id, 'sort_order' => 1],
            ['designation' => 'athlete', 'athlete_id' => $c['athletes'][0]->id, 'sort_order' => 2],
        ],
    ]))->assertSessionHasNoErrors();

    $group = DavraaReportGroup::sole();
    $ordered = $group->members()->get();
    expect($ordered->pluck('designation')->map->value->all())->toBe(['coach', 'athlete', 'athlete'])
        ->and($ordered->pluck('athlete_id')->all())->toBe([null, $c['athletes'][2]->id, $c['athletes'][0]->id]);

    $this->actingAs($c['ict'])->get(route('davraa-reports.edit', $group))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('davraa-reports/form')
            ->where('group.members.0.coach_user_id', $c['coachA']->id)
            ->where('group.members.1.athlete_id', $c['athletes'][2]->id)
            ->where('group.members.2.athlete_id', $c['athletes'][0]->id));
});

test('a report with missing school / district data prints and exports without a 500', function () {
    $c = davraaContext();
    // A school with no municipality/district linked at all.
    $orphanSchool = School::factory()->create(['district_id' => null, 'school_district_id' => null]);
    $bare = Athlete::factory()->create([
        'delegation_id' => $c['delegation']->id, 'school_id' => $orphanSchool->id,
    ]);
    SportRosterMember::create([
        'meet_sport_id' => $c['athleticsMeetSport']->id, 'delegation_id' => $c['delegation']->id,
        'athlete_id' => $bare->id, 'level' => 'secondary', 'gender' => 'girls',
    ]);
    // A coach with no personnel profile row.
    $bareCoach = User::factory()->create(['role' => UserRole::Coach, 'name' => 'Cruz']);

    $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c, [
        'members' => [
            ['designation' => 'athlete', 'athlete_id' => $bare->id, 'sort_order' => 0],
            ['designation' => 'chaperone', 'coach_user_id' => $bareCoach->id, 'sort_order' => 1],
        ],
    ]))->assertSessionHasNoErrors();

    $group = DavraaReportGroup::sole();

    $this->actingAs($c['ict'])->get(route('davraa-reports.print', $group))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('davraa-reports/print')
            ->where('report.rows.0.designation', 'ATHLETE')
            ->where('report.rows.0.district_name', '')
            ->where('report.rows.0.incomplete', fn ($v) => collect($v)->contains('district_name')));

    $export = $this->actingAs($c['ict'])->get(route('davraa-reports.export', $group))->assertOk();
    $csv = $export->streamedContent();
    expect($csv)
        ->toContain('LIST OF RECOMMENDED QUALIFIERS TO DAVRAA')
        ->toContain('Division')
        ->toContain('Name of District')
        ->toContain('ATHLETE')
        ->toContain('CHAPERONE')
        ->toContain('Prepared By:')
        ->toContain('Tournament Manager');
});

test('the print payload follows the DAVRAA template structure', function () {
    $c = davraaContext();
    $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c))->assertSessionHasNoErrors();
    $group = DavraaReportGroup::sole();

    $this->actingAs($c['ict'])->get(route('davraa-reports.print', $group))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('davraa-reports/print')
            ->where('report.division', 'GIRLS')
            ->where('report.level', 'SEC')
            ->where('report.event', '100m')
            ->has('report.rows', 2)
            ->where('report.rows.0.no', 1)
            ->where('report.rows.0.designation', 'ATHLETE')
            ->where('report.rows.1.designation', 'COACH'));
});

test('groups can be duplicated, archived and restored without hard deletion', function () {
    $c = davraaContext();
    $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c))->assertSessionHasNoErrors();
    $group = DavraaReportGroup::sole();

    $this->actingAs($c['ict'])->post(route('davraa-reports.duplicate', $group))->assertRedirect();
    $copy = DavraaReportGroup::where('id', '!=', $group->id)->sole();
    expect($copy->name)->toContain('(Copy)')
        ->and($copy->members()->count())->toBe(2)
        ->and($copy->events()->count())->toBe(1);

    $this->actingAs($c['ict'])->patch(route('davraa-reports.status', $group), ['status' => 'archived'])->assertRedirect();
    expect($group->fresh()->status->value)->toBe('archived');
    // Archived group is hidden from the default list but still in the database.
    $this->actingAs($c['ict'])->get(route('davraa-reports.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('groups', fn ($groups) => collect($groups)->doesntContain('id', $group->id)));
    expect(DavraaReportGroup::whereKey($group->id)->exists())->toBeTrue();

    $this->actingAs($c['ict'])->patch(route('davraa-reports.status', $group), ['status' => 'draft'])->assertRedirect();
    expect($group->fresh()->status->value)->toBe('draft');
});

test('a Coach, a Viewer, and an ICT scoped to another sport cannot manage DAVRAA reports', function () {
    $c = davraaContext();
    $this->actingAs($c['ict'])->post(route('davraa-reports.store'), davraaGroupPayload($c))->assertSessionHasNoErrors();
    $group = DavraaReportGroup::sole();

    $coach = $c['coachA'];
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $otherSport = Sport::factory()->create(['name' => 'Chess']);
    $otherMeetSport = MeetSport::firstOrCreate(['meet_id' => $c['meet']->id, 'sport_id' => $otherSport->id], ['active' => true]);
    $otherIct = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $otherMeetSport->id, 'user_id' => $otherIct->id,
        'role' => MeetSportAssignmentRole::TournamentICT, 'status' => MeetSportAssignmentStatus::Active,
    ]);

    foreach ([$coach, $viewer] as $user) {
        $this->actingAs($user)->get(route('davraa-reports.index'))->assertForbidden();
        $this->actingAs($user)->post(route('davraa-reports.store'), davraaGroupPayload($c))->assertForbidden();
    }

    // The other-sport ICT can open the module but not this Athletics group.
    $this->actingAs($otherIct)->get(route('davraa-reports.index'))->assertOk();
    $this->actingAs($otherIct)->get(route('davraa-reports.edit', $group))->assertForbidden();
    $this->actingAs($otherIct)->put(route('davraa-reports.update', $group), davraaGroupPayload($c))->assertForbidden();
    $this->actingAs($otherIct)->post(route('davraa-reports.store'), davraaGroupPayload($c))->assertForbidden();
});

test('System Admin has full DAVRAA management across every sport', function () {
    $c = davraaContext();
    $this->actingAs($c['admin'])->post(route('davraa-reports.store'), davraaGroupPayload($c, [
        'sport_id' => $c['basketball']->id, 'name' => 'Admin group', 'event_ids' => [],
        'members' => [['designation' => 'athlete', 'athlete_id' => $c['athletes'][0]->id, 'sort_order' => 0]],
    ]))->assertSessionHasNoErrors();

    expect(DavraaReportGroup::sole()->creator->id)->toBe($c['admin']->id);
});

test('existing PMMS pages are unaffected by the DAVRAA module', function () {
    $c = davraaContext();
    $this->actingAs($c['admin'])->get('/results')->assertOk();
    $this->actingAs($c['admin'])->get('/athletes')->assertOk();
    $this->actingAs($c['admin'])->get('/tally')->assertOk();
    $this->actingAs($c['admin'])->get('/matches')->assertOk();
});
